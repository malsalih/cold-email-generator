<?php

namespace App\Http\Controllers;

use App\Models\WarmingAccount;
use App\Models\WarmingLog;
use App\Models\WarmingTemplate;
use App\Models\WarmingStrategy;
use App\Models\WarmingSetting;
use App\Models\Campaign;
use App\Models\BotLog;
use Illuminate\Http\Request;

/**
 * WarmingApiController — JSON API for the Warming Bot (Node.js/Puppeteer).
 *
 * These endpoints are consumed by warming_bot/bot.js and the dashboard AJAX.
 * All responses are JSON. No authentication required (internal use only).
 *
 * Safety features built into this API:
 * - Automatic stale job recovery (processing > 10 min → pending)
 * - Daily send limits (100/account)
 * - Job verification endpoint (prevents double-sends)
 * - Real-time bot event logging (for dashboard monitor)
 */
class WarmingApiController extends Controller
{
    /**
     * Get the next pending job for the bot to process.
     * Called by the Node.js warming bot.
     *
     * Accepts optional ?account_id=X to filter jobs for a specific account.
     * This prevents the bot from picking up jobs for a different account
     * (which would open the wrong browser session).
     */
    public function nextJob(Request $request)
    {
        // Reset stale 'processing' jobs (stuck > 10 min)
        WarmingLog::where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(10))
            ->update(['status' => 'pending']);

        // Build query base
        $query = WarmingLog::where('status', 'pending');

        if ($request->input('mode') === 'send_later') {
            // Send Later mode: pick up ANY pending campaign job regardless of time,
            // because the bot needs to schedule them into Zoho NOW so Zoho sends them later.
            $query->where('source_type', 'campaign');
        } else {
            // Standard execution mode: only pick up jobs that are due right now
            $query->where(function ($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', now());
            });
        }
        
        $query->with(['account', 'campaign'])
            ->orderBy('scheduled_at', 'asc')
            ->orderBy('created_at', 'asc');

        // Filter by account if specified (prevents cross-account session issues)
        if ($request->has('account_id')) {
            $query->where('warming_account_id', $request->input('account_id'));
        }

        $pendingLog = $query->first();

        if ($pendingLog) {
            $account = $pendingLog->account;

            if (!$account || !$account->is_logged_in) {
                return response()->json([
                    'has_job' => false,
                    'reason' => 'Account not logged in',
                ]);
            }

            // Check if campaign is paused
            if ($pendingLog->campaign && $pendingLog->campaign->status === 'paused') {
                return response()->json([
                    'has_job' => false,
                    'reason' => 'Campaign is paused',
                ]);
            }

            // Check daily limit (max 100 per account)
            $sentToday = WarmingLog::where('warming_account_id', $account->id)
                ->where('status', 'sent')
                ->whereDate('sent_at', today())
                ->count();
            if ($sentToday >= 100) {
                return response()->json([
                    'has_job' => false,
                    'reason' => 'Daily limit reached (100) for ' . $account->email,
                ]);
            }

            // Mark as processing immediately to prevent double-pick
            $pendingLog->update(['status' => 'processing']);

            $sendMode = \App\Models\WarmingSetting::getSendMode();

            // Determine send mode: campaign jobs use send_later if schedule_send_at is set
            $jobSendMode = $sendMode;
            $scheduleSendAt = null;
            $jobTimezone = null;

            if ($pendingLog->schedule_send_at && $pendingLog->campaign_id) {
                $jobSendMode = 'send_later';
                $scheduleSendAt = $pendingLog->schedule_send_at->format('Y-m-d H:i');
                $campaign = $pendingLog->campaign;
                $jobTimezone = $campaign->timezone ?? 'Asia/Riyadh';
            }

            return response()->json([
                'has_job' => true,
                'type' => 'queued',
                'send_mode' => $jobSendMode,
                'job' => [
                    'log_id' => $pendingLog->id,
                    'account_id' => $account->id,
                    'account_email' => $account->email,
                    'session_dir' => $account->getSessionPath(),
                    'recipient' => $pendingLog->recipient_email,
                    'subject' => $pendingLog->subject_sent,
                    'body' => $pendingLog->body_sent,
                    'campaign_id' => $pendingLog->campaign_id,
                    'schedule_send_at' => $scheduleSendAt,
                    'timezone' => $jobTimezone,
                ],
            ]);
        }

        // If account filter is active and no pending jobs remain → signal bot to stop
        if ($request->has('account_id')) {
            $accountId = $request->input('account_id');
            $stillPending = WarmingLog::where('warming_account_id', $accountId)
                ->whereIn('status', ['pending', 'processing'])
                ->count();

            return response()->json([
                'has_job' => false,
                'queue_empty' => $stillPending === 0,
                'reason' => $stillPending === 0
                    ? 'All jobs for this account are completed!'
                    : "Waiting — {$stillPending} jobs not yet ready (scheduled for later)",
            ]);
        }

        // Auto-generate warming job if no pending jobs
        $strategy = WarmingStrategy::getDefault();

        if (!$strategy->isWithinActiveHours()) {
            return response()->json([
                'has_job' => false,
                'reason' => 'Outside active hours (' . $strategy->active_hours_start . ' - ' . $strategy->active_hours_end . ')',
            ]);
        }

        // Find an active account that can still send today
        $account = WarmingAccount::ready()
            ->whereColumn('current_day_sent', '<', 'daily_limit')
            ->orderBy('last_sent_at', 'asc') // prioritize least recently used
            ->first();

        if (!$account) {
            return response()->json([
                'has_job' => false,
                'reason' => 'No accounts available (all at daily limit or inactive)',
            ]);
        }

