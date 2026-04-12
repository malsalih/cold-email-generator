<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpamClassifierService
{
    /**
     * Helper for verbose development logging.
     */
    protected function devLog(string $message, array $context = []): void
    {
        if (app()->environment() !== 'production') {
            Log::debug('[SpamClassifier] ' . $message, $context);
        }
    }

    /**
     * Sends generated variants to the local ML classifier (port 5050).
     */
    public function correctSpamViaML(array $variants): array
    {
        $this->devLog('Checking spam via local ML logic...', ['count' => count($variants)]);

        try {
            $startTime = microtime(true);
            $response = Http::timeout(10)->post('http://127.0.0.1:5050/correct', [
                'variants' => $variants,
            ]);

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->devLog("Python ML returned in {$duration}ms with status " . $response->status());

            if ($response->successful()) {
                $json = $response->json();
                $correctedVariants = $json['variants'] ?? $variants;
                $totalCorrections = $json['total_corrections'] ?? 0;

                if ($totalCorrections > 0) {
                    Log::info("ML Spam Classifier corrected {$totalCorrections} variant(s) locally.");
                }

                // Preserve metadata but strictly restore target_emails from PHP layer to avoid Python serialization drop
                $mapped = [];
                foreach ($correctedVariants as $i => $v) {
                    $originalVariant = $variants[$i] ?? [];
                    $mapped[] = [
                        'target_email' => $v['target_email'] ?? $originalVariant['target_email'] ?? 'General / Bulk',
                        'target_emails' => $originalVariant['target_emails'] ?? [$v['target_email'] ?? 'General / Bulk'],
                        'subject' => $v['subject'] ?? '',
                        'body' => $v['body'] ?? '',
                        'original_subject' => $v['original_subject'] ?? $v['subject'] ?? '',
                        'original_body' => $v['original_body'] ?? $v['body'] ?? '',
                        'was_spam' => $v['was_spam'] ?? false,
                        'spam_probability' => $v['spam_probability'] ?? 0,
                        'corrected_spam_probability' => $v['corrected_spam_probability'] ?? $v['spam_probability'] ?? 0,
                    ];
                }
                return $mapped;
            }

            Log::warning('ML Spam Classifier returned non-200. Passing text through uncorrected.');
        } catch (\Exception $e) {
            Log::error('ML Spam Classifier microservice offline: ' . $e->getMessage());
        }

        $this->devLog('Returning fallback (uncorrected) variants.');
        return array_map(function ($v) {
            return array_merge($v, [
                'original_subject' => $v['subject'] ?? '',
                'original_body' => $v['body'] ?? '',
                'was_spam' => false,
                'spam_probability' => 0,
            ]);
        }, $variants);
    }
}
