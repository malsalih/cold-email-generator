<?php

namespace App\Http\Controllers;

use App\Models\GeneratedEmail;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmailGeneratorController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Show the main generator form.
     */
    public function index()
    {
        $recentEmails = GeneratedEmail::latestFirst()->limit(5)->get();

        return view('generator.index', [
            'recentEmails' => $recentEmails,
            'tones' => $this->getAvailableTones(),
        ]);
    }

    /**
     * Generate the cold email.
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'owned_domain' => 'required|string|max:255|regex:/^[a-zA-Z0-9][a-zA-Z0-9-]*\.[a-zA-Z]{2,}$/',
            'target_website' => 'nullable|string|max:255',
            'target_emails' => 'nullable|string|max:10000',
            'max_emails' => 'required|integer|min:1|max:50',
            'instructions' => 'required|string|min:10|max:2000',
            'tone' => 'required|string|in:' . implode(',', array_keys($this->getAvailableTones())),
        ], [
            'owned_domain.regex' => 'Please enter a valid domain (e.g., mysuperdomain.com without http://).',
            'instructions.min' => 'Please provide at least 10 characters of instructions for better email generation.',
        ]);

        $ownedDomain = strtolower(trim($validated['owned_domain']));
        $targetWebsite = isset($validated['target_website']) ? strtolower(trim($validated['target_website'])) : null;
        
        // Parse target emails from textarea (comma or newline separated)
        $targetEmails = [];
        if (!empty($validated['target_emails'])) {
            $rawEmails = preg_split('/[\s,]+/', $validated['target_emails'], -1, PREG_SPLIT_NO_EMPTY);
            foreach ($rawEmails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $targetEmails[] = [
                        'email' => $email,
                        'prefix' => explode('@', $email)[0],
                        'category' => 'custom',
                    ];
                }
            }
        }

        // If no emails provided, fall back to a generic bulk template indicator
        if (empty($targetEmails)) {
            $targetEmails[] = [
                'email' => 'General / Bulk Template',
                'prefix' => 'generic',
                'category' => 'bulk',
            ];
        } else {
            // Apply randomized limit if provided emails exceed max_emails
            $maxEmails = (int) $validated['max_emails'];
            if (count($targetEmails) > $maxEmails) {
                shuffle($targetEmails);
                $targetEmails = array_slice($targetEmails, 0, $maxEmails);
            }
        }

        try {
            // Call Gemini API
            $result = $this->geminiService->generateEmail([
                'owned_domain' => $ownedDomain,
                'target_website' => $targetWebsite,
                'instructions' => $validated['instructions'],
                'tone' => $validated['tone'],
                'target_emails' => $targetEmails,
                'max_emails' => (int) $validated['max_emails'],
            ]);

            // Save to database
            $emailRecord = GeneratedEmail::create([
                'target_domain' => $targetWebsite ?? 'N/A',
                'owned_domain' => $validated['owned_domain'],
                'target_website' => $validated['target_website'] ?? null,
                'target_emails' => $targetEmails,
                'user_instructions' => $validated['instructions'],
                'tone' => $validated['tone'],
                'system_prompt' => $result['system_prompt'],
                'full_prompt_sent' => $result['full_prompt'],
                'generated_variants' => $result['variants'],
                // Fallbacks so legacy queries don't break immediately
                'generated_subject' => $result['variants'][0]['subject'] ?? '',
                'generated_body' => $result['variants'][0]['body'] ?? '',
                'gemini_model' => $result['model'],
                'tokens_used' => $result['tokens_used'],
                'generation_time_ms' => $result['generation_time_ms'],
            ]);

            return redirect()->route('email.result', $emailRecord->id)
                ->with('success', 'Email generated successfully!');

        } catch (\RuntimeException $e) {
            Log::error('Email Generation Failed', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Show the generated email result.
     */
    public function result(GeneratedEmail $email)
    {
        return view('generator.result', [
            'email' => $email,
        ]);
    }

    /**
     * Show the email history.
     */
    public function history(Request $request)
    {
        $query = GeneratedEmail::latestFirst();

        if ($request->filled('domain')) {
            $query->forDomain($request->input('domain'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('owned_domain', 'like', "%{$search}%")
                  ->orWhere('target_website', 'like', "%{$search}%")
                  ->orWhere('generated_subject', 'like', "%{$search}%")
                  ->orWhere('generated_body', 'like', "%{$search}%");
            });
        }

        $emails = $query->paginate(12);

        return view('generator.history', [
            'emails' => $emails,
        ]);
    }

    /**
     * Show details of a specific historical email.
     */
    public function show(GeneratedEmail $email)
    {
        return view('generator.show', [
            'email' => $email,
        ]);
    }

    /**
     * Delete a generated email.
     */
    public function destroy(GeneratedEmail $email)
    {
        $email->delete();

        return redirect()->route('email.history')
            ->with('success', 'Email record deleted successfully.');
    }

    /**
     * Get available tone options.
     */
    protected function getAvailableTones(): array
    {
        return [
            'professional' => 'Professional & Polished',
            'friendly' => 'Friendly & Warm',
            'casual' => 'Casual & Conversational',
            'authoritative' => 'Authoritative & Expert',
            'curious' => 'Curious & Inquisitive',
            'empathetic' => 'Empathetic & Understanding',
        ];
    }
}
