@extends('layouts.app')

@section('title', 'Generated Email — ColdForge')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold text-white">Your Email is Ready</h1>
            <p class="text-sm text-zinc-400">Generated for <span class="font-mono text-violet-400">{{ $email->target_domain }}</span> in {{ number_format($email->generation_time_ms) }}ms</p>
        </div>
        <a href="{{ route('email.index') }}"
           class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-zinc-400 hover:text-white bg-zinc-800/50 hover:bg-zinc-800 border border-zinc-700/50 rounded-xl transition-all">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            New Email
        </a>
    </div>

    {{-- Email Preview Card --}}
    <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl overflow-hidden" x-data="{ copied: false }">
        {{-- Email Header Bar --}}
        <div class="bg-zinc-800/50 border-b border-zinc-700/30 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="space-y-2 flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider shrink-0">Subject</span>
                        <span class="text-sm text-white font-medium truncate">{{ $email->generated_subject }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider shrink-0">To</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($email->target_emails as $target)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-zinc-700/50 text-[11px] font-mono text-zinc-300">
                                    {{ $target['email'] }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Email Body --}}
        <div class="px-6 py-6">
            <div class="prose prose-invert prose-sm max-w-none">
                <pre class="whitespace-pre-wrap font-sans text-sm text-zinc-300 leading-relaxed bg-transparent border-0 p-0 m-0" id="email-body">{{ $email->generated_body }}</pre>
            </div>
        </div>

        {{-- Action Bar --}}
        <div class="bg-zinc-800/30 border-t border-zinc-700/30 px-6 py-4 flex items-center gap-3">
            <button @click="
                const subject = @js($email->generated_subject);
                const body = document.getElementById('email-body').innerText;
                const full = 'Subject: ' + subject + '\n\n' + body;
                navigator.clipboard.writeText(full).then(() => {
                    copied = true;
                    setTimeout(() => copied = false, 2000);
                });
            "
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200"
            :class="copied ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-violet-500/10 text-violet-400 hover:bg-violet-500/20 border border-violet-500/30 hover:border-violet-500/50'">
                <template x-if="!copied">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9.75a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" /></svg>
                        Copy Full Email
                    </span>
                </template>
                <template x-if="copied">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        Copied!
                    </span>
                </template>
            </button>

            <button @click="
                const body = document.getElementById('email-body').innerText;
                navigator.clipboard.writeText(body).then(() => { });
            "
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-zinc-400 hover:text-white bg-zinc-800/50 hover:bg-zinc-700/50 border border-zinc-700/50 rounded-xl transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5A3.375 3.375 0 0 0 6.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0 0 15 2.25h-1.5a2.251 2.251 0 0 0-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 0 0-9-9Z" /></svg>
                Body Only
            </button>

            <button @click="
                navigator.clipboard.writeText(@js($email->generated_subject)).then(() => { });
            "
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-zinc-400 hover:text-white bg-zinc-800/50 hover:bg-zinc-700/50 border border-zinc-700/50 rounded-xl transition-all">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                Subject Only
            </button>
        </div>
    </div>

    {{-- Metadata --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-5 text-center">
            <p class="text-xs text-zinc-500 uppercase tracking-wider mb-1">Model</p>
            <p class="text-sm font-mono text-cyan-400">{{ $email->gemini_model }}</p>
        </div>
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-5 text-center">
            <p class="text-xs text-zinc-500 uppercase tracking-wider mb-1">Tokens Used</p>
            <p class="text-sm font-mono text-violet-400">{{ number_format($email->tokens_used ?? 0) }}</p>
        </div>
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-5 text-center">
            <p class="text-xs text-zinc-500 uppercase tracking-wider mb-1">Tone</p>
            <p class="text-sm text-fuchsia-400 capitalize">{{ $email->tone }}</p>
        </div>
    </div>

    {{-- Prompt Used (Collapsible) --}}
    <details class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl overflow-hidden group">
        <summary class="px-6 py-4 cursor-pointer select-none flex items-center justify-between hover:bg-zinc-800/30 transition-colors">
            <span class="flex items-center gap-2 text-sm font-medium text-zinc-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" /></svg>
                View Prompt Sent to Gemini
            </span>
            <svg class="w-4 h-4 text-zinc-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </summary>
        <div class="px-6 pb-6 border-t border-zinc-800/50 pt-4">
            <pre class="whitespace-pre-wrap text-xs text-zinc-400 font-mono leading-relaxed bg-zinc-800/30 rounded-xl p-4 max-h-80 overflow-y-auto">{{ $email->full_prompt_sent }}</pre>
        </div>
    </details>
</div>
@endsection
