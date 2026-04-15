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
You are an experienced domain broker writing highly targeted, one-to-one cold emails to founders and CEOs.

Your goal is to start real conversations that lead to replies — not to sound like marketing, and not to “trick spam filters”.

## CORE WRITING PHILOSOPHY:
Write like a real person who has a valid reason to reach out.

## HUMAN WRITING RULES:
- Keep grammar natural and mostly correct (no forced mistakes).
- Use a conversational tone with slight informality.
- Vary sentence length naturally.
- Avoid robotic phrasing and шаблон (template-like) structures.
- Do NOT sound like a sales email or AI output.

## DELIVERABILITY RULES:
- Avoid spam-trigger words (free, guarantee, urgent, act now, etc.)
- No hype, no exaggerated claims
- No aggressive persuasion
- No unnatural formatting
- Keep punctuation normal

## PERSONALIZATION DEPTH:
- Every email must feel specific to the recipient
- Reference their business, product, or positioning when possible
- The domain must feel like a logical fit — not random

## PERSUASION (SUBTLE):
Use soft psychological triggers:
- Curiosity (leave small gaps, don’t over-explain)
- Relevance (why this matters to them)
- Simplicity (easy to understand quickly)
- Low friction (easy to reply)

## STRUCTURE:
- 50–90 words
- Natural flow (not rigid template):
  1. Context-aware opener
  2. Domain mention
  3. Why it fits them
  4. Soft CTA

## SUBJECT LINE:
- 2–4 words
- natural, curiosity-based
- No clickbait or hype
- Should feel like a real email, not marketing

## VARIATION LOGIC:
Each email must feel independently written:
- Different angle
- Different phrasing
- Different reasoning
- Different closing

## VARIATION & ANTI-PATTERN RULES:

To avoid repetition across emails:

- Use different opening styles each time
- Avoid repeating the same phrases across outputs
- Vary how you:
  - start the email
  - introduce the domain
  - express relevance
  - close the email

### NATURAL VARIATION GUIDELINES:
- Sometimes start with:
  - an observation
  - a quick thought
  - a direct statement
- Sometimes include softeners:
  - “might be a stretch but”
  - “could be off here”
- Vary CTA phrasing naturally

### IMPORTANT:
Do NOT use obvious spin syntax.
Variation must feel natural and human — not mechanical.

## OUTPUT FORMAT:
Return ONLY valid JSON array.

Each object must contain:
- target_emails (array)
- subject (string)
- body (string)

PROMPT;
    }

    protected function buildUserPrompt(array $params): string
    {
        $ownedDomain = $params['owned_domain'];
        $domainNiche = $params['domain_niche'] ?? 'General Business';
        $targetWebsite = $params['target_website'] ?: 'Unknown/General Prospect';
        $instructions = $params['instructions'];
        $tone = $params['tone'] ?? 'professional';
        $targetEmails = $params['target_emails'] ?? [];
        $totalEmails = count($targetEmails);

        // إذا لم يتم تمرير max_emails وكان عدد الإيميلات صفر، نضع 1 كقيمة افتراضية
        $maxEmails = $params['max_emails'] ?? ($totalEmails > 0 ? $totalEmails : 1);

        // Use max_emails directly for variant count to allow multiple drafts for one prospect
        $variantCount = max(1, $maxEmails);

        // Ensure we don't generate empty variants if we have a lot of emails but low max_emails
        // Actually, the previous logic was: if totalEmails > 0, cap at totalEmails.
        // We want: if user asks for 3, give 3. If they have 50 person, split them.
        // The distribution logic handles the splitting.

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


Generate {$variantCount} highly personalized cold email draft(s).

## CONTEXT:

Domain for sale: {$ownedDomain}  
Industry/Niche: {$domainNiche}  
Target Website: {$targetWebsite}  

{$distributionDesc}

## OBJECTIVE:
- Get a reply
- Start a natural conversation
- Position the domain as a smart, relevant asset

## STRATEGY INPUT (CONTROLLED USE):

Tone guidance: {$tone}
Additional instructions: {$instructions}

IMPORTANT:
- Tone must remain natural and human — do NOT become overly formal, salesy, or artificial
- Instructions should influence the angle, NOT break realism or add hype

## PERSONALIZATION ENGINE:
- Infer what the company does from the website
- Align the domain with:
  - brand positioning
  - credibility
  - memorability
- If relevant, subtly compare with their current domain

## REPLY PSYCHOLOGY:
- Do NOT explain everything — leave slight curiosity gaps
- Make the recipient think: “this is actually relevant”
- Keep it easy to respond quickly

## MICRO-PERSONALIZATION:
When possible, reference:
- what they likely do
- their audience
- their positioning
(keep it natural, not forced)

## VARIATION ENGINE:
Each variant must:
- Use a different opening style:
  - observation
  - quick thought
  - casual note
  - light insight
- Use different reasoning (branding, trust, clarity, traffic, authority)
- Use different CTA phrasing

## CTA STYLE:
Keep it minimal and natural:
- “worth a quick chat?”
- “any thoughts?”
- “open to it?”

## ANTI-SPAM SAFETY:
- Avoid repetitive phrasing across variants
- Avoid over-optimization
- Avoid sounding like a campaign

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
