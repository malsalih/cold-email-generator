<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WarmingTemplate;
use Illuminate\Support\Facades\Http;

class GenerateWarmingTemplates extends Command
{
    protected $signature = 'warming:generate-templates {count=10 : The number of templates to generate}';
    protected $description = 'Generate personal warming templates via LOCAL ML text generator and verify them via local spam filter (100% offline, no external APIs).';

    public function handle()
    {
        $targetCount = (int) $this->argument('count');
        $this->info("Starting local pipeline to generate {$targetCount} warming templates...");
        
        $successCount = 0;
        $attempts = 0;
        $maxAttempts = $targetCount * 5;

        while ($successCount < $targetCount && $attempts < $maxAttempts) {
            $attempts++;
            
            try {
                $response = Http::timeout(15)->post('http://127.0.0.1:5050/generate');

                if ($response->failed()) {
                    $this->error("Local ML API Error (Status {$response->status()}). Ensure python ml_service is running.");
                    sleep(1);
                    continue;
                }

                $data = $response->json();
                
                if (!($data['success'] ?? false)) {
                    $this->warn("Attempt {$attempts}: Generator retry - " . ($data['error'] ?? 'unknown'));
                    continue;
                }

                $subject = $data['subject'] ?? '';
                $body = $data['body'] ?? '';
                $spamProb = $data['spam_probability'] ?? 0;

                if (empty($subject) || empty($body)) {
                    continue;
                }

                WarmingTemplate::create([
                    'name' => 'Local Gen #' . rand(1000, 9999),
                    'subject' => $subject,
                    'body' => $body,
                    'is_active' => true,
                ]);

                $successCount++;
                $this->info("[{$successCount}/{$targetCount}] ✓ Saved (spam: {$spamProb}%) — " . substr($subject, 0, 50));

            } catch (\Exception $e) {
                $this->error("Connection failed: " . $e->getMessage());
                break;
            }
        }

        if ($successCount < $targetCount) {
            $this->warn("Generated {$successCount}/{$targetCount} after {$attempts} attempts.");
        } else {
            $this->info("Successfully generated {$successCount} verified templates!");
        }

        return Command::SUCCESS;
    }
}
