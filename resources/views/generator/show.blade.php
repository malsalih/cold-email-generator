@extends('layouts.app')

@section('title', $email->generated_subject . ' — ColdForge')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    {{-- Back & Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('email.history') }}"
           class="flex items-center gap-2 text-sm text-zinc-400 hover:text-white transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            Back to History
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('email.index') }}"
               class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-zinc-400 hover:text-white bg-zinc-800/50 hover:bg-zinc-800 border border-zinc-700/50 rounded-lg transition-all">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                New Email
            </a>
            <form action="{{ route('email.destroy', $email->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this email record?')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-red-400 hover:text-red-300 bg-red-500/5 hover:bg-red-500/10 border border-red-500/20 hover:border-red-500/30 rounded-lg transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    Delete
                </button>
            </form>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-[11px] text-zinc-500 uppercase tracking-wider">Domain</p>
                <p class="text-sm font-mono text-violet-400 mt-1">{{ $email->target_domain }}</p>
            </div>
            <div>
                <p class="text-[11px] text-zinc-500 uppercase tracking-wider">Tone</p>
                <p class="text-sm text-white capitalize mt-1">{{ $email->tone }}</p>
            </div>
            <div>
                <p class="text-[11px] text-zinc-500 uppercase tracking-wider">Generated</p>
                <p class="text-sm text-white mt-1">{{ $email->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div>
                <p class="text-[11px] text-zinc-500 uppercase tracking-wider">Generation Time</p>
                <p class="text-sm text-white mt-1">{{ number_format($email->generation_time_ms) }}ms</p>
            </div>
        </div>
        @if($email->product_service)
        <div class="mt-4 pt-4 border-t border-zinc-800/50">
            <p class="text-[11px] text-zinc-500 uppercase tracking-wider">Product / Service</p>
            <p class="text-sm text-white mt-1">{{ $email->product_service }}</p>
        </div>
        @endif
    </div>

    {{-- Email Preview Card --}}
    <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl overflow-hidden" x-data="{ copied: false }">
        <div class="bg-zinc-800/50 border-b border-zinc-700/30 px-6 py-4">
            <div class="space-y-2">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium text-zinc-500 uppercase tracking-wider shrink-0">Subject</span>
                    <span class="text-sm text-white font-medium">{{ $email->generated_subject }}</span>
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
        <div class="px-6 py-6">
            <pre class="whitespace-pre-wrap font-sans text-sm text-zinc-300 leading-relaxed bg-transparent border-0 p-0 m-0" id="email-body">{{ $email->generated_body }}</pre>
        </div>
        <div class="bg-zinc-800/30 border-t border-zinc-700/30 px-6 py-4">
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
        </div>
    </div>

    {{-- User Instructions --}}
    <details class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl overflow-hidden group">
        <summary class="px-6 py-4 cursor-pointer select-none flex items-center justify-between hover:bg-zinc-800/30 transition-colors">
            <span class="flex items-center gap-2 text-sm font-medium text-zinc-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                Your Original Instructions
            </span>
            <svg class="w-4 h-4 text-zinc-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </summary>
        <div class="px-6 pb-6 border-t border-zinc-800/50 pt-4">
            <p class="text-sm text-zinc-300 leading-relaxed">{{ $email->user_instructions }}</p>
        </div>
    </details>

    {{-- Prompt Sent --}}
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
