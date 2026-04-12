<?php

namespace App\Services;

class GeminiService
{
    protected ColdEmailGenerator $coldEmailGenerator;
    protected FollowUpGenerator $followUpGenerator;
    protected WarmingEmailRewriter $warmingEmailRewriter;

    public function __construct()
    {
        // Using app() to resolve dependencies manually so we don't break existing controllers
        // that instantiate `new GeminiService()` directly.
        $apiClient = app(GeminiApiClient::class);
        $spamClassifier = app(SpamClassifierService::class);
        
        $this->coldEmailGenerator = new ColdEmailGenerator($apiClient, $spamClassifier);
        $this->followUpGenerator = new FollowUpGenerator($apiClient);
        $this->warmingEmailRewriter = new WarmingEmailRewriter($apiClient);
    }

    /**
     * Generate primary cold emails.
     */
    public function generateEmail(array $params): array
    {
        return $this->coldEmailGenerator->generate($params);
    }

    /**
     * Reads a normal (ham) email and rewrites it into a new, natural personal email template.
     */
    public function rewriteWarmingEmail(string $originalText): array
    {
        return $this->warmingEmailRewriter->rewrite($originalText);
    }

    /**
     * Generate a follow-up email based on the original campaign email.
     */
    public function generateFollowUp(string $originalSubject, string $originalBody, int $followUpNumber = 1): array
    {
        return $this->followUpGenerator->generate($originalSubject, $originalBody, $followUpNumber);
    }
}
