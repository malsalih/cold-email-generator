<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$account = \App\Models\WarmingAccount::find(2);
$strategy = $account->strategy;
$logs = \App\Models\WarmingLog::where('account_id', 2)->whereDate('scheduled_at', today())->get();

echo "Account ID: {$account->id}\n";
echo "Active Day: {$account->active_day}\n";
echo "Emails Sent Today (Counter): {$account->emails_sent_today}\n";
echo "Paused: " . ($account->is_paused ? 'Yes' : 'No') . "\n";
echo "---\n";
echo "Strategy Start: {$strategy->start_emails_per_day}\n";
echo "Strategy Increment: {$strategy->daily_increment}\n";
echo "Strategy Max: {$strategy->max_emails_per_day}\n";
$expected_today = min($strategy->max_emails_per_day, $strategy->start_emails_per_day + (($account->active_day - 1) * $strategy->daily_increment));
echo "Expected Emails Today: {$expected_today}\n";
echo "---\n";
echo "Logs Generated Today:\n";
foreach($logs as $log) {
    echo "- Log #{$log->id}: {$log->status} at {$log->scheduled_at} ({$log->source_type})\n";
}
