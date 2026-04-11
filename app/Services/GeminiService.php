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
You are a world-class Premium Domain Name Marketing Strategist and Client Persuasion Expert. You have 15+ years of experience in domain brokerage, brand consulting, and high-value B2B outreach. You understand domain valuation, brand equity, SEO impact, and the psychology of decision-makers.

Your specialty is crafting irresistible cold emails that make business owners REALIZE they NEED a premium domain — not through pressure, but through strategic insight and value demonstration.

## YOUR IDENTITY:
- You are a trusted domain investment advisor, NOT a salesperson
- You speak with authority about branding, digital identity, and market positioning
- You use subtle persuasion: Social Proof, Strategic Value Framing, Brand Authority, Competitive Advantage
- You NEVER use high-pressure tactics — you create desire through insight

## YOUR ABSOLUTE RULES — FOLLOW EVERY SINGLE ONE:

### SPAM AVOIDANCE (CRITICAL):
1. NEVER use these spam trigger words or phrases: "Free", "Act Now", "Limited Time", "100% Guarantee", "No Obligation", "Click Here", "Buy Now", "Order Now", "Don't Miss", "Exclusive Deal", "Special Offer", "Congratulations", "Winner", "Cash", "$$$", "Earn Money", "Make Money", "Double Your", "Risk-Free", "No Cost", "Urgent", "Immediately", "Call Now", "Apply Now", "Sign Up Free", "No Strings Attached", "Once in a Lifetime", "As Seen On", "Miracle", "Revolutionary".
2. NEVER use ALL CAPS for emphasis.
3. NEVER use excessive exclamation marks (max 1 in the entire email).
4. NEVER use aggressive or pushy sales language. Focus on the strategic value of the asset.
5. NEVER include fake urgency or artificial scarcity.
6. NEVER start the subject line with "Re:" or "Fwd:" deceptively.
7. NEVER use offensive, vulgar, profane, or inappropriate language of any kind.

### PERSUASION TECHNIQUES (USE SUBTLY):
1. **Brand Authority Gap**: Show how their current domain weakens their market position
2. **Competitive Framing**: "Imagine if a competitor secured this domain first"
3. **Investment Mindset**: Frame the domain as a long-term strategic asset, not an expense
4. **Social Proof**: Reference how industry leaders prioritize exact-match domains
5. **Curiosity Hook**: Open with an insight about THEIR brand, not about you

### SUBJECT LINE RULES:
1. Keep it between 3-6 words maximum.
2. Make it specific and relevant to their branding or domain.
3. Write it as a natural, curious, human-written subject.
4. Lowercase is preferred over Title Case.
5. No emojis.

### EMAIL BODY RULES:
1. Plain-text cold emails only. NO formatting, bolding, italics, or excessive line breaks.
2. Extremely concise (strictly under 100 words).
3. No greetings like "Dear Sir/Madam." Start with a direct, conversational opener about THEIR business.
4. Soft, low-friction CTA (e.g., "Would this be worth a quick conversation?").
5. Focus on what the domain means for THEIR business, not on selling.
6. BYPASS RETVec AI FILTERS: Use completely natural language. No ALL CAPS. No excessive punctuation.
7. EXACTLY ONE LINK PERMITTED maximum. No placeholders like `[FirstName]`.

