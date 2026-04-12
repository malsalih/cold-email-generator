@extends('layouts.app')

@section('title', 'ColdForge — AI Cold Email Generator')

@section('content')
<div class="space-y-10">
    {{-- Hero Section --}}
    <div class="text-center space-y-4 pt-4">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-500/10 border border-violet-500/20 text-violet-400 text-xs font-medium tracking-wide uppercase">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
            {{ __('generator.shield_item1_desc') }}
        </div>
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">
            <span class="bg-gradient-to-r from-content-primary via-content-secondary to-content-muted bg-clip-text text-transparent">{{ __('generator.sell_premium_domains') }}</span>
            <br>
            <span class="bg-gradient-to-r from-violet-400 via-fuchsia-400 to-cyan-400 bg-clip-text text-transparent">{{ __('generator.with_ai_precision') }}</span>
        </h1>
        <p class="text-content-muted text-lg max-w-2xl mx-auto leading-relaxed">
            {{ __('generator.hero_subtitle') }}
        </p>
    </div>

    {{-- Main Form --}}
    <form action="{{ route('email.generate') }}" method="POST" class="space-y-6" x-data="emailForm()" @submit="isLoading = true">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left Column: Inputs --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Domain Input --}}
                <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4 hover:border-violet-500/30 transition-colors">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-content-primary">{{ __('generator.owned_domain') }}</h2>
                            <p class="text-xs text-content-muted">{{ __('generator.owned_domain_desc') }}</p>
                        </div>
                    </div>

                    <div class="relative">
                        <input type="text"
                               name="owned_domain"
                               id="owned_domain"
                               value="{{ old('owned_domain') }}"
                               placeholder="e.g., superai.com"
                               class="w-full px-4 py-3 bg-surface-bg border border-surface-border rounded-xl text-content-primary placeholder-content-muted text-sm font-mono focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition-all text-left" dir="ltr"
                               required>
                    </div>
                    @error('owned_domain')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror

                    {{-- Target Website --}}
                    <div class="space-y-2 mt-4 pt-4 border-t border-surface-border">
                        <div class="flex flex-col">
                            <label class="text-xs font-semibold text-content-primary">{{ __('generator.target_website') }}</label>
                            <span class="text-[11px] text-content-muted">{{ __('generator.target_website_desc') }}</span>
                        </div>
                        <input type="text" name="target_website" value="{{ old('target_website') }}" placeholder="e.g., super-ai-tech.net"
                               class="w-full px-4 py-2 bg-surface-bg border border-surface-border rounded-xl text-content-primary placeholder-content-muted text-sm font-mono focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition-all text-left" dir="ltr">
                    </div>
                </div>

                {{-- Instructions --}}
                <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4 hover:border-cyan-500/30 transition-colors">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-cyan-500/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-content-primary">{{ __('generator.your_instructions') }}</h2>
                            <p class="text-xs text-content-muted">{{ __('generator.instructions_desc') }}</p>
                        </div>
                    </div>

                    <textarea name="instructions"
                              id="instructions"
                              rows="5"
                              placeholder="{{ __('generator.instructions_placeholder') }}"
                              class="w-full px-4 py-3 bg-surface-bg border border-surface-border rounded-xl text-content-primary placeholder-content-muted text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-cyan-500/50 transition-all resize-none text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}"
                              required>{{ old('instructions') }}</textarea>
                    @error('instructions')
                        <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                    @enderror

                    <div class="space-y-3">
                        <div class="space-y-2">
                            <label for="target_emails" class="text-xs font-medium text-content-muted">{{ __('generator.target_emails') }}</label>
                            <textarea name="target_emails"
                                      id="target_emails"
                                      rows="2"
                                      placeholder="ceo@company.com, marketing@company.com"
                                      class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary placeholder-content-muted text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50 transition-all resize-none text-left" dir="ltr">{{ old('target_emails') }}</textarea>
                        </div>
                        
                        {{-- Random Email Limit Slider --}}
                        <div class="space-y-2 pt-2 border-t border-surface-border">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-medium text-content-muted">{{ __('generator.random_select_label', ['count' => '']) }}</label>
                                <span class="text-xs font-mono text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded-md" x-text="maxEmails"></span>
                            </div>
                            <input type="range" name="max_emails" min="1" max="50" x-model="maxEmails"
                                   class="w-full h-1.5 bg-zinc-700 rounded-lg appearance-none cursor-pointer accent-cyan-500">
                        </div>
                    </div>
                    
                    <div class="space-y-2 pt-2">
                        <label for="tone" class="text-xs font-medium text-content-muted">{{ __('generator.email_tone') }}</label>
                        <select name="tone"
                                id="tone"
                                class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50 transition-all appearance-none cursor-pointer text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}">
                            @foreach($tones as $value => $label)
                                <option value="{{ $value }}" {{ old('tone', 'professional') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Submit --}}
                <button type="submit"
                        class="w-full relative group overflow-hidden rounded-xl py-3.5 px-6 font-semibold text-sm transition-all duration-300"
                        :disabled="isLoading">
                    <div class="absolute inset-0 bg-gradient-to-r from-violet-600 via-fuchsia-600 to-cyan-600 group-hover:opacity-90 transition-opacity"></div>
                    <div class="absolute inset-0 bg-gradient-to-r from-violet-600 via-fuchsia-600 to-cyan-600 blur-xl opacity-50 group-hover:opacity-70 transition-opacity"></div>
                    <span class="relative flex items-center justify-center gap-2 text-white">
                        <template x-if="!isLoading">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                                {{ __('generator.btn_generate') }}
                            </span>
                        </template>
                        <template x-if="isLoading">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                {{ __('generator.btn_generating') }}
                            </span>
                        </template>
                    </span>
                </button>
            </div>

            {{-- Right Column: Info Panel --}}
            <div class="space-y-6">
                {{-- Anti-Spam Features --}}
                <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                        {{ __('generator.anti_spam_shield') }}
                    </h3>
                    <div class="space-y-3">
                        @foreach([
                            [__('generator.shield_item1_title'), __('generator.shield_item1_desc')],
                            [__('generator.shield_item2_title'), __('generator.shield_item2_desc')],
                            [__('generator.shield_item3_title'), __('generator.shield_item3_desc')],
                            [__('generator.shield_item4_title'), __('generator.shield_item4_desc')],
                            [__('generator.shield_item5_title'), __('generator.shield_item5_desc')],
                            [__('generator.shield_item6_title'), __('generator.shield_item6_desc')],
                        ] as [$title, $desc])
                        <div class="flex items-start gap-3">
                            <div class="w-5 h-5 rounded-full bg-emerald-500/10 flex items-center justify-center mt-0.5 shrink-0">
                                <svg class="w-3 h-3 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-content-secondary">{{ $title }}</p>
                                <p class="text-[11px] text-content-muted">{{ $desc }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Recent Emails --}}
                @if($recentEmails->count() > 0)
                <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-content-primary flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-content-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                            {{ __('generator.recent') }}
                        </span>
                        <a href="{{ route('email.history') }}" class="text-xs text-violet-400 hover:text-violet-300 transition">{{ __('generator.view_all') }}</a>
                    </h3>
                    <div class="space-y-2">
                        @foreach($recentEmails as $recent)
                        <a href="{{ route('email.show', $recent->id) }}"
                           class="block p-3 rounded-lg bg-surface-bg hover:bg-surface-card hover:shadow-sm border border-surface-border transition-all group">
                            <p class="text-xs font-medium text-content-secondary group-hover:text-content-primary truncate">{{ $recent->generated_subject }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[11px] font-mono text-violet-400">{{ $recent->target_domain }}</span>
                                <span class="text-zinc-600">·</span>
                                <span class="text-[11px] text-content-muted">{{ $recent->created_at->diffForHumans() }}</span>
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
        maxEmails: {{ old('max_emails', 5) }},
        isLoading: false,
    }
}
</script>
@endpush
@endsection
