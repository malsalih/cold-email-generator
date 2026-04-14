<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use App\Models\ReverseWarmingAccount;
use App\Models\ReverseWarmingLog;
use App\Models\WarmingAccount;
use App\Models\WarmingTemplate;
use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use Illuminate\Support\Facades\Log;

class SendReverseWarmingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 60;

    protected $targetAccountId;

    /**
     * Create a new job instance.
     */
    public function __construct($targetAccountId)
    {
        $this->targetAccountId = $targetAccountId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $target = WarmingAccount::find($this->targetAccountId);
        if (!$target || $target->status !== 'active') {
            Log::info("SendReverseWarmingJob: Target account {$this->targetAccountId} missing or inactive. Job silently exited.");
            return;
        }

        // 1. Pick a random active Gmail account with quota capacity
        $sender = ReverseWarmingAccount::where('status', 'active')
            ->where('is_active', true)
            ->whereColumn('sent_today', '<', 'daily_limit')
            ->inRandomOrder()
            ->first();

        if (!$sender) {
            // Cannot process now - no Gmail accounts available
            Log::error("SendReverseWarmingJob failed: No active ReverseWarmingAccounts have remaining daily quota.");
            return;
        }

        // 2. Fetch an unused ML generated template
        $template = WarmingTemplate::active()->where('times_used', 0)->first();

        // 3. Fallback: Auto-scaling pool 
        if (!$template) {
            Artisan::call('warming:generate-templates', ['count' => 10]);
            $template = WarmingTemplate::active()->where('times_used', 0)->first();
            
            if (!$template) {
                Log::error("SendReverseWarmingJob failed: ML Template pool generation utterly failed.");
                return;
            }
        }

        try {
            // 4. Client Init
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
                $client->fetchAccessTokenWithRefreshToken($sender->refresh_token);
                $newToken = $client->getAccessToken();
                
                if (isset($newToken['access_token'])) {
                    $sender->access_token = $newToken['access_token'];
                    if (isset($newToken['refresh_token'])) {
                        $sender->refresh_token = $newToken['refresh_token'];
                    }
                    $sender->expires_in = $newToken['expires_in'];
                    $sender->token_expires_at = now()->addSeconds($newToken['expires_in'] - 60);
                    $sender->save();
                } else {
                    throw new \Exception("Token refresh object missing access_token. Probably revoked.");
                }
            }

            // 5. Build RFC 2822
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

            // 6. Send
            $service->users_messages->send("me", $msg);

            // 7. Success state
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

        } catch (\Exception $e) {
            Log::error("SendReverseWarmingJob Error: " . $e->getMessage());

            ReverseWarmingLog::create([
                'reverse_warming_account_id' => $sender->id,
                'target_email' => $target->email,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            if (str_contains(strtolower($e->getMessage()), 'invalid_grant')) {
                $sender->status = 'disconnected';
                $sender->is_active = false;
                $sender->save();
            }
        }
    }
}
