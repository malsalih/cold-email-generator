<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    public function __construct()
    {
        // $this->apiKey = env('GEMINI_API_KEY', ''); // Can fallback to direct env if config is cached incorrectly, but config is safer in Laravel
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->model = config('services.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash'));
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    }

    // Deprecated generateTargetEmails removed as we use user input target emails now.

    /**
     * Build the comprehensive anti-spam system prompt.
     */
    public function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert Domain Name Broker and cold email copywriter. You specialize in selling premium domain names to businesses that currently have inferior, confusing, or non-matching domain names.

## YOUR ABSOLUTE RULES — FOLLOW EVERY SINGLE ONE:

### SPAM AVOIDANCE (CRITICAL):
1. NEVER use these spam trigger words or phrases: "Free", "Act Now", "Limited Time", "100% Guarantee", "No Obligation", "Click Here", "Buy Now", "Order Now", "Don't Miss", "Exclusive Deal", "Special Offer", "Congratulations", "Winner", "Cash", "$$$", "Earn Money", "Make Money", "Double Your", "Risk-Free", "No Cost", "Urgent", "Immediately", "Call Now", "Apply Now", "Sign Up Free", "No Strings Attached", "Once in a Lifetime", "As Seen On", "Miracle", "Revolutionary".
2. NEVER use ALL CAPS for emphasis.
3. NEVER use excessive exclamation marks (max 1 in the entire email).
4. NEVER use aggressive or pushy sales language. Avoid making it sound like a "clearance sale". Focus on the strategic value of the asset.
5. NEVER include fake urgency or artificial scarcity.
6. NEVER start the subject line with "Re:" or "Fwd:" deceptively.

### SUBJECT LINE RULES:
1. Keep it between 3-6 words maximum.
2. Make it specific and relevant to their branding or domain.
3. Write it as a natural, curious, human-written subject.
4. Lowercase is preferred over Title Case.
5. No emojis.

### EMAIL BODY RULES:
1. You are writing plain-text cold emails. DO NOT use formatting, bolding, italics, or excessive line breaks.
2. The subject line MUST be natural, human-like, and between 4-8 words. Never use Clickbait or excessive urgency.
3. The email body must be extremely concise (strictly under 100 words).
4. No greetings like "Dear Sir/Madam." Start with a direct, conversational opener.
5. Provide a soft, low-friction Call to Action (e.g., "Open to a quick chat?") instead of asking for a meeting.
6. AVOID SPAM TRIGGER WORDS AT ALL COSTS. Do not use phrases like "guaranteed," "free," "act now," "limited time," or "buy."
7. BYPASS RETVec AI FILTERS: Use completely natural language. Do NOT use ALL CAPS. Do NOT use excessive punctuation (like !!! or ???). Do not try to disguise words with homoglyphs.
8. Focus the pitch on the strategic value of the "Owned Domain" being offered.
9. EXACTLY ONE LINK PERMITTED: If you must reference a link, keep it to a maximum of 1 full, clean URL. Do not use placeholders that look like broken merge tags (e.g., Avoid `[FirstName]`). 

