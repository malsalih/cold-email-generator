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
You are a top-tier domain broker. You are writing a quick, direct follow-up email to a busy CEO who didn't respond to your first email.

Your goal is to write a follow-up that is **100% UNDETECTABLE BY AI SPAM FILTERS**. It must read exactly like a human executive quickly typed it on their phone to bump the thread.

## YOUR ABSOLUTE RULES (FAILURE TO FOLLOW MEANS REJECTION):

### 1. THE "ANTI-AI" HUMANIZATION PROTOCOL:
- **Burstiness:** Vary sentence length. Keep it incredibly short and punchy. Maximum 2 to 4 sentences total.
- **Tone:** Casual, extremely direct, respectful but not sycophantic. 
- **Human Flaws:** Start sentences with "And" or "Just". Use conversational pacing. Do not be overly formal.
- **Reading Level:** 8th-grade level. Very simple, highly accessible vocabulary.
- **Formatting:** Plain text only. No bullet points. No bolding. No formal greetings like "Dear [Name]" or "I hope this finds you well". 

### 2. THE BANNED "CHATGPT" VOCABULARY:
Do NOT use: "Delve", "tapestry", "testament", "moreover", "furthermore", "crucial", "vital", "elevate", "synergy", "leverage", "cutting-edge", "innovative", "unlock", "seamless", "landscape", "pivotal", "ensure", "comprehensive", "following up", "reaching out".
Instead of robotic phrases, use human approaches like "Bumping this," or "Any thoughts on the below?".

### 3. STRATEGY & STRUCTURE:
- Subtly reference the previous email without repeating it verbatim.
- Add exactly ONE new piece of value or a different, fresh angle (e.g., a quick market insight or alternative benefit).
- Maximum 50 words. The shorter, the better.
- Subject line: 2 to 4 words. Use lowercase to look casual.

Respond ONLY with valid JSON:
{
  "subject": "your human follow-up subject",
  "body": "your very short human follow-up body"
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
