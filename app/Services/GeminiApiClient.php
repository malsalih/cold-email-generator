<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiApiClient
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected array $fallbackModels = [
        'gemini-3-flash-preview',
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite',
    ];

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY', 'gemini-3.1-flash-lite-preview'));
        $this->model = config('services.gemini.model', env('GEMINI_MODEL', ));
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    }

    /**
     * Helper for verbose development logging.
     */
    protected function devLog(string $message, array $context = []): void
    {
        if (app()->environment() !== 'production') {
            Log::debug('[GeminiAPI] ' . $message, $context);
        }
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Sends the text to Gemini with strict fallback logic on 429/503.
     */
    public function sendRequest(string $systemPrompt, string $userPrompt, array $generationConfig = []): array
    {
        if (empty($this->apiKey)) {
            $this->devLog('API Key missing.');
            throw new \RuntimeException('Gemini API key is not configured.');
        }

        $this->devLog('Initiating sendRequest with system & user prompt.');

        $defaultConfig = [
            'temperature' => 0.7,
            'topK' => 40,
            'topP' => 0.95,
            'maxOutputTokens' => 8192,
            'responseMimeType' => 'application/json',
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => [
                [
                    'parts' => [['text' => $userPrompt]]
                ]
            ],
            'generationConfig' => array_merge($defaultConfig, $generationConfig),
            'safetySettings' => [
                ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_LOW_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_LOW_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_LOW_AND_ABOVE'],
                ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_LOW_AND_ABOVE']
            ]
        ];

        $modelsToTry = array_unique(array_merge([$this->model], $this->fallbackModels));
        $maxRetries = 2;
        $lastError = 'Unknown error';

        foreach ($modelsToTry as $currentModel) {
            $url = "{$this->baseUrl}/{$currentModel}:generateContent?key={$this->apiKey}";
            $this->devLog("Trying model: {$currentModel}");

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $startTime = microtime(true);
                    $response = Http::timeout(30)->post($url, $payload);
                    $duration = round((microtime(true) - $startTime) * 1000, 2);

                    if ($response->failed()) {
                        $status = $response->status();
                        $lastError = "API Error. Model: {$currentModel}, Status: {$status}";
                        $this->devLog("HTTP Failed on {$currentModel}. Status: {$status}. Duration: {$duration}ms");
                        
                        if (in_array($status, [429, 503])) {
                            Log::warning("Gemini API Error {$status} on {$currentModel}. Switching to next fallback model immediately...");
                            break; // Break the attempt loop, go to the next model in foreach
                        }
                        
                        Log::error("Gemini API Error ({$currentModel}): " . $response->body());
                        
                        if ($attempt < $maxRetries) {
                            $this->devLog("Backing off for attempt " . ($attempt + 1));
                            sleep(pow(2, $attempt));
                            continue;
                        }
                        return ['success' => false, 'error' => $lastError];
                    }

                    $this->devLog("API call successful. Duration: {$duration}ms");
                    $json = $response->json();
                    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $tokensUsed = $json['usageMetadata']['totalTokenCount'] ?? null;

                    if (empty($text)) {
                        $lastError = 'API returned empty response.';
                        continue;
                    }

                    $decoded = json_decode($text, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error("Gemini JSON Parse Error ({$currentModel})", ['text' => $text, 'error' => json_last_error_msg()]);
                        $lastError = 'Failed to parse AI response.';
                        continue;
                    }

                    return ['success' => true, 'data' => $decoded, 'model' => $currentModel, 'tokens_used' => $tokensUsed, 'generation_time_ms' => $duration];

                } catch (\Exception $e) {
                    $lastError = $e->getMessage();
                    Log::error("Gemini Exception ({$currentModel}, Attempt {$attempt}): " . $lastError);
                    if ($attempt < $maxRetries) {
                        sleep(pow(2, $attempt));
                        continue;
                    }
                }
            }
        }
        
        $this->devLog('All models and retries failed.');
        return ['success' => false, 'error' => $lastError];
    }
}