        // Pick a random active template
        $template = WarmingTemplate::active()->inRandomOrder()->first();

        if (!$template) {
            return response()->json([
                'has_job' => false,
                'reason' => 'No active templates available',
            ]);
        }

        // We need recipients - check if any were provided
        // For now, return info about needing a recipient entry
        return response()->json([
            'has_job' => false,
            'reason' => 'No recipients queued. Add warming emails via the dashboard.',
            'account_ready' => true,
            'account_email' => $account->email,
            'suggested_delay' => $strategy->getRandomDelay(),
        ]);
    }

    /**
     * Report the result of a send attempt from the bot.
     */
    public function report(Request $request)
    {
        $validated = $request->validate([
            'log_id' => 'required|integer|exists:warming_logs,id',
            'status' => 'required|string|in:sent,failed',
            'error_message' => 'nullable|string',
        ]);

        $log = WarmingLog::findOrFail($validated['log_id']);

        $log->update([
            'status' => $validated['status'],
            'error_message' => $validated['error_message'] ?? null,
            'sent_at' => $validated['status'] === 'sent' ? now() : null,
        ]);

        // Update account counters if sent successfully
        if ($validated['status'] === 'sent' && $log->account) {
            $log->account->incrementSent();

            // Mark template as used
            if ($log->template) {
                $log->template->markUsed();
            }
        }

        // Update generated email status if this was a campaign email
        if ($log->generated_email_id) {
            $log->generatedEmail()->update([
                'sending_status' => $validated['status'] === 'sent' ? 'sent' : 'failed',
            ]);
        }

        // Refresh campaign counts
        if ($log->campaign_id) {
            $log->campaign->refreshCounts();
        }

        return response()->json([
            'success' => true,
            'message' => $validated['status'] === 'sent' ? 'Email sent successfully' : 'Send failure recorded',
        ]);
    }

    /**
     * Check if an account's session is still valid.
     */
    public function sessionStatus(WarmingAccount $account)
    {
        return response()->json([
            'account_id' => $account->id,
            'email' => $account->email,
            'is_logged_in' => $account->is_logged_in,
            'session_dir' => $account->getSessionPath(),
            'can_send' => $account->canSendToday(),
            'remaining_today' => $account->remaining_today,
        ]);
    }

    /**
     * Mark an account as logged in (called by login.js after successful login).
     */
    public function markLoggedIn(Request $request)
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:warming_accounts,id',
        ]);

        $account = WarmingAccount::findOrFail($validated['account_id']);
        $account->update([
            'is_logged_in' => true,
            'last_login_check' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account marked as logged in',
        ]);
    }

    /**
     * Get current warming settings (for the bot).
     */
    public function getSettings()
    {
        return response()->json([
            'send_mode' => WarmingSetting::getSendMode(),
        ]);
    }

    // ─── BOT LIVE MONITOR ────────────────────────────────────

    /**
     * Receive a live log event from the bot.
     */
    public function pushBotLog(Request $request)
    {
        $validated = $request->validate([
            'event' => 'required|string',
            'message' => 'required|string',
            'log_id' => 'nullable|integer',
            'metadata' => 'nullable|array',
            'session_id' => 'nullable|string',
        ]);

        BotLog::create([
            'event' => $validated['event'],
            'message' => $validated['message'],
            'log_id' => $validated['log_id'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
            'created_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Get current bot status and recent logs for the dashboard.
     */
    public function getBotStatus()
    {
        return response()->json(BotLog::getLatestStatus());
    }

    /**
     * Verify a job is still valid before the bot sends it.
     */
    public function verifyJob($logId)
    {
        $log = WarmingLog::find($logId);

        if (!$log) {
            return response()->json(['valid' => false, 'reason' => 'Job not found']);
        }

        if ($log->status === 'sent') {
            return response()->json(['valid' => false, 'reason' => 'Already sent']);
        }

        if (!in_array($log->status, ['processing', 'pending'])) {
            return response()->json(['valid' => false, 'reason' => 'Invalid status: ' . $log->status]);
        }

        // Verify fields are complete
        if (empty($log->recipient_email) || empty($log->subject_sent) || empty($log->body_sent)) {
            return response()->json(['valid' => false, 'reason' => 'Missing required fields']);
        }

        return response()->json([
            'valid' => true,
            'recipient' => $log->recipient_email,
            'subject' => $log->subject_sent,
        ]);
    }

    /**
     * Called by bot when it finishes all jobs for an account.
     * Checks if ALL accounts for a campaign are done, then marks campaign as completed.
     */
    public function botComplete(Request $request)
    {
        $accountId = $request->input('account_id');
        if (!$accountId) {
            return response()->json(['ok' => false, 'reason' => 'No account_id']);
        }

        // Find campaigns that this account belongs to and are currently running
        $campaigns = Campaign::where('status', 'running')
            ->whereJsonContains('warming_account_ids', (int) $accountId)
            ->get();

        foreach ($campaigns as $campaign) {
            // Check if ALL pending/processing logs for this campaign are done
            $remaining = WarmingLog::where('campaign_id', $campaign->id)
                ->whereIn('status', ['pending', 'processing'])
                ->count();

            if ($remaining === 0) {
                $sentCount = WarmingLog::where('campaign_id', $campaign->id)
                    ->where('status', 'sent')
                    ->count();
                    
                $campaign->update([
                    'status' => 'completed',
                    'sent_count' => $sentCount,
                ]);
                \Log::info("Campaign #{$campaign->id} '{$campaign->name}' completed automatically. Sent: {$sentCount}");
            }
        }

        return response()->json(['ok' => true]);
    }
}
