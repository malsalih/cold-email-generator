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
