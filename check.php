<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$logs = \App\Models\WarmingLog::where('warming_account_id', 2)
    ->whereDate('scheduled_at', today())
    ->get();

foreach ($logs as $log) {
    echo "Log ID: {$log->id} | Status: {$log->status} | Scheduled: {$log->scheduled_at} | Sent: {$log->sent_at}\n";
}
