<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;

    /**
     * All available professional email prefixes, grouped by category.
     */
    protected array $emailPrefixes = [
        'executive' => ['ceo', 'cto', 'cfo', 'coo', 'founder', 'director', 'president', 'vp'],
        'general' => ['admin', 'contact', 'info', 'hello', 'team', 'office', 'general', 'enquiries'],
        'sales' => ['sales', 'business', 'partnerships', 'deals', 'accounts', 'billing'],
        'marketing' => ['marketing', 'media', 'press', 'pr', 'communications', 'social'],
        'support' => ['support', 'help', 'service', 'care', 'feedback'],
        'hr' => ['hr', 'careers', 'jobs', 'recruiting', 'talent', 'people'],
        'technical' => ['tech', 'engineering', 'dev', 'it', 'security', 'ops'],
    ];

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->model = config('services.gemini.model', 'gemini-2.0-flash');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    }

    /**
     * Generate random professional email targets for a domain.
     */
    public function generateTargetEmails(string $domain, int $count = 5): array
    {
        $allPrefixes = [];
        foreach ($this->emailPrefixes as $category => $prefixes) {
            foreach ($prefixes as $prefix) {
                $allPrefixes[] = [
                    'email' => $prefix . '@' . $domain,
                    'prefix' => $prefix,
                    'category' => $category,
                ];
            }
        }

        // Shuffle and pick random ones
        shuffle($allPrefixes);
        $selected = array_slice($allPrefixes, 0, min($count, count($allPrefixes)));

        // Ensure at least one from 'general' and one from 'executive' if possible
        $categories = array_column($selected, 'category');
        if (!in_array('general', $categories)) {
            $generalPrefixes = $this->emailPrefixes['general'];
            $randomGeneral = $generalPrefixes[array_rand($generalPrefixes)];
            $selected[0] = [
                'email' => $randomGeneral . '@' . $domain,
                'prefix' => $randomGeneral,
                'category' => 'general',
            ];
        }

        return $selected;
    }

    /**
     * Build the comprehensive anti-spam system prompt.
     */
    public function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are an expert cold email copywriter who specializes in writing highly effective, deliverable, and anti-spam emails. Your emails consistently land in the PRIMARY inbox, never in spam or promotions.

## YOUR ABSOLUTE RULES — FOLLOW EVERY SINGLE ONE:

### SPAM AVOIDANCE (CRITICAL):
1. NEVER use these spam trigger words or phrases: "Free", "Act Now", "Limited Time", "100% Guarantee", "No Obligation", "Click Here", "Buy Now", "Order Now", "Don't Miss", "Exclusive Deal", "Special Offer", "Congratulations", "Winner", "Cash", "$$$", "Earn Money", "Make Money", "Double Your", "Risk-Free", "No Cost", "Urgent", "Immediately", "Call Now", "Apply Now", "Sign Up Free", "No Strings Attached", "Once in a Lifetime", "As Seen On", "Miracle", "Revolutionary".
2. NEVER use ALL CAPS for emphasis.
3. NEVER use excessive exclamation marks (max 1 in the entire email, and only if truly natural).
4. NEVER use aggressive or pushy sales language.
5. NEVER include fake urgency or artificial scarcity.
6. NEVER use clickbait phrases in the subject line.
7. NEVER start the subject line with "Re:" or "Fwd:" deceptively.

### SUBJECT LINE RULES:
1. Keep it between 4-8 words maximum.
2. Make it specific and relevant to the recipient's business/domain.
3. Write it as a natural, curious, human-written subject — like an email between colleagues.
4. Lowercase is preferred over Title Case (more natural).
5. No emojis, no special characters, no brackets.

