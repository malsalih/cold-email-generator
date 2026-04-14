<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ReverseEmailGenerator
{
    public function generate(): array
    {
        try {
            $response = Http::timeout(15)->post('http://127.0.0.1:5050/generate');

            if ($response->failed()) {
                throw new \RuntimeException("Local ML API Error. Ensure python ml_service is running.");
            }

            $data = $response->json();
            
            if (!($data['success'] ?? false)) {
                throw new \RuntimeException("Generator failed: " . ($data['error'] ?? 'unknown'));
            }

            return [
                'subject' => $data['subject'] ?? 'Hello',
                'body' => $data['body'] ?? 'Just checking in.',
            ];

        } catch (\Exception $e) {
            // Fallback generic inquiry if ML server is down
            return [
                'subject' => 'Quick question',
                'body' => "Hi there,\n\nI was browsing your website and had a quick question about your services. Are you currently taking new clients?\n\nLet me know, thanks!",
            ];
        }
    }
}
