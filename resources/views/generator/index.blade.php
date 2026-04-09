@extends('layouts.app')

@section('title', 'ColdForge — AI Cold Email Generator')

@section('content')
<div class="space-y-10">
    {{-- Hero Section --}}
    <div class="text-center space-y-4 pt-4">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-500/10 border border-violet-500/20 text-violet-400 text-xs font-medium tracking-wide uppercase">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
            Powered by Gemini AI
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">
            <span class="bg-gradient-to-r from-white via-zinc-300 to-zinc-500 bg-clip-text text-transparent">Forge Cold Emails</span>
            <br>
            <span class="bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-400 bg-clip-text text-transparent">That Actually Land</span>
        </h1>
        <p class="text-zinc-400 text-lg max-w-2xl mx-auto leading-relaxed">
            Generate hyper-personalized, anti-spam cold emails that bypass filters and land in the primary inbox. Every email is crafted with deliverability in mind.
        </p>
    </div>

    {{-- Main Form --}}
    <form action="{{ route('email.generate') }}" method="POST" class="space-y-6" x-data="emailForm()">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Inputs --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Domain Input --}}
                <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4 hover:border-zinc-700/50 transition-colors">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-white">Target Domain</h2>
                            <p class="text-xs text-zinc-500">The company you want to reach out to</p>
                        </div>
                    </div>

                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-zinc-500 text-sm font-mono">@</span>
                        </div>
                        <input type="text"
                               name="target_domain"
                               id="target_domain"
                               value="{{ old('target_domain') }}"
                               placeholder="eagnt.com"
                               x-model="domain"
                               class="w-full pl-10 pr-4 py-3 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition-all"
                               required>
                    </div>
                    @error('target_domain')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror

                    {{-- Email count slider --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs text-zinc-400">Number of target emails</label>
                            <span class="text-xs font-mono text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded-md" x-text="emailCount"></span>
                        </div>
                        <input type="range" name="email_count" min="3" max="10" x-model="emailCount"
                               class="w-full h-1.5 bg-zinc-700 rounded-lg appearance-none cursor-pointer accent-violet-500">
                    </div>
                </div>

                {{-- Instructions --}}
                <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4 hover:border-zinc-700/50 transition-colors">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-cyan-500/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-white">Your Instructions</h2>
                            <p class="text-xs text-zinc-500">Tell the AI what you want to communicate</p>
                        </div>
                    </div>

                    <textarea name="instructions"
                              id="instructions"
                              rows="5"
                              placeholder="Describe your core message. For example: 'We offer an AI-powered customer support platform that reduces ticket response time by 60%. I want to reach out and offer a personalized demo showing how their team could benefit.'"
                              class="w-full px-4 py-3 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50 transition-all resize-none"
                              required>{{ old('instructions') }}</textarea>
                    @error('instructions')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label for="product_service" class="text-xs font-medium text-zinc-400">Product / Service</label>
                            <input type="text"
                                   name="product_service"
                                   id="product_service"
                                   value="{{ old('product_service') }}"
                                   placeholder="e.g., AI Customer Support Platform"
                                   class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50 transition-all">
                        </div>
                        <div class="space-y-2">
                            <label for="tone" class="text-xs font-medium text-zinc-400">Email Tone</label>
                            <select name="tone"
                                    id="tone"
                                    class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500/50 transition-all appearance-none cursor-pointer">
                                @foreach($tones as $value => $label)
                                    <option value="{{ $value }}" {{ old('tone', 'professional') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full relative group overflow-hidden rounded-xl py-3.5 px-6 font-semibold text-sm transition-all duration-300"
                        :disabled="isLoading"
                        @click="isLoading = true">
                    <div class="absolute inset-0 bg-gradient-to-r from-violet-600 via-fuchsia-600 to-cyan-600 group-hover:opacity-90 transition-opacity"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-violet-600 via-fuchsia-600 to-cyan-600 blur-xl opacity-50 group-hover:opacity-70 transition-opacity"></div>
                    <span class="relative flex items-center justify-center gap-2 text-white">
                        <template x-if="!isLoading">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                                Generate Anti-Spam Email
                            </span>
                        </template>
                        <template x-if="isLoading">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Generating with Gemini...
                            </span>
                        </template>
                    </span>
                </button>
            </div>

            {{-- Right Column: Info Panel --}}
            <div class="space-y-6">
                {{-- Anti-Spam Features --}}
                <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                        Anti-Spam Shield
                    </h3>
                    <div class="space-y-3">
                        @foreach([
                            ['Spam word filtering', 'Blocks 50+ trigger words'],
                            ['Natural subject lines', '4-8 words, human-like'],
                            ['Concise body', 'Under 120 words'],
                            ['Soft CTA', 'Low-friction calls to action'],
                            ['Plain text format', 'No HTML, no images'],
                            ['Domain-aware', 'Industry-personalized'],
                        ] as [$title, $desc])
                        <div class="flex items-start gap-3">
                            <div class="w-5 h-5 rounded-full bg-emerald-500/10 flex items-center justify-center mt-0.5 shrink-0">
                                <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-zinc-300">{{ $title }}</p>
                                <p class="text-[11px] text-zinc-500">{{ $desc }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Recent Emails --}}
                @if($recentEmails->count() > 0)
                <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-white flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            Recent
                        </span>
                        <a href="{{ route('email.history') }}" class="text-xs text-violet-400 hover:text-violet-300 transition">View all →</a>
                    </h3>
                    <div class="space-y-2">
                        @foreach($recentEmails as $recent)
                        <a href="{{ route('email.show', $recent->id) }}"
                           class="block p-3 rounded-lg bg-zinc-800/30 hover:bg-zinc-800/60 border border-transparent hover:border-zinc-700/50 transition-all group">
                            <p class="text-xs font-medium text-zinc-300 group-hover:text-white truncate">{{ $recent->generated_subject }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[11px] font-mono text-violet-400">{{ $recent->target_domain }}</span>
                                <span class="text-zinc-600">·</span>
                                <span class="text-[11px] text-zinc-500">{{ $recent->created_at->diffForHumans() }}</span>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function emailForm() {
    return {
        domain: '{{ old("target_domain", "") }}',
        emailCount: {{ old('email_count', 5) }},
        isLoading: false,
    }
}
</script>
@endpush
@endsection
