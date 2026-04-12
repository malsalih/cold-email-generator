<?php

namespace App\Services;

class FollowUpGenerator
{
    protected GeminiApiClient $apiClient;

    public function __construct(GeminiApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function generate(string $originalSubject, string $originalBody, int $followUpNumber = 1): array
    {
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

        $result = $this->apiClient->sendRequest($systemPrompt, $userPrompt);

        if (!$result['success']) {
            throw new \RuntimeException("Generation failed: " . $result['error']);
        }

        return $result;
    }
}
