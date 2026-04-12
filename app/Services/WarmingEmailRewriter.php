<?php

namespace App\Services;

class WarmingEmailRewriter
{
    protected GeminiApiClient $apiClient;

    public function __construct(GeminiApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function rewrite(string $originalText): array
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

        $result = $this->apiClient->sendRequest($systemPrompt, $userPrompt);

        if (!$result['success']) {
            throw new \RuntimeException("Generation failed: " . $result['error']);
        }

        return $result;
    }
}
