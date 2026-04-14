<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\GeneratedEmail;
use App\Models\WarmingAccount;
use App\Models\WarmingLog;
use Illuminate\Http\Request;

/**
 * CampaignController — Full campaign lifecycle management.
 *
 * Features:
 * - Create campaigns with smart Send Later scheduling
 * - Launch campaigns (starts bot in send_later mode)
 * - Pause/resume/delete campaigns
 * - Follow-up chain: manual or auto selection of non-responders
 */
class CampaignController extends Controller
{
    /**
     * List all campaigns (root only, follow-ups nested).
     */
    public function index()
    {
        $campaigns = Campaign::with(['generatedEmail', 'followUps'])
            ->rootOnly()
            ->latestFirst()
            ->paginate(15);

        $stats = [
            'total' => Campaign::count(),
            'running' => Campaign::where('status', 'running')->count(),
            'completed' => Campaign::where('status', 'completed')->count(),
            'scheduled' => Campaign::where('status', 'scheduled')->count(),
        ];

        return view('campaigns.index', compact('campaigns', 'stats'));
    }

    /**
     * Show campaign creation form.
     */
    public function create(Request $request)
    {
        $accounts = WarmingAccount::ready()->get();
        $generatedEmails = GeneratedEmail::latestFirst()->limit(20)->get();
        $preselectedEmail = null;

        if ($request->filled('email_id')) {
            $preselectedEmail = GeneratedEmail::find($request->input('email_id'));
        }

        // Pre-fill data when coming from Quick Campaign or Campaign from Variants
        $prefill = [
            'name' => $request->input('prefill_name', ''),
            'accounts' => $request->input('prefill_accounts', []),
            'recipients' => $request->input('prefill_recipients', ''),
            'subject' => $request->input('prefill_subject', ''),
            'body' => $request->input('prefill_body', ''),
            'variant_index' => $request->input('variant_index', 0),
            'multi_variant' => $request->boolean('multi_variant'),
        ];

        return view('campaigns.create', compact('accounts', 'generatedEmails', 'preselectedEmail', 'prefill'));
    }

