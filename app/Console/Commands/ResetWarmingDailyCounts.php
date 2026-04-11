<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WarmingAccount;

class ResetWarmingDailyCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'warming:reset-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the daily email sent counts for all active warming accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Resetting daily sending counts for all warming accounts...');

        $updated = WarmingAccount::query()->update(['daily_sent_count' => 0]);

        $this->info("Successfully reset counts for {$updated} accounts.");
        
        return Command::SUCCESS;
    }
}
