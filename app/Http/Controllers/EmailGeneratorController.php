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
            'target_domain' => 'required|string|max:255|regex:/^[a-zA-Z0-9][a-zA-Z0-9-]*\.[a-zA-Z]{2,}$/',
            'instructions' => 'required|string|min:10|max:2000',
            'product_service' => 'nullable|string|max:500',
            'tone' => 'required|string|in:' . implode(',', array_keys($this->getAvailableTones())),
            'email_count' => 'nullable|integer|min:3|max:10',
        ], [
            'target_domain.regex' => 'Please enter a valid domain (e.g., example.com without http://).',
            'instructions.min' => 'Please provide at least 10 characters of instructions for better email generation.',
        ]);

        // Clean up the domain
        $domain = strtolower(trim($validated['target_domain']));
        $domain = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $domain);
        $domain = rtrim($domain, '/');

        $emailCount = $validated['email_count'] ?? 5;

        // Generate target emails
        $targetEmails = $this->geminiService->generateTargetEmails($domain, $emailCount);

        try {
            // Call Gemini API
            $result = $this->geminiService->generateEmail([
                'domain' => $domain,
                'instructions' => $validated['instructions'],
                'product_service' => $validated['product_service'] ?? '',
                'tone' => $validated['tone'],
                'target_emails' => $targetEmails,
            ]);

            // Save to database
            $email = GeneratedEmail::create([
                'target_domain' => $domain,
                'target_emails' => $targetEmails,
                'user_instructions' => $validated['instructions'],
                'product_service' => $validated['product_service'] ?? null,
                'tone' => $validated['tone'],
                'system_prompt' => $result['system_prompt'],
                'full_prompt_sent' => $result['full_prompt'],
                'generated_subject' => $result['subject'],
                'generated_body' => $result['body'],
                'gemini_model' => $result['model'],
                'tokens_used' => $result['tokens_used'],
                'generation_time_ms' => $result['generation_time_ms'],
            ]);

            return redirect()->route('email.result', $email->id)
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
                $q->where('target_domain', 'like', "%{$search}%")
                  ->orWhere('generated_subject', 'like', "%{$search}%")
                  ->orWhere('generated_body', 'like', "%{$search}%")
                  ->orWhere('product_service', 'like', "%{$search}%");
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
