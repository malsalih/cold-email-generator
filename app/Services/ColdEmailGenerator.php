<?php

namespace App\Services;

class ColdEmailGenerator
{
    protected GeminiApiClient $apiClient;
    protected SpamClassifierService $spamClassifier;

    public function __construct(GeminiApiClient $apiClient, SpamClassifierService $spamClassifier)
    {
        $this->apiClient = $apiClient;
        $this->spamClassifier = $spamClassifier;
    }

    public function generate(array $params): array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($params);

        $result = $this->apiClient->sendRequest($systemPrompt, $userPrompt);

        if (!$result['success']) {
            throw new \RuntimeException("Generation failed: " . $result['error']);
        }

        $parsed = $this->parseResponse($result['data']);
        $parsed = $this->spamClassifier->correctSpamViaML($parsed);

        return [
            'variants' => $parsed,
            'system_prompt' => $systemPrompt,
            'full_prompt' => $userPrompt,
            'model' => $result['model'],
            'tokens_used' => $result['tokens_used'],
            'generation_time_ms' => $result['generation_time_ms'],
        ];
    }

    protected function buildSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a top-tier premium domain broker. You are writing a direct, 1-to-1 cold email to a busy CEO or founder.

Your goal is to write an email that is **100% UNDETECTABLE BY AI SPAM FILTERS**. It must read exactly like a human executive typed it quickly on their iPhone between meetings.

## YOUR ABSOLUTE RULES (FAILURE TO FOLLOW MEANS REJECTION):

### 1. THE "ANTI-AI" HUMANIZATION PROTOCOL:
- **Grammar:** gnore strict grammar rules. Randomly leave out or misplace commas and periods so the text flows a bit more messily. Also, never use an apostrophe in the word 'it's'—always write 'its' even when you mean 'it is'.
- **Burstiness (CRITICAL):** Radically vary your sentence lengths. Write a 3-word punchy sentence. Then a 12-word sentence. Then a 5-word sentence. AI writes in monotonous, uniform lengths. You MUST NOT.
- **Tone:** Casual, extremely direct, slightly informal but respectful. Do not sound like a marketer. Sound like an equal.
- **Human Flaws:** It is okay to start sentences with "And", "But", or "Because". Use em-dashes (—) occasionally instead of commas. Do not be grammatically flawless.
- **Reading Level:** Write at an 8th-grade reading level. Use simple, everyday vocabulary. No complex academic jargon. 
- **Formatting:** Plain text only. No bullet points. No bolding. No standard "3-paragraph essay" structure. No generic "I hope this email finds you well" greetings.

### 2. THE BANNED "CHATGPT" VOCABULARY:
If you use ANY of these words, the email will be flagged as AI and deleted:
- "Delve", "tapestry", "testament", "moreover", "furthermore", "in conclusion", "crucial", "vital", "elevate", "synergy", "leverage", "cutting-edge", "innovative", "unlock", "seamless", "landscape", "pivotal", "tailored", "realm", "bustling", "ensure", "comprehensive".
- Avoid all overly dramatic adjectives ("incredible", "amazing", "revolutionary").

### 3. TRADITIONAL SPAM AVOIDANCE:
- NEVER use: "Free", "Act Now", "Limited Time", "Guarantee", "Buy Now", "Order Now", "Click Here", "Urgent", "$$$".
- NEVER use ALL CAPS for emphasis.
- NEVER use more than one exclamation mark (!) in the entire email.
- NEVER include fake urgency or aggressive sales pressure.

### 4. STRATEGY & STRUCTURE:
- Very short: 40 to 80 words maximum.
- Open directly about THEIR business or a specific observation.
- Mention the domain simply as a strategic asset that belongs with them.
- Close with a very low-friction, casual question (e.g., "Open to a quick chat about this?", "Worth exploring?", "Any interest?").
- Subject line must be 2 to 5 words, lowercase or sentence case, looking like an internal forward. No emojis.

### OUTPUT FORMAT:
You must respond with ONLY a valid JSON array of objects in exactly this format, with no additional text or markdown before or after:
[
  {
    "target_email": "ceo@example.com",
    "subject": "your human subject line",
    "body": "your full human email body here including a casual sign-off"
  }
]
PROMPT;
    }

    protected function buildUserPrompt(array $params): string
    {
        $ownedDomain = $params['owned_domain'];
        $targetWebsite = $params['target_website'] ?: 'Unknown/General Prospect';
        $instructions = $params['instructions'];
        $tone = $params['tone'] ?? 'professional';
        $targetEmails = $params['target_emails'] ?? [];
        $maxEmails = $params['max_emails'] ?? count($targetEmails);

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
Generate {$variantCount} distinct cold email draft(s) for the following opportunity:

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

    protected function parseResponse(array $responseArray): array
    {
        // Extract array from standard Gemini response map
        if (isset($responseArray[0]['subject'])) {
            return array_map(function ($v) {
                if (!empty($v['target_emails']) && is_array($v['target_emails'])) {
                    $v['target_email'] = $v['target_emails'][0] ?? 'General / Bulk';
                } elseif (!empty($v['target_email'])) {
                    $v['target_emails'] = [$v['target_email']];
                } else {
                    $v['target_email'] = 'General / Bulk';
                    $v['target_emails'] = ['General / Bulk'];
                }
                return $v;
            }, $responseArray);
        }

        // Single object fallback
        if (isset($responseArray['subject']) && isset($responseArray['body'])) {
            return [
                [
                    'target_email' => 'General / Bulk',
                    'target_emails' => ['General / Bulk'],
                    'subject' => trim($responseArray['subject']),
                    'body' => trim($responseArray['body']),
                ]
            ];
        }

        throw new \RuntimeException('Failed to parse Gemini generated email format natively.');
    }
}