    /**
     * Store a new campaign with Send Later scheduling.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'warming_account_ids' => 'required|array|min:1',
            'warming_account_ids.*' => 'exists:warming_accounts,id',
            'generated_email_id' => 'nullable|exists:generated_emails,id',
            'custom_subject' => 'required_without:generated_email_id|nullable|string|max:500',
            'custom_body' => 'required_without:generated_email_id|nullable|string',
            'recipients_text' => 'required|string',
            'send_start_date' => 'required|date|after_or_equal:today',
            'send_start_time' => 'required|date_format:H:i',
            'send_end_time' => 'required|date_format:H:i|after:send_start_time',
            'min_delay_minutes' => 'required|integer|min:2|max:60',
            'max_delay_minutes' => 'required|integer|min:2|max:120|gte:min_delay_minutes',
            'timezone' => 'required|string',
            'variant_index' => 'nullable|integer|min:0',
            // Follow-up settings
            'auto_followup' => 'nullable|boolean',
            'followup_wait_days' => 'nullable|integer|min:1|max:30',
            'max_followups' => 'nullable|integer|min:1|max:5',
        ], [
            'name.required' => __('validation.required', ['attribute' => __('campaign.campaign_name')]),
            'warming_account_ids.required' => __('validation.required', ['attribute' => __('campaign.sending_accounts')]),
            'recipients_text.required' => __('validation.required', ['attribute' => __('campaign.target_recipients')]),
            'send_start_date.required' => __('validation.required', ['attribute' => __('campaign.start_send_date')]),
            'send_start_time.required' => __('validation.required', ['attribute' => __('campaign.start_send_time')]),
            'send_end_time.required' => __('validation.required', ['attribute' => __('campaign.end_send_time')]),
            'send_end_time.after' => __('validation.after', ['attribute' => __('campaign.end_send_time'), 'date' => __('campaign.start_send_time')]),
            'max_delay_minutes.gte' => __('validation.gte.numeric', ['attribute' => __('campaign.max_delay'), 'value' => __('campaign.min_delay')]),
        ]);

        // Parse recipients
        $recipients = array_values(array_unique(array_filter(
            preg_split('/[\s,;]+/', $validated['recipients_text']),
            fn($e) => filter_var(trim($e), FILTER_VALIDATE_EMAIL)
        )));

        if (empty($recipients)) {
            return back()->withInput()->with('error', __('messages.invalid_emails'));
        }

        // Resolve subject/body
        $customSubject = $validated['custom_subject'] ?? null;
        $customBody = $validated['custom_body'] ?? null;

        // Check if multi-variant mode
        $emailVariants = null;
        if (!empty($validated['generated_email_id'])) {
            $genEmail = GeneratedEmail::find($validated['generated_email_id']);
            if ($genEmail) {
                if ($request->boolean('multi_variant')) {
                    // Multi-template: store ALL variants with their recipients
                    $emailVariants = $genEmail->generated_variants;
                } else {
                    $variantIndex = $validated['variant_index'] ?? 0;
                    $variants = $genEmail->generated_variants;
                    if (!empty($variants[$variantIndex])) {
                        $customSubject = $customSubject ?: ($variants[$variantIndex]['subject'] ?? $genEmail->generated_subject);
                        $customBody = $customBody ?: ($variants[$variantIndex]['body'] ?? $genEmail->generated_body);
                    }
                }
            }
        }

        $campaign = Campaign::create([
            'name' => $validated['name'],
            'warming_account_ids' => $validated['warming_account_ids'],
            'generated_email_id' => $validated['generated_email_id'] ?? null,
            'custom_subject' => $customSubject,
            'custom_body' => $customBody,
            'email_variants' => $emailVariants,
            'recipients' => $recipients,
            'send_start_date' => $validated['send_start_date'],
            'send_start_time' => $validated['send_start_time'],
            'send_end_time' => $validated['send_end_time'],
            'min_delay_minutes' => $validated['min_delay_minutes'],
            'max_delay_minutes' => $validated['max_delay_minutes'],
            'delay_minutes' => $validated['min_delay_minutes'],
            'timezone' => $validated['timezone'],
            'auto_followup' => $validated['auto_followup'] ?? false,
            'followup_wait_days' => $validated['followup_wait_days'] ?? 3,
            'max_followups' => $validated['max_followups'] ?? 3,
            'status' => 'draft',
            'total_recipients' => count($recipients),
        ]);

        // Create scheduled WarmingLogs with business-hours distribution
        $campaign->createScheduledLogs();

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', __('messages.campaign_created', ['count' => $campaign->total_recipients]));
    }

    /**
     * Show campaign details, progress, and timeline.
     */
    public function show(Campaign $campaign)
    {
        $campaign->refreshCounts();
        $campaign->load(['generatedEmail', 'followUps.logs', 'logs' => function ($q) {
            $q->orderBy('schedule_send_at', 'asc');
        }]);

        return view('campaigns.show', compact('campaign'));
    }

    /**
     * Launch a campaign — starts the bot in send_later mode.
     */
    public function launch(Campaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', __('messages.campaign_status_error', ['status' => $campaign->status_label]));
        }

        $campaign->update(['status' => 'running']);

