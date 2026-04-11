<?php

namespace App\Http\Controllers;

use App\Models\WarmingAccount;
use App\Models\WarmingTemplate;
use App\Models\WarmingStrategy;
use App\Models\WarmingLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

/**
 * WarmingController — Manages the email warming dashboard, accounts,
 * templates, strategies, settings, daily rounds, and bot lifecycle.
 *
 * Key features:
 * - Dashboard with live bot monitoring and real-time stats
 * - Quick Start warming (manual recipient input)
 * - Daily Round warming (strategy-based automated scheduling)
 * - Saved recipients management
 * - Bot start/stop control with safety guards
 * - Template generation via local ML service (100% offline)
 */
class WarmingController extends Controller
{
    /**
     * Warming Dashboard — Shows stats, account status, bot monitor,
     * recent activity, strategy info, and quick start form.
     */
    public function dashboard()
    {
        $accounts = WarmingAccount::all();
        $strategy = WarmingStrategy::getDefault();

        $stats = [
            'total_accounts' => $accounts->count(),
            'active_accounts' => $accounts->where('status', 'active')->count(),
            'sent_today' => WarmingLog::sent()->today()->count(),
            'sent_this_week' => WarmingLog::sent()->thisWeek()->count(),
            'total_sent' => WarmingLog::sent()->count(),
            'failed_today' => WarmingLog::failed()->today()->count(),
            'templates_count' => WarmingTemplate::active()->count(),
            'unused_templates' => WarmingTemplate::active()->where('times_used', 0)->count(),
            'pending_jobs' => WarmingLog::where('status', 'pending')->count(),
            'processing_jobs' => WarmingLog::where('status', 'processing')->count(),
        ];

        $recentLogs = WarmingLog::with(['account', 'template'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $botStatus = \App\Models\BotLog::getLatestStatus();
        $savedRecipients = \App\Models\WarmingRecipient::orderBy('group')->orderBy('email')->get();

        return view('warming.dashboard', compact('accounts', 'strategy', 'stats', 'recentLogs', 'botStatus', 'savedRecipients'));
    }

    // ─── ACCOUNTS ────────────────────────────────────────────────

    public function accounts()
    {
        $accounts = WarmingAccount::orderBy('created_at', 'desc')->get();
        return view('warming.accounts', compact('accounts'));
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:warming_accounts,email',
            'display_name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
        ]);

        $domain = $validated['domain'] ?? 'eagnt.com';
        // Extract domain from email if not provided
        if (empty($request->input('domain'))) {
            $domain = explode('@', $validated['email'])[1] ?? 'eagnt.com';
        }

        WarmingAccount::create([
            'email' => $validated['email'],
            'display_name' => $validated['display_name'],
            'domain' => $domain,
            'status' => 'pending',
        ]);

        return redirect()->route('warming.accounts')
            ->with('success', 'تم إضافة الحساب بنجاح. سجّل الدخول لبدء التسخين.');
    }

    public function deleteAccount(WarmingAccount $account)
    {
        $account->delete();
        return redirect()->route('warming.accounts')
            ->with('success', 'تم حذف الحساب.');
    }

    public function toggleAccount(WarmingAccount $account)
    {
        $newStatus = $account->status === 'active' ? 'paused' : 'active';

        if ($newStatus === 'active' && !$account->is_logged_in) {
            return back()->with('error', 'يجب تسجيل الدخول أولاً قبل تفعيل الحساب.');
        }

        $account->update([
            'status' => $newStatus,
            'warming_started_at' => $account->warming_started_at ?? now(),
            'warming_day' => $account->warming_day ?: 1,
        ]);

        // Update daily limit based on strategy
        if ($newStatus === 'active') {
            $strategy = WarmingStrategy::getDefault();
            $account->update([
                'daily_limit' => $strategy->getDailyLimitForDay($account->warming_day),
            ]);
        }

        return back()->with('success', $newStatus === 'active' ? 'تم تفعيل الحساب.' : 'تم إيقاف الحساب مؤقتاً.');
    }

