<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\ReverseWarmingAccount;
use App\Models\ReverseWarmingLog;
use App\Models\WarmingAccount;
use App\Models\WarmingTemplate;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;

class ExecuteReverseWarming extends Command
{
    protected $signature = 'warming:reverse';
    protected $description = 'Executes a single reverse warming job using pre-generated ML templates.';

    public function handle()
    {
        // 1. Get an active Gmail account that hasn't reached its limit
        $sender = ReverseWarmingAccount::where('status', 'active')
                    ->where('is_active', true)
                    ->whereColumn('sent_today', '<', 'daily_limit')
                    ->inRandomOrder()
                    ->first();

        if (!$sender) {
            $this->info("No active reverse warming accounts with available daily quota.");
            return;
        }

        // 2. Get a random active professional Zoho account
        $target = WarmingAccount::where('status', 'active')->inRandomOrder()->first();

        if (!$target) {
            $this->error("No active Zoho professional accounts to send to.");
            return;
        }

        $this->info("Preparing to send from {$sender->email} to {$target->email}");

        // 3. Fetch an unused ML generated template
        $template = WarmingTemplate::active()->where('times_used', 0)->first();

        // 4. If all templates are used (or none exist), generate new ones via ML pool
        if (!$template) {
            $this->info("Template pool exhausted (all used). Generating 10 new ML templates...");
            Artisan::call('warming:generate-templates', ['count' => 10]);
            
            // Try fetching again
            $template = WarmingTemplate::active()->where('times_used', 0)->first();
            
            if (!$template) {
                // Completely abort if ML server failed to generate new templates
                $this->error("Failed to generate new templates. ML server might be offline.");
                return;
            }
        }

        try {
            // 5. Initialize Google Client
            $client = new Google_Client();
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            
            $tokenArray = [
                'access_token' => $sender->access_token,
                'refresh_token' => $sender->refresh_token,
                'expires_in' => $sender->expires_in,
                'created' => $sender->updated_at->timestamp,
            ];
            $client->setAccessToken($tokenArray);

            if ($client->isAccessTokenExpired()) {
                $this->info("Access token expired. Refreshing...");
                $client->fetchAccessTokenWithRefreshToken($sender->refresh_token);
                $newToken = $client->getAccessToken();
                
                $sender->access_token = $newToken['access_token'];
                if (isset($newToken['refresh_token'])) {
                    $sender->refresh_token = $newToken['refresh_token'];
                }
                $sender->expires_in = $newToken['expires_in'];
                $sender->token_expires_at = now()->addSeconds($newToken['expires_in'] - 60);
                $sender->save();
            }

            // 6. Build RFC 2822 Message
            $service = new Google_Service_Gmail($client);

            $subject = $template->subject;
            $body = $template->body;

            $mime = "To: {$target->email}\r\n";
            $mime .= "Subject: =?utf-8?B?" . base64_encode($subject) . "?=\r\n";
            $mime .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
            $mime .= $body;

            $rawMessage = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');

            $msg = new Google_Service_Gmail_Message();
            $msg->setRaw($rawMessage);

            // 7. Send via Gmail API
            $this->info("Sending via Gmail API...");
            $service->users_messages->send("me", $msg);

            // 8. Log & Mark Used
            $template->markUsed();

            ReverseWarmingLog::create([
                'reverse_warming_account_id' => $sender->id,
                'target_email' => $target->email,
                'subject' => $subject,
                'body' => $body,
                'status' => 'sent',
                'sent_at' => now()
            ]);

            $sender->increment('sent_today');
            $this->info("Reverse warming email sent successfully from {$sender->email} to {$target->email}.");

        } catch (\Exception $e) {
            $this->error("Failed to send: " . $e->getMessage());
            
            ReverseWarmingLog::create([
                'reverse_warming_account_id' => $sender->id,
                'target_email' => $target->email,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            if (str_contains($e->getMessage(), 'invalid_grant')) {
                $sender->status = 'disconnected';
                $sender->save();
            }
        }
    }
}