### EMAIL BODY RULES:
1. Keep it under 120 words. Brevity is king.
2. Start with a personalized opener that references something specific about their domain/industry (infer from the domain name).
3. Provide ONE clear value proposition in 1-2 sentences.
4. Keep paragraphs to 1-2 sentences maximum.
5. Write in a conversational, human tone — like a real person emailing another real person.
6. End with a soft, low-friction CTA. Examples of good CTAs: "Would it make sense to chat for 15 minutes?", "Happy to share more details if you're curious.", "Worth a quick look?".
7. Include a simple, professional sign-off (e.g., "Best," or "Cheers,").
8. Add a realistic sender name and title.
9. NO heavy HTML formatting. Plain text only. No bold, no bullet points, no images.
10. NO tracking pixels language, no "click here" links.

### OUTPUT FORMAT:
You must respond with ONLY a valid JSON object in exactly this format, with no additional text before or after:
{
  "subject": "your subject line here",
  "body": "your full email body here including sign-off"
}

Do NOT include markdown code fences, do NOT include any explanation. Only the raw JSON object.
PROMPT;
    }

    /**
     * Build the user prompt with context.
     */
    public function buildUserPrompt(array $params): string
    {
        $domain = $params['domain'];
        $instructions = $params['instructions'];
        $productService = $params['product_service'] ?? 'our product/service';
        $tone = $params['tone'] ?? 'professional';
        $targetEmails = $params['target_emails'] ?? [];

        $emailList = implode(', ', array_column($targetEmails, 'email'));

        return <<<PROMPT
Generate a cold email for the following context:

**Target Domain:** {$domain}
**Target Email Addresses:** {$emailList}
**Product/Service Being Offered:** {$productService}
**Desired Tone:** {$tone}
**Specific Instructions from the sender:** {$instructions}

Remember:
- Infer the industry and business type from the domain name "{$domain}" and personalize accordingly.
- The email should feel like it was written specifically for someone at {$domain}.
- Follow ALL anti-spam rules from your system instructions without exception.
- Return ONLY the JSON object with "subject" and "body" keys.
PROMPT;
    }

    /**
     * Call the Gemini API and generate the email.
     */
    public function generateEmail(array $params): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('Gemini API key is not configured. Please set GEMINI_API_KEY in your .env file.');
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($params);

        $startTime = microtime(true);

        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

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

        try {
            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            $endTime = microtime(true);
            $generationTimeMs = round(($endTime - $startTime) * 1000, 2);

            if (!$response->successful()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Unknown API error';
                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'error' => $errorMessage,
                ]);
                throw new \RuntimeException("Gemini API Error ({$response->status()}): {$errorMessage}");
            }

            $data = $response->json();

            // Extract the generated text
            $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $tokensUsed = $data['usageMetadata']['totalTokenCount'] ?? null;

            // Parse the JSON response
            $parsed = $this->parseGeneratedEmail($generatedText);

            return [
                'subject' => $parsed['subject'],
                'body' => $parsed['body'],
                'system_prompt' => $systemPrompt,
                'full_prompt' => $userPrompt,
                'model' => $this->model,
                'tokens_used' => $tokensUsed,
                'generation_time_ms' => $generationTimeMs,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Gemini API Connection Error', ['message' => $e->getMessage()]);
            throw new \RuntimeException('Could not connect to the Gemini API. Please check your internet connection and try again.');
        }
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

        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['subject']) && isset($decoded['body'])) {
            return [
                'subject' => trim($decoded['subject']),
                'body' => trim($decoded['body']),
            ];
        }

        // Fallback: try to extract subject and body manually
        Log::warning('Failed to parse Gemini JSON response, attempting fallback', ['raw' => $text]);

        $subject = 'Quick question about your business';
        $body = $text;

        // Try regex extraction
        if (preg_match('/"subject"\s*:\s*"([^"]+)"/i', $text, $subjectMatch)) {
            $subject = $subjectMatch[1];
        }
        if (preg_match('/"body"\s*:\s*"([\s\S]+?)"\s*}/i', $text, $bodyMatch)) {
            $body = $bodyMatch[1];
            $body = str_replace('\n', "\n", $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
