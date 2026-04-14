<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReverseWarmingAccount;
use App\Models\ReverseWarmingLog;
use App\Models\WarmingAccount;
use App\Jobs\SendReverseWarmingJob;
use Google_Client;
use Google_Service_Oauth2;

class ReverseWarmingController extends Controller
{
    /**
     * Display the Reverse Warming Dashboard.
     */
    public function dashboard()
    {
        $accounts = ReverseWarmingAccount::orderBy('created_at', 'desc')->get();
        
        $totalSentToday = ReverseWarmingAccount::sum('sent_today');
        
        $logs = ReverseWarmingLog::with('account')
                ->orderBy('created_at', 'desc')
                ->take(100)
                ->get();

        $zohoAccounts = WarmingAccount::where('status', 'active')->get();
                
        return view('reverse_warming.dashboard', compact('accounts', 'totalSentToday', 'logs', 'zohoAccounts'));
    }

    /**
     * Start the queued Reverse Warming sequence.
     */
    public function startCampaign(Request $request)
    {
        $validated = $request->validate([
            'target_accounts' => 'required|array',
            'target_accounts.*' => 'exists:warming_accounts,id',
            'email_count' => 'required|integer|min:1|max:50',
            'delay_minutes' => 'required|integer|min:0|max:120',
        ]);

        $targets = $validated['target_accounts'];
        $count = $validated['email_count'];
        $delayIncrement = $validated['delay_minutes'];

        $totalJobs = 0;

        foreach ($targets as $targetId) {
            for ($i = 0; $i < $count; $i++) {
                // Calculate delay queue
                $delayMinutes = $i * $delayIncrement;
                
                if ($delayMinutes == 0) {
                    SendReverseWarmingJob::dispatch($targetId);
                } else {
                    SendReverseWarmingJob::dispatch($targetId)->delay(now()->addMinutes($delayMinutes));
                }
                
                $totalJobs++;
            }
        }

        return back()->with('success', "تم جدولة {$totalJobs} رسالة بنجاح للعمل في الخلفية.");
    }

    /**
     * Construct the Google Client.
     */
    protected function getGoogleClient()
    {
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri(route('reverse_warming.callback'));
        $client->setAccessType('offline'); // Required for getting a refresh token
        $client->setPrompt('consent'); // Force consent to ensure refresh token is provided
        
        // Scopes needed
        $client->addScope("https://mail.google.com/");
        $client->addScope("https://www.googleapis.com/auth/userinfo.email");
        $client->addScope("https://www.googleapis.com/auth/userinfo.profile");

        return $client;
    }

    /**
     * Redirect to Google OAuth Consent Screen.
     */
    public function redirect()
    {
        if (!env('GOOGLE_CLIENT_ID') || !env('GOOGLE_CLIENT_SECRET')) {
            return back()->with('error', 'Google Client ID and Secret are not configured in .env');
        }

        $client = $this->getGoogleClient();
        return redirect()->away($client->createAuthUrl());
    }

    /**
     * Handle the OAuth Callback from Google.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('reverse_warming.dashboard')->with('error', 'OAuth authorization failed or was denied.');
        }

        if (!$request->has('code')) {
            return redirect()->route('reverse_warming.dashboard')->with('error', 'No authorization code received.');
        }

        $client = $this->getGoogleClient();
        
        try {
            $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
            
            if (isset($token['error'])) {
                return redirect()->route('reverse_warming.dashboard')->with('error', 'Token error: ' . $token['error']);
            }

            $client->setAccessToken($token);
            
            // Get user info
            $oauth2 = new Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();

            $email = $userInfo->email;
            $name = $userInfo->name;

            // Save or update the account
            $account = ReverseWarmingAccount::firstOrNew(['email' => $email]);
            $account->name = $name;
            $account->access_token = $token['access_token'];
            
            // We only get a refresh token on the first authorization (because of prompt=consent)
            if (isset($token['refresh_token'])) {
                $account->refresh_token = $token['refresh_token'];
            }
            
            $account->expires_in = $token['expires_in'] ?? 3599;
            $account->token_expires_at = now()->addSeconds($account->expires_in - 60); // 60s buffer
            $account->status = 'active';
            $account->is_active = true;
            $account->save();

            return redirect()->route('reverse_warming.dashboard')->with('success', "Gmail account {$email} connected successfully!");

        } catch (\Exception $e) {
            return redirect()->route('reverse_warming.dashboard')->with('error', 'Failed to connect: ' . $e->getMessage());
        }
    }

    /**
     * Toggle Account Active Status.
     */
    public function toggle(ReverseWarmingAccount $account)
    {
        $account->is_active = !$account->is_active;
        $account->save();
        
        return back()->with('success', 'Account status updated.');
    }

    /**
     * Delete Account.
     */
    public function destroy(ReverseWarmingAccount $account)
    {
        $account->delete();
        return back()->with('success', 'Account disconnected and removed.');
    }
}