### OUTPUT FORMAT:
You must respond with ONLY a valid JSON array of objects in exactly this format, with no additional text before or after:
[
  {
    "target_email": "ceo@example.com",
    "subject": "your subject line here",
    "body": "your full email body here including sign-off"
  },
  {
    "target_email": "marketing@example.com",
    "subject": "your subject line here",
    "body": "your full email body here including sign-off"
  }
]
PROMPT;
    }

    /**
     * Build the user prompt with context.
     */
    public function buildUserPrompt(array $params): string
    {
        $ownedDomain = $params['owned_domain'];
        $targetWebsite = $params['target_website'] ?: 'Unknown/General Prospect';
        $instructions = $params['instructions'];
        $tone = $params['tone'] ?? 'professional';
        $targetEmails = $params['target_emails'] ?? [];
        $maxEmails = $params['max_emails'] ?? count($targetEmails);

        $emailList = implode(', ', array_column($targetEmails, 'email'));

        return <<<PROMPT
Generate distinct, persuasive cold emails for the following domain sales opportunity:

**Domain Being Sold (Our Asset):** {$ownedDomain}

**Target Emails Pool:** {$emailList}
**Target's Current Website:** {$targetWebsite}

**Desired Tone:** {$tone}
**Specific Instructions / Pitch Angle:** {$instructions}

Remember:
- You MUST generate EXACTLY {$maxEmails} distinct email drafts. Do not generate more or less.
- For each draft, randomly select and assign one of the target emails from the 'Target Emails Pool' to the `target_email` field.
- COMPLETELY VARY AND SCRAMBLE your sentence structures, openers, and sign-offs across the different drafts. They must not look like identical templates, to avoid spam filters.
- If Target's Current Website is known, contrast it with the Domain Being Sold. Highlight the upgrade in brand equity.
- The email should be concise and focus heavily on the value of acquiring the exact match domain "{$ownedDomain}".
- Return ONLY the JSON array of {$maxEmails} objects.
PROMPT;
    }

    /**
     * Models to try in order if the primary model is overloaded.
     */
    protected array $fallbackModels = [
        'gemini-2.5-flash-lite',
        'gemini-2.5-pro',
    ];

    /**
     * Call the Gemini API and generate the email.
     * Includes automatic retry with exponential backoff and model fallback.
     */
    public function generateEmail(array $params): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($params);

        // Build the list of models to try: primary first, then fallbacks
        $modelsToTry = array_unique(array_merge([$this->model], $this->fallbackModels));

        $lastException = null;

        foreach ($modelsToTry as $currentModel) {
            try {
                $result = $this->callGeminiApi($currentModel, $systemPrompt, $userPrompt);
                return $result;
            } catch (\RuntimeException $e) {
                $lastException = $e;
                // Only fallback on 429 (rate limit) or 503 (overloaded) errors
                if (str_contains($e->getMessage(), '429') || str_contains($e->getMessage(), '503')) {
                    Log::warning("Model {$currentModel} unavailable, trying next fallback...", [
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
                // For other errors, don't try fallbacks
                throw $e;
            }
        }

        // All models failed
        throw $lastException ?? new \RuntimeException('All Gemini models are currently unavailable. Please try again later.');
    }

    /**
     * Make the actual API call to Gemini with retry logic.
     */
    protected function callGeminiApi(string $model, string $systemPrompt, string $userPrompt): array
    {
        $maxRetries = 3;
        $lastException = null;

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topP' => 0.9,
                'topK' => 40,
                'maxOutputTokens' => 1024,
                'responseMimeType' => 'application/json',
            ],
        ];

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $startTime = microtime(true);
            $url = "{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}";

            try {
                $response = Http::timeout(30)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                $endTime = microtime(true);
                $generationTimeMs = round(($endTime - $startTime) * 1000, 2);

                if ($response->successful()) {
                    $data = $response->json();

                    // Extract the generated text
                    $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $tokensUsed = $data['usageMetadata']['totalTokenCount'] ?? null;

                    // Parse the JSON response
                    $parsed = $this->parseGeneratedEmail($generatedText);

                    // Send AI output to the local Python ML model for classification & correction
                    $parsed = $this->correctSpamViaML($parsed);

                    return [
                        'variants' => $parsed,
                        'system_prompt' => $systemPrompt,
                        'full_prompt' => $userPrompt,
                        'model' => $model,
                        'tokens_used' => $tokensUsed,
                        'generation_time_ms' => $generationTimeMs,
                    ];
                }

                // Handle error responses
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Unknown API error';
                $statusCode = $response->status();

                // Retry on rate limits, overhead, or spam rejection
                if (in_array($statusCode, [429, 503]) && $attempt < $maxRetries) {
                    $waitSeconds = pow(2, $attempt); // 2s, 4s exponential backoff
                    Log::warning("Gemini API returned {$statusCode}, retrying in {$waitSeconds}s (attempt {$attempt}/{$maxRetries})", [
                        'model' => $model,
                        'error' => $errorMessage,
                    ]);
                    sleep($waitSeconds);
                    continue;
                }

                Log::error('Gemini API Error', [
                    'status' => $statusCode,
                    'error' => $errorMessage,
                    'model' => $model,
                    'attempt' => $attempt,
                ]);

                $lastException = new \RuntimeException("Gemini API Error ({$statusCode}): {$errorMessage}");

            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                Log::error('Gemini API Connection Error', ['message' => $e->getMessage(), 'attempt' => $attempt]);
                $lastException = new \RuntimeException('Could not connect to the Gemini API. Please check your internet connection and try again.');

                if ($attempt < $maxRetries) {
                    sleep(pow(2, $attempt));
                    continue;
                }
            }
        }

        throw $lastException ?? new \RuntimeException('Failed to generate email after multiple attempts.');
    }

    /**
     * Parse the generated email from Gemini's response.
     */
    protected function parseGeneratedEmail(string $text): array
    {
        // Clean up: remove markdown code fences if present
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/i', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Check if it's a numeric array of objects
            if (isset($decoded[0]['subject'])) {
                return $decoded;
            }
            
            // In case it returned a single object despite asking for an array
            if (isset($decoded['subject']) && isset($decoded['body'])) {
                return [
                    [
                        'target_email' => 'General / Bulk',
                        'subject' => trim($decoded['subject']),
                        'body' => trim($decoded['body']),
                    ]
                ];
            }
        }

        // Fallback: try to extract subject and body manually
        Log::warning('Failed to parse Gemini JSON array response, attempting fallback', ['raw' => $text]);

        $subject = 'Regarding your domain strategy';
        $body = $text;

        // Try regex extraction (just grab one for fallback)
        if (preg_match('/"subject"\s*:\s*"([^"]+)"/i', $text, $subjectMatch)) {
            $subject = $subjectMatch[1];
        }
        if (preg_match('/"body"\s*:\s*"([\s\S]+?)"\s*}/i', $text, $bodyMatch)) {
            $body = $bodyMatch[1];
            $body = str_replace('\n', "\n", $body);
        }

        return [
            [
                'target_email' => 'General / Bulk',
                'subject' => $subject,
                'body' => $body,
            ]
        ];
    }

    /**
     * Sends generated variants to the local Python ML classifier.
     * If spam is detected, the Python service corrects the text locally
     * and returns both original + corrected versions with spam metadata.
     */
    protected function correctSpamViaML(array $variants): array
    {
        try {
            $response = Http::timeout(10)->post('http://127.0.0.1:5000/correct', [
                'variants' => $variants,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $correctedVariants = $json['variants'] ?? $variants;
                $totalCorrections = $json['total_corrections'] ?? 0;

                if ($totalCorrections > 0) {
                    Log::info("ML Spam Classifier corrected {$totalCorrections} variant(s) locally.");
                }

                // Preserve all metadata from the Python response for the UI
                return array_map(function ($v) {
                    return [
                        'target_email' => $v['target_email'] ?? 'General / Bulk',
                        'subject' => $v['subject'] ?? '',
                        'body' => $v['body'] ?? '',
                        'original_subject' => $v['original_subject'] ?? $v['subject'] ?? '',
                        'original_body' => $v['original_body'] ?? $v['body'] ?? '',
                        'was_spam' => $v['was_spam'] ?? false,
                        'spam_probability' => $v['spam_probability'] ?? 0,
                        'corrected_spam_probability' => $v['corrected_spam_probability'] ?? $v['spam_probability'] ?? 0,
                    ];
                }, $correctedVariants);
            }

            Log::warning('ML Spam Classifier returned non-200 response, passing text through uncorrected.');
        } catch (\Exception $e) {
            Log::error('ML Spam Classifier microservice offline: ' . $e->getMessage());
        }

        // Fallback: return variants with default metadata
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
