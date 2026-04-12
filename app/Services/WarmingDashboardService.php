<?php

namespace App\Services;

use App\Models\WarmingAccount;
use App\Models\WarmingTemplate;
use App\Models\WarmingStrategy;
use App\Models\WarmingLog;
use Illuminate\Support\Facades\Log;

class WarmingDashboardService
{
    /**
     * Helper for verbose development logging.
     */
    protected function devLog(string $message, array $context = []): void
    {
        if (app()->environment() !== 'production') {
            Log::debug('[DashboardService] ' . $message, $context);
        }
    }

    public function getDashboardData(): array
    {
        $this->devLog('Gathering dashboard statistics...');
        
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

        $this->devLog('Dashboard data gathered successfully.');

        return compact('accounts', 'strategy', 'stats', 'recentLogs', 'botStatus', 'savedRecipients');
    }
}