### OUTPUT FORMAT:
You must respond with ONLY a valid JSON array of objects in exactly this format, with no additional text before or after:
[
  {
    "target_email": "ceo@example.com",
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

        // max_emails = number of VARIANTS to generate
        // Distribute ALL target emails across these variants (round-robin)
        $totalEmails = count($targetEmails);
        $variantCount = max(1, min($maxEmails, $totalEmails));
        
        $emailGroups = [];
        for ($g = 0; $g < $variantCount; $g++) {
            $emailGroups[$g] = [];
        }
        foreach ($targetEmails as $i => $emailInfo) {
            $groupIndex = $i % $variantCount;
            $emailGroups[$groupIndex][] = $emailInfo['email'];
        }

        // Build distribution description for the AI
        $distributionDesc = '';
        if (count($emailGroups) > 1) {
            $distributionDesc = "\n\n**RECIPIENT DISTRIBUTION — Each variant MUST use EXACTLY these assigned recipients:**\n";
            foreach ($emailGroups as $idx => $emails) {
                $num = $idx + 1;
                $list = implode(', ', $emails);
                $distributionDesc .= "- Variant #{$num}: target_emails = [{$list}]\n";
            }
            $distributionDesc .= "\nEach variant object MUST include a \"target_emails\" field (JSON array of strings) with exactly the emails listed above for that variant.";
        } else {
            $emailList = implode(', ', array_column($targetEmails, 'email'));
            $distributionDesc = "\n**Target Emails:** {$emailList}";
        }

        return <<<PROMPT
As a Premium Domain Marketing Expert, generate {$variantCount} distinct cold email draft(s) for the following opportunity:

**Domain Being Offered (Our Premium Asset):** {$ownedDomain}
**Target's Current Website:** {$targetWebsite}
{$distributionDesc}

**Desired Tone:** {$tone}
**Pitch Strategy / Instructions:** {$instructions}

## IMPORTANT REQUIREMENTS:
- Generate EXACTLY {$variantCount} distinct email variant(s). Each must have completely different openers, angles, and sign-offs.
- Use your expertise as a domain marketing strategist to highlight the strategic value of "{$ownedDomain}".
- Focus on what "{$ownedDomain}" means for THEIR brand, market position, and competitive edge.
- If Target's Current Website is known, subtly contrast it with the premium domain.
- Each variant MUST include a "target_emails" field (JSON array of email strings) listing all recipients for that variant.
- Return ONLY the JSON array of {$variantCount} objects. Each object must have: target_emails (array), subject (string), body (string).

### OUTPUT FORMAT:
[
  {
    "target_emails": ["email1@example.com", "email2@example.com"],
    "subject": "subject line",
    "body": "email body with sign-off"
  }
]
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
     * Sends the text to Gemini.
     */
    protected function sendRequest(string $systemPrompt, string $userPrompt): array
    {
        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ],
            'contents' => [
                [
                    'parts' => [
                        ['text' => $userPrompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
                'responseMimeType' => 'application/json',
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_LOW_AND_ABOVE',
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_LOW_AND_ABOVE',
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_LOW_AND_ABOVE',
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_LOW_AND_ABOVE',
                ]
            ]
        ];

        try {
            $response = Http::timeout(30)->post($url, $payload);

            if ($response->failed()) {
                Log::error('Gemini API Error: ' . $response->body());
                return ['success' => false, 'error' => 'Failed to connect to AI service. ' . $response->status()];
            }

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';

            if (empty($text)) {
                return ['success' => false, 'error' => 'API returned empty response.'];
            }

            $decoded = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gemini JSON Parse Error', ['text' => $text, 'error' => json_last_error_msg()]);
                return ['success' => false, 'error' => 'Failed to parse AI response.'];
            }

            return ['success' => true, 'data' => $decoded];

        } catch (\Exception $e) {
            Log::error('Gemini Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reads a normal (ham) email and rewrites it into a new, natural personal email template.
     */
    public function rewriteWarmingEmail(string $originalText): array
    {
        $systemPrompt = <<<'PROMPT'
You are a professional communications expert specializing in domain and digital marketing. 
Your task is to read a provided email snippet and REWRITE it completely into a new, distinct personal email.
It must not look like the original, but can share the general theme (e.g., catching up, asking a question, sharing an update).
Create a natural Subject Line (2-5 words).
Create a natural Body text (2-5 sentences).
Write as if you are a seasoned professional with excellent communication skills.
Keep it very conversational as if writing to a colleague or business contact.

## CRITICAL SAFETY RULES:
1. ABSOLUTELY NEVER use offensive language, profanity, swear words, or insults (e.g., "ass", "idiot", "damn", "shit", "hell", "crap", etc.).
2. Do not use abusive, divisive, or inappropriate language. Maintain a perfectly safe, pleasant, and professional tone.
3. AVOID spam trigger words (e.g., "free", "guarantee", "buy now", "click here", "act now").
4. Do NOT use ALL CAPS or excessive punctuation.
5. Keep the language warm, natural, and human.

Respond ONLY with valid JSON in this format:
{
  "subject": "your casual subject",
  "body": "Hi [Name],\n\nyour rewritten casual email here.\n\nBest,\n[Your Name]"
}
PROMPT;

        $userPrompt = "Please rewrite this personal email text into a new completely distinct message:\n\n\"\"\"\n" . $originalText . "\n\"\"\"";

        return $this->sendRequest($systemPrompt, $userPrompt);
    }

    /**
     * Generate a follow-up email based on the original campaign email.
     * Uses the original subject/body as context to create a strategic follow-up.
     */
    public function generateFollowUp(string $originalSubject, string $originalBody, int $followUpNumber = 1): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Gemini API key is not configured.');
        }

        $systemPrompt = <<<'PROMPT'
You are a world-class Premium Domain Name Marketing Strategist and Follow-Up Expert. You specialize in writing strategic follow-up emails that re-engage prospects who didn't respond to the initial outreach.

## YOUR RULES:
1. You will receive the ORIGINAL email that was sent. Your job is to write a FOLLOW-UP email based on it.
2. The follow-up must reference the original email subtly (e.g., "Following up on my note about...")
3. Add NEW value — don't just repeat the original. Offer a different angle, insight, or perspective.
4. Keep it even shorter than the original (under 60 words for the body).
5. Use a different, fresh subject line (3-5 words).
6. Maintain a warm, non-pushy tone. Show genuine interest in THEIR business.
7. NEVER use spam trigger words, ALL CAPS, or aggressive language.
8. NEVER use offensive or inappropriate language.
9. Soft CTA only (e.g., "Still on your radar?", "Worth revisiting?")

## FOLLOW-UP STRATEGIES (choose one per email):
- **New Angle**: Present a different benefit of the domain
- **Social Proof**: Mention industry trends in domain acquisitions
- **Gentle Reminder**: Brief, friendly nudge
- **Added Value**: Share an insight about their current digital presence

Respond ONLY with valid JSON:
{
  "subject": "follow-up subject line",
  "body": "follow-up email body with sign-off"
}
PROMPT;

        $userPrompt = <<<PROMPT
This is follow-up #{$followUpNumber} for a prospect who did NOT respond to our original email.

**ORIGINAL EMAIL SENT:**
Subject: {$originalSubject}
Body:
{$originalBody}

Generate a strategic follow-up email that references the original but adds new value. Keep it concise and natural.
PROMPT;

        return $this->sendRequest($systemPrompt, $userPrompt);
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
                // Normalize: ensure target_emails is always an array
                return array_map(function ($v) {
                    if (!empty($v['target_emails']) && is_array($v['target_emails'])) {
                        // New format: target_emails array
                        $v['target_email'] = $v['target_emails'][0] ?? 'General / Bulk';
                    } elseif (!empty($v['target_email'])) {
                        // Old format: single target_email string
                        $v['target_emails'] = [$v['target_email']];
                    } else {
                        $v['target_email'] = 'General / Bulk';
                        $v['target_emails'] = ['General / Bulk'];
                    }
                    return $v;
                }, $decoded);
            }
            
            // In case it returned a single object despite asking for an array
            if (isset($decoded['subject']) && isset($decoded['body'])) {
                return [
                    [
                        'target_email' => 'General / Bulk',
                        'target_emails' => ['General / Bulk'],
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
                'target_emails' => ['General / Bulk'],
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
            $response = Http::timeout(10)->post('http://127.0.0.1:5050/correct', [
                'variants' => $variants,
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $correctedVariants = $json['variants'] ?? $variants;
                $totalCorrections = $json['total_corrections'] ?? 0;

                if ($totalCorrections > 0) {
                    Log::info("ML Spam Classifier corrected {$totalCorrections} variant(s) locally.");
                }

                // Preserve all metadata from the Python response for the UI, but restore target_emails from the original PHP array
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