        // Kill existing bot running for campaign
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('wmic process where "CommandLine like \'%warming_bot\\\\bot.js%\' and Name=\'node.exe\'" call terminate 2>nul');
            exec('wmic process where "CommandLine like \'%warming_sessions%\' and Name=\'chrome.exe\'" call terminate 2>nul');
        } else {
            exec("pkill -f 'node.*bot.js' 2>/dev/null");
            exec("pkill -f 'chrome.*warming_sessions' 2>/dev/null");
        }
        sleep(1);

        // Start bot in send_later mode for each of this campaign's accounts
        $botPath = base_path('warming_bot/bot.js');
        $tz = $campaign->timezone;
        $accounts = $campaign->accounts;
        
        foreach ($accounts as $account) {
            $flags = "--loop --account={$account->id} --send-later --timezone=\"{$tz}\"";
            $command = "node \"{$botPath}\" {$flags}";

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $windowStyle = app()->isProduction() ? 'Hidden' : 'Normal';
                $psCommand = "Start-Process node -ArgumentList '\"{$botPath}\" {$flags}' -WindowStyle {$windowStyle}";
                pclose(popen("powershell -WindowStyle Hidden -Command \"{$psCommand}\"", 'r'));
            } else {
                exec("nohup {$command} > /dev/null 2>&1 &");
            }
        }

        return back()->with('success', __('messages.campaign_launched', ['accounts' => count($accounts)]));
    }

    /**
     * Show follow-up creation form.
     */
    public function createFollowUp(Campaign $campaign)
    {
        if ($campaign->status !== 'completed') {
            return back()->with('error', __('messages.complete_before_followup'));
        }

        $nextFollowUpNumber = $campaign->followUps()->count() + 1;

        if ($nextFollowUpNumber === 1) {
            $availableRecipients = $campaign->getSentRecipients();
        } else {
            // Sequential chaining: Follow-up #2 goes to recipients of Follow-up #1, and so on.
            $lastFollowUp = $campaign->followUps()->orderBy('followup_number', 'desc')->first();
            $availableRecipients = $lastFollowUp ? $lastFollowUp->getSentRecipients() : [];
        }

        return view('campaigns.follow_up', compact('campaign', 'availableRecipients', 'nextFollowUpNumber'));
    }

    /**
     * Store a follow-up campaign linked to the parent.
     */
    public function storeFollowUp(Request $request, Campaign $campaign)
    {
        try {
            $validated = $request->validate([
                'custom_subject' => 'required|string|max:500',
                'custom_body' => 'required|string',
                'selected_recipients' => 'required|array|min:1',
                'selected_recipients.*' => 'email',
                'send_start_date' => 'required|date|after_or_equal:today',
                'send_start_time' => 'required',
                'send_end_time' => 'required',
                'min_delay_minutes' => 'required|integer|min:2|max:60',
                'max_delay_minutes' => 'required|integer|min:2|max:120|gte:min_delay_minutes',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            dd($e->errors(), $request->all());
        }

        $nextNumber = $campaign->followUps()->count() + 1;

        $followUp = Campaign::create([
            'name' => $campaign->name . " — Follow-Up #{$nextNumber}",
            'warming_account_ids' => $campaign->warming_account_ids,
            'custom_subject' => $validated['custom_subject'],
            'custom_body' => $validated['custom_body'],
            'recipients' => $validated['selected_recipients'],
            'send_start_date' => $validated['send_start_date'],
            'send_start_time' => $validated['send_start_time'],
            'send_end_time' => $validated['send_end_time'],
            'min_delay_minutes' => $validated['min_delay_minutes'],
            'max_delay_minutes' => $validated['max_delay_minutes'],
            'delay_minutes' => $validated['min_delay_minutes'],
            'timezone' => $campaign->timezone,
            'parent_campaign_id' => $campaign->id,
            'followup_number' => $nextNumber,
            'status' => 'draft',
            'total_recipients' => count($validated['selected_recipients']),
        ]);

        $followUp->createScheduledLogs();

        return redirect()->route('campaigns.show', $followUp)
            ->with('success', __('messages.followup_created', ['number' => $nextNumber, 'recipients' => $followUp->total_recipients]));
    }

    /**
     * Pause a running campaign.
     */
    public function pause(Campaign $campaign)
    {
        if (in_array($campaign->status, ['running', 'scheduled'])) {
            $campaign->update(['status' => 'paused']);
            $campaign->logs()->where('status', 'pending')->update(['status' => 'paused']);
            
            // Kill this specific campaign's bots
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                exec("taskkill /F /FI \"WINDOWTITLE eq ColdForge Campaign Bot*{$campaign->name}*\" 2>nul");
            } else {
                // Approximate for linux
                exec("pkill -f 'node.*bot.js' 2>/dev/null");
            }
        }

        return back()->with('success', __('messages.campaign_paused'));
    }

    /**
     * Resume a paused campaign.
     */
    public function resume(Campaign $campaign)
    {
        if ($campaign->status === 'paused') {
            $campaign->update(['status' => 'scheduled']); // Set to scheduled so launch() accepts it
            $campaign->logs()->where('status', 'paused')->update(['status' => 'pending']);
            
            // Auto-relaunch
            return $this->launch($campaign);
        }

        return back()->with('success', __('messages.campaign_resumed'));
    }

    /**
     * Show campaign edit form.
     */
    public function edit(Campaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', __('messages.cannot_edit_status'));
        }

        $accounts = WarmingAccount::ready()->get();
        $generatedEmails = GeneratedEmail::latestFirst()->limit(20)->get();

        return view('campaigns.edit', compact('campaign', 'accounts', 'generatedEmails'));
    }

    /**
     * Update campaign settings and regenerate scheduled logs.
     */
    public function update(Request $request, Campaign $campaign)
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', __('messages.cannot_edit_status'));
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'warming_account_ids' => 'required|array|min:1',
            'warming_account_ids.*' => 'exists:warming_accounts,id',
            'custom_subject' => 'nullable|string|max:500',
            'custom_body' => 'nullable|string',
            'recipients_text' => 'required|string',
            'send_start_date' => 'required|date|after_or_equal:today',
            'send_start_time' => 'required|date_format:H:i',
            'send_end_time' => 'required|date_format:H:i|after:send_start_time',
            'min_delay_minutes' => 'required|integer|min:2|max:60',
            'max_delay_minutes' => 'required|integer|min:2|max:120|gte:min_delay_minutes',
            'timezone' => 'required|string',
            'auto_followup' => 'nullable|boolean',
            'followup_wait_days' => 'nullable|integer|min:1|max:30',
            'max_followups' => 'nullable|integer|min:1|max:5',
        ]);

        // Parse recipients
        $recipients = array_values(array_unique(array_filter(
            preg_split('/[\s,;]+/', $validated['recipients_text']),
            fn($e) => filter_var(trim($e), FILTER_VALIDATE_EMAIL)
        )));

        if (empty($recipients)) {
            return back()->withInput()->with('error', __('messages.invalid_emails'));
        }

        $campaign->update([
            'name' => $validated['name'],
            'warming_account_ids' => $validated['warming_account_ids'],
            'custom_subject' => $validated['custom_subject'] ?? $campaign->custom_subject,
            'custom_body' => $validated['custom_body'] ?? $campaign->custom_body,
            'recipients' => $recipients,
            'send_start_date' => $validated['send_start_date'],
            'send_start_time' => $validated['send_start_time'],
            'send_end_time' => $validated['send_end_time'],
            'min_delay_minutes' => $validated['min_delay_minutes'],
            'max_delay_minutes' => $validated['max_delay_minutes'],
            'timezone' => $validated['timezone'],
            'auto_followup' => $validated['auto_followup'] ?? false,
            'followup_wait_days' => $validated['followup_wait_days'] ?? 3,
            'max_followups' => $validated['max_followups'] ?? 3,
            'total_recipients' => count($recipients),
        ]);

        // Delete old pending logs and regenerate
        $campaign->logs()->whereIn('status', ['pending', 'paused'])->delete();
        $campaign->update(['sent_count' => 0, 'failed_count' => 0, 'status' => 'draft']);
        $campaign->createScheduledLogs();

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', __('messages.campaign_updated', ['count' => $campaign->total_recipients]));
    }

    /**
     * Delete a campaign and its pending logs.
     */
    public function destroy(Campaign $campaign)
    {
        $campaign->logs()->whereIn('status', ['pending', 'paused'])->delete();
        $campaign->delete();

        return redirect()->route('campaigns.index')
            ->with('success', __('messages.campaign_deleted'));
    }

    /**
     * Generate a follow-up email using AI, based on the original campaign email.
     */
    public function generateFollowUpEmail(Request $request, Campaign $campaign)
    {
        $originalSubject = $campaign->getSubject();
        $originalBody = $campaign->getBody();

        if (empty($originalSubject) || empty($originalBody)) {
            return response()->json([
                'success' => false,
                'error' => __('campaign.error_no_original'),
            ]);
        }

        $followUpNumber = $campaign->followUps()->count() + 1;

        try {
            $geminiService = app(\App\Services\GeminiService::class);
            $result = $geminiService->generateFollowUp($originalSubject, $originalBody, $followUpNumber);

            if (!empty($result['success']) && !empty($result['data'])) {
                return response()->json([
                    'success' => true,
                    // 'subject' => $result['data']['subject'] ?? '',
                    'subject' => 'Re: ' . $originalSubject ?? '',
                    'body' => $result['data']['body'] ?? '',
                    'followup_number' => $followUpNumber,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? __('campaign.error_generation_failed'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => __('messages.error_label') . $e->getMessage(),
            ]);
        }
    }
}