    /**
     * Launch Puppeteer login session for an account.
     * Returns JSON (called via fetch from the UI).
     */
    public function loginAccount(WarmingAccount $account)
    {
        $sessionDir = $account->getSessionPath();
        $botPath = base_path('warming_bot/login.js');
        $nodePath = 'node'; // assumes node is in PATH

        // Build the command
        $command = "{$nodePath} \"{$botPath}\" --account-id={$account->id} --session-dir=\"{$sessionDir}\"";

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // On Windows: use 'start' WITHOUT /B to open a visible CMD window
            // The title "" is required by 'start' when the command contains quotes
            pclose(popen("start \"ColdForge Login\" {$command}", 'r'));
        } else {
            // On Linux/Mac: run in background
            exec("{$command} > /dev/null 2>&1 &");
        }

        // Return JSON for the fetch-based button
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'تم فتح متصفح Zoho — سجّل الدخول فيه.',
            ]);
        }

        return back()->with('success', 'تم فتح نافذة المتصفح. سجّل الدخول إلى Zoho Mail. سيتم حفظ الجلسة تلقائياً.');
    }

    /**
     * Advance to the next warming day directly without waiting for cron.
     */
    public function nextDay(WarmingAccount $account)
    {
        $account->update([
            'current_day_sent' => 0,
            'warming_day' => $account->warming_day + 1,
        ]);
        
        // Update daily limit based on strategy
        $strategy = WarmingStrategy::getDefault();
        $account->update([
            'daily_limit' => $strategy->getDailyLimitForDay($account->warming_day),
        ]);

        return back()->with('success', "تم بدء يوم التسخين {$account->warming_day} بنجاح!");
    }

    // ─── TEMPLATES ───────────────────────────────────────────────

    public function templates()
    {
        $templates = WarmingTemplate::orderBy('created_at', 'desc')->get();
        $categories = WarmingTemplate::getCategories();
        return view('warming.templates', compact('templates', 'categories'));
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(WarmingTemplate::getCategories())),
            'subject' => 'required|string|max:500',
            'body' => 'required|string|min:10',
        ]);

        // Auto-detect variables from the template
        preg_match_all('/\{(\w+)\}/', $validated['subject'] . ' ' . $validated['body'], $matches);
        $variables = array_unique($matches[0] ?? []);

        WarmingTemplate::create([
            ...$validated,
            'variables' => array_values($variables),
        ]);

        return redirect()->route('warming.templates')
            ->with('success', 'تم إنشاء القالب بنجاح.');
    }

    public function updateTemplate(Request $request, WarmingTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(WarmingTemplate::getCategories())),
            'subject' => 'required|string|max:500',
            'body' => 'required|string|min:10',
            'is_active' => 'nullable|boolean',
        ]);

        preg_match_all('/\{(\w+)\}/', $validated['subject'] . ' ' . $validated['body'], $matches);
        $variables = array_unique($matches[0] ?? []);

        $template->update([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
            'variables' => array_values($variables),
        ]);

        return redirect()->route('warming.templates')
            ->with('success', 'تم تحديث القالب.');
    }

    public function deleteTemplate(WarmingTemplate $template)
    {
        $template->delete();
        return redirect()->route('warming.templates')
            ->with('success', 'تم حذف القالب.');
    }

    // ─── STRATEGIES ──────────────────────────────────────────────

    public function strategies()
    {
        $strategies = WarmingStrategy::all();

        if ($strategies->isEmpty()) {
            WarmingStrategy::createDefault();
            $strategies = WarmingStrategy::all();
        }

        return view('warming.strategies', compact('strategies'));
    }

    // ─── LOGS ────────────────────────────────────────────────────

    public function logs(Request $request)
    {
        $query = WarmingLog::with(['account', 'template'])->orderBy('created_at', 'desc');

        if ($request->filled('account_id')) {
            $query->where('warming_account_id', $request->input('account_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('source')) {
            $query->where('source_type', $request->input('source'));
        }

        $logs = $query->paginate(20);
        $accounts = WarmingAccount::all();

        return view('warming.logs', compact('logs', 'accounts'));
    }

    // ─── SEND TEST EMAIL ─────────────────────────────────────────

    public function sendTest(Request $request, WarmingAccount $account)
    {
        $validated = $request->validate([
            'recipient' => 'required|email',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
        ]);

        // Create a pending log
        $log = WarmingLog::create([
            'warming_account_id' => $account->id,
            'recipient_email' => $validated['recipient'],
            'subject_sent' => $validated['subject'],
            'body_sent' => $validated['body'],
            'status' => 'pending',
            'source_type' => 'warming',
        ]);

        return back()->with('success', "تم إضافة إيميل تجريبي للإرسال. شغّل البوت لإرساله. (Log #{$log->id})");
    }

    // ─── SETTINGS & QUICK START ──────────────────────────────────

    public function settings()
    {
        $sendMode = \App\Models\WarmingSetting::getSendMode();
        $accounts = WarmingAccount::ready()->get();

        return view('warming.settings', compact('sendMode', 'accounts'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'send_mode' => 'required|in:auto,manual_send,full_manual',
        ]);

        \App\Models\WarmingSetting::setValue('send_mode', $validated['send_mode']);

        return back()->with('success', 'تم حفظ الإعدادات بنجاح.');
    }

    public function quickStartWarming(Request $request)
    {
        set_time_limit(0); // Prevent PHP timeout if we need to generate many templates

        $validated = $request->validate([
            'warming_account_id' => 'required|exists:warming_accounts,id',
            'target_emails' => 'required|string',
            'delay_minutes' => 'required|integer|min:0|max:1440',
        ]);

        $account = WarmingAccount::findOrFail($validated['warming_account_id']);
        
        // Parse emails
        $recipients = array_values(array_unique(array_filter(
            preg_split('/[\s,;]+/', $validated['target_emails']),
            fn($e) => filter_var(trim($e), FILTER_VALIDATE_EMAIL)
        )));

        if (empty($recipients)) {
            return back()->with('error', 'لم يتم العثور على إيميلات صالحة.');
        }

        // Filter out recipients that already have pending/processing jobs
        $existingPending = \App\Models\WarmingLog::where('warming_account_id', $account->id)
            ->whereIn('status', ['pending', 'processing'])
            ->whereIn('recipient_email', $recipients)
            ->pluck('recipient_email')
            ->toArray();

        $recipients = array_values(array_diff($recipients, $existingPending));
        
        if (empty($recipients)) {
            return back()->with('error', 'جميع الإيميلات المدخلة موجودة بالفعل في قائمة الانتظار.');
        }

        $skippedCount = count($existingPending);

        $neededTemplatesCount = count($recipients);

        // Fetch fresh templates that have never been used
        $availableTemplates = \App\Models\WarmingTemplate::active()->where('times_used', 0)->get();

        // If we don't have enough unused templates, auto-generate more via the second ML model
        if ($availableTemplates->count() < $neededTemplatesCount) {
            $deficit = $neededTemplatesCount - $availableTemplates->count();
            $generateCount = max(10, $deficit); // Generate at least 10 each time
            
            \Illuminate\Support\Facades\Artisan::call('warming:generate-templates', ['count' => $generateCount]);
            
            // Reload available templates
            $availableTemplates = \App\Models\WarmingTemplate::active()->where('times_used', 0)->get();
        }

        // Failsafe if API failed to generate enough
        if ($availableTemplates->count() < $neededTemplatesCount) {
            return back()->with('error', 'فشل نموذج الذكاء الاصطناعي في الاستجابة وتوليد قوالب كافية للمستلمين (نقص بالقوالب الحصرية). يرجى المحاولة مرة أخرى.');
        }

        $now = now();
        $delay = (int) $validated['delay_minutes'];
        
        foreach ($recipients as $index => $toEmail) {
            // Pick a random template and remove it from the pool so it's not reused in this loop
            $randomKey = $availableTemplates->keys()->random();
            $template = $availableTemplates->pull($randomKey);
            
            // Enforce usage rule: A template is used for exactly ONE time.
            $template->markUsed(); // Increments times_used to 1

            $scheduledAt = $now->copy()->addMinutes($delay * $index);

            \App\Models\WarmingLog::create([
                'warming_account_id' => $account->id,
                'warming_template_id' => $template->id,
                'recipient_email' => trim($toEmail),
                'subject_sent' => $template->renderSubject(),
                'body_sent' => $template->renderBody(),
                'status' => 'pending',
                'source_type' => 'warming',
                'scheduled_at' => $scheduledAt,
            ]);
        }

        // Start bot - ALWAYS use manual_send during development
        // The bot will fill everything but wait for user to click Send
        $botPath = base_path('warming_bot/bot.js');
        $sendMode = \App\Models\WarmingSetting::getSendMode();

        // Always use at least manual_send to prevent accidental auto-sends
        $flags = "--loop --manual --account={$account->id}";
        if ($sendMode === 'full_manual') {
            $flags = "--loop --manual --skip-fill --account={$account->id}";
        }

        // Stop any existing bot first
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('taskkill /F /FI "WINDOWTITLE eq ColdForge Warming Bot*" 2>nul');
        } else {
            exec("pkill -f 'node.*bot.js' 2>/dev/null");
        }
        sleep(1);

        $command = "node \"{$botPath}\" {$flags}";
        $title = "ColdForge Warming Bot - {$account->email}";

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start \"{$title}\" cmd /K \"{$command}\"", 'r'));
        } else {
            exec("{$command} 2>&1 | tee -a warming_bot.log &");
        }

        $msg = "تم جدولة " . count($recipients) . " رسالة تسخين لحساب {$account->email}. البوت يعمل في نافذة Terminal (وضع يدوي).";
        if ($skippedCount > 0) {
            $msg .= " تم تخطي {$skippedCount} إيميل مكرر.";
        }
        return back()->with('success', $msg);
    }

    // ─── RETRY ──────────────────────────────────────────────────

    public function retryLog(WarmingLog $log)
    {
        if ($log->status === 'failed') {
            $log->update([
                'status' => 'pending',
                'error_message' => null,
                'sent_at' => null,
            ]);

            // Update campaign counts if applicable
            if ($log->campaign) {
                $log->campaign->refreshCounts();
            }
        }

        return back()->with('success', "تم إعادة المهمة #{$log->id} إلى قائمة الانتظار.");
    }

    // ─── DAILY ROUND ────────────────────────────────────────────

    public function startDailyRound(WarmingAccount $account)
    {
        set_time_limit(0);

        // Check account is ready
        if ($account->status !== 'active') {
            return back()->with('error', 'الحساب غير نشط.');
        }
        if (!$account->is_logged_in) {
            return back()->with('error', 'يجب تسجيل الدخول للحساب أولاً.');
        }

        // Get strategy and today's target
        $strategy = WarmingStrategy::getDefault();
        $todayTarget = $strategy->getDailyLimitForDay($account->warming_day);
        $alreadySent = $account->current_day_sent;
        $remaining = max(0, $todayTarget - $alreadySent);

        if ($remaining === 0) {
            return back()->with('error', "الحساب أكمل جولة اليوم {$account->warming_day} بالفعل ({$alreadySent}/{$todayTarget}). سيتم الانتقال لليوم التالي غداً.");
        }

        // Get saved recipients
        $savedRecipients = \App\Models\WarmingRecipient::pluck('email')->toArray();
        if (empty($savedRecipients)) {
            return back()->with('error', 'لا يوجد مستلمين محفوظين. أضف إيميلات في قسم "المستلمين المحفوظين" أولاً.');
        }

        // Filter out recipients with pending/processing jobs
        $busyRecipients = WarmingLog::where('warming_account_id', $account->id)
            ->whereIn('status', ['pending', 'processing'])
            ->pluck('recipient_email')
            ->toArray();
        $availableRecipients = array_values(array_diff($savedRecipients, $busyRecipients));

        if (empty($availableRecipients)) {
            return back()->with('error', 'جميع المستلمين المحفوظين لديهم مهام معلقة بالفعل.');
        }

        // Shuffle and pick recipients randomly (cycle through if not enough)
        shuffle($availableRecipients);
        $selectedRecipients = [];
        for ($i = 0; $i < $remaining; $i++) {
            $selectedRecipients[] = $availableRecipients[$i % count($availableRecipients)];
        }

        // Generate templates if needed
        $availableTemplates = WarmingTemplate::active()->where('times_used', 0)->get();
        if ($availableTemplates->count() < count($selectedRecipients)) {
            $needed = count($selectedRecipients) - $availableTemplates->count();
            \Artisan::call('warming:generate-templates', ['count' => $needed]);
            $availableTemplates = WarmingTemplate::active()->where('times_used', 0)->get();
        }

        if ($availableTemplates->isEmpty()) {
            return back()->with('error', 'فشل توليد قوالب كافية. تحقق من خدمة ML.');
        }

        // Schedule the jobs with delays from strategy
        $templates = $availableTemplates->shuffle();
        $scheduled = 0;

        foreach ($selectedRecipients as $i => $recipientEmail) {
            $template = $templates[$i % $templates->count()];
            $scheduleTime = now()->addMinutes($i * rand($strategy->min_delay_minutes, $strategy->max_delay_minutes));

            WarmingLog::create([
                'warming_account_id' => $account->id,
                'warming_template_id' => $template->id,
                'recipient_email' => trim($recipientEmail),
                'subject_sent' => $template->renderSubject(),
                'body_sent' => $template->renderBody(),
                'status' => 'pending',
                'source_type' => 'warming',
                'scheduled_at' => $scheduleTime,
            ]);

            $template->increment('times_used');
            $scheduled++;
        }

        // Stop any existing bot first (prevent cross-account session conflicts)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('taskkill /F /FI "WINDOWTITLE eq ColdForge Warming Bot*" 2>nul');
        } else {
            exec("pkill -f 'node.*bot.js' 2>/dev/null");
        }
        sleep(1); // Give it a moment to die

        // Start bot locked to this account only
        $botPath = base_path('warming_bot/bot.js');
        $flags = "--loop --manual --account={$account->id}";
        $command = "node \"{$botPath}\" {$flags}";
        $title = "ColdForge Warming Bot - {$account->email}";
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start \"{$title}\" cmd /K \"{$command}\"", 'r'));
        } else {
            exec("{$command} 2>&1 | tee -a warming_bot.log &");
        }

        return back()->with('success', "🔥 بدأت جولة اليوم {$account->warming_day} — تم جدولة {$scheduled} رسالة من أصل {$todayTarget} مطلوبة. البوت يعمل (وضع يدوي).");
    }

    // ─── BOT CONTROL ────────────────────────────────────────────

    public function startBot()
    {
        $botPath = base_path('warming_bot/bot.js');
        $sendMode = \App\Models\WarmingSetting::getSendMode();

        $flags = '--loop';
        if ($sendMode === 'manual_send') {
            $flags .= ' --manual';
        } elseif ($sendMode === 'full_manual') {
            $flags .= ' --manual --skip-fill';
        }

        $command = "node \"{$botPath}\" {$flags}";

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // /K keeps the window open so user can see all logs
            pclose(popen("start \"ColdForge Warming Bot\" cmd /K \"{$command}\"", 'r'));
        } else {
            exec("{$command} 2>&1 | tee -a warming_bot.log &");
        }

        return back()->with('success', 'تم تشغيل البوت! راقب نافذة Terminal لرؤية كل ما يحدث.');
    }

    public function stopBot()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Kill all ColdForge bot windows (warming + campaign)
            exec('taskkill /F /FI "WINDOWTITLE eq ColdForge*" 2>nul');
        } else {
            exec("pkill -f 'node.*bot.js' 2>/dev/null");
        }

        return back()->with('success', 'تم إيقاف البوت.');
    }

    // ─── SAVED RECIPIENTS ───────────────────────────────────────

    public function saveRecipients(Request $request)
    {
        $validated = $request->validate([
            'emails' => 'required|string',
            'group' => 'nullable|string|max:50',
        ]);

        $group = $validated['group'] ?? 'default';
        $emails = array_unique(array_filter(
            preg_split('/[\s,;]+/', $validated['emails']),
            fn($e) => filter_var(trim($e), FILTER_VALIDATE_EMAIL)
        ));

        $saved = 0;
        foreach ($emails as $email) {
            \App\Models\WarmingRecipient::firstOrCreate(
                ['email' => trim($email)],
                ['group' => $group]
            );
            $saved++;
        }

        return back()->with('success', "تم حفظ {$saved} مستلم.");
    }

    public function deleteRecipient(\App\Models\WarmingRecipient $recipient)
    {
        $recipient->delete();
        return back()->with('success', 'تم حذف المستلم.');
    }
}
