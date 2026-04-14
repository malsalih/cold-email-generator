@extends('layouts.app')

@section('title', 'Generated Email — ColdForge')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    {{-- Back & Actions --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('email.history') }}"
               class="flex items-center gap-2 text-sm text-content-muted hover:text-content-primary transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                {{ __('generator.back_to_history') }}
            </a>
            <div class="h-4 w-px bg-surface-border"></div>
            <p class="text-sm text-content-muted">{{ __('generator.generated_for') }} <span class="font-mono text-violet-500 dark:text-violet-400">{{ $email->owned_domain }}</span></p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('email.index') }}"
               class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-content-muted hover:text-content-primary bg-surface-bg hover:bg-surface-card border border-surface-border rounded-lg transition-all">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                {{ __('generator.btn_new_email') }}
            </a>
            <form action="{{ route('email.destroy', $email->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('generator.confirm_delete_record') }}')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-red-400 hover:text-red-300 bg-red-500/5 hover:bg-red-500/10 border border-red-500/20 hover:border-red-500/30 rounded-lg transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    {{ __('generator.btn_delete_record') }}
                </button>
            </form>
        </div>
    </div>

    @php
        $variantsToDisplay = is_array($email->generated_variants) ? $email->generated_variants : [
            [
                'target_email' => 'General Template',
                'target_emails' => [],
                'subject' => $email->generated_subject,
                'body' => $email->generated_body,
                'original_subject' => $email->generated_subject,
                'original_body' => $email->generated_body,
                'was_spam' => false,
                'spam_probability' => 0,
            ]
        ];
    @endphp

    <div class="space-y-6">
        @foreach($variantsToDisplay as $index => $variant)
        @php
            $wasSpam = $variant['was_spam'] ?? false;
            $spamProb = $variant['spam_probability'] ?? 0;
            $correctedSpamProb = $variant['corrected_spam_probability'] ?? $spamProb;
            $origSubject = $variant['original_subject'] ?? ($variant['subject'] ?? '');
            $origBody = $variant['original_body'] ?? ($variant['body'] ?? '');
            $correctedSubject = $variant['subject'] ?? '';
            $correctedBody = $variant['body'] ?? '';
            $variantEmails = $variant['target_emails'] ?? [$variant['target_email'] ?? 'Unknown'];
        @endphp
        {{-- Email Preview Card --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden" x-data="{ copied: false, showOriginal: false }">

            {{-- ML Status Banner --}}
            <div class="px-6 py-3 border-b border-surface-border flex items-center justify-between {{ $wasSpam ? 'bg-amber-500/5' : 'bg-emerald-500/5' }}">
                <div class="flex items-center gap-3 flex-wrap">
                    @if($wasSpam)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-500/15 border border-amber-500/30 text-xs font-semibold text-amber-400">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                            {{ __('generator.ml_corrected') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-xs font-semibold text-emerald-400">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                            {{ __('generator.ml_verified') }}
                        </span>
                    @endif

                    <div class="flex items-center gap-2">
                        <span class="text-[10px] uppercase tracking-wider text-content-muted font-medium">{{ __('generator.score') }}</span>
                        <span class="text-[11px] font-mono {{ $spamProb > 70 ? 'text-red-600 dark:text-red-400' : ($spamProb > 40 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">{{ $spamProb }}%</span>
                        @if($wasSpam)
                        <svg class="w-3 h-3 text-content-muted" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                        <span class="text-[11px] font-mono {{ $correctedSpamProb > 70 ? 'text-red-600 dark:text-red-400' : ($correctedSpamProb > 40 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">{{ $correctedSpamProb }}%</span>
                        @endif
                    </div>

                    @php $displayProb = $wasSpam ? $correctedSpamProb : $spamProb; @endphp
                    <div class="w-20 h-1.5 bg-surface-bg border border-surface-border rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 {{ $displayProb > 70 ? 'bg-red-500' : ($displayProb > 40 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                            style="width: {{ min($displayProb, 100) }}%"></div>
                    </div>
                </div>

                @if($wasSpam)
                <button @click="showOriginal = !showOriginal"
                    class="flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-medium rounded-lg transition-all duration-200"
                    :class="showOriginal ? 'bg-violet-500/20 text-violet-300 border border-violet-500/40' : 'text-zinc-500 hover:text-zinc-300 bg-zinc-800/50 hover:bg-zinc-800 border border-zinc-700/50'">
                    <span x-text="showOriginal ? '{{ __('generator.btn_show_corrected') }}' : '{{ __('generator.btn_view_original') }}'"></span>
                </button>
                @endif
            </div>

            {{-- Variant Header --}}
            <div class="bg-surface-bg/50 border-b border-surface-border px-6 py-4">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-content-muted uppercase tracking-wider shrink-0">{{ __('generator.variant') }}</span>
                        <span class="text-xs font-mono text-violet-500 dark:text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded-md">#{{ $index + 1 }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-content-muted uppercase tracking-wider shrink-0">{{ __('generator.subject') }}</span>
                        <span x-show="!showOriginal" class="text-sm text-content-primary font-medium text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}">{{ $correctedSubject }}</span>
                        @if($wasSpam)
                        <span x-show="showOriginal" x-cloak class="text-sm font-medium text-red-500/80 line-through text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}">{{ $origSubject }}</span>
                        @endif
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-xs font-medium text-content-muted uppercase tracking-wider shrink-0 mt-0.5">{{ __('generator.to') }}</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($variantEmails as $ve)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-surface-bg border border-surface-border text-[11px] font-mono text-content-secondary group-hover:text-content-primary transition-colors">{{ $ve }}</span>
                            @endforeach
                            @if(count($variantEmails) > 1)
                            <span class="text-[11px] font-mono text-cyan-600 dark:text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded-md">{{ __('generator.recipients', ['count' => count($variantEmails)]) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Email Body --}}
            <div x-show="!showOriginal" class="px-6 py-6 border-b border-surface-border/30 bg-surface-bg/10">
                <pre class="whitespace-pre-wrap font-sans text-sm text-content-secondary leading-relaxed text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}" id="email-body-{{ $index }}" dir="auto">{{ $correctedBody }}</pre>
            </div>
            @if($wasSpam)
            <div x-show="showOriginal" x-cloak class="px-6 py-6 space-y-4">
                <div class="bg-red-500/[0.03] border border-red-500/10 rounded-xl p-4">
                    <pre class="whitespace-pre-wrap font-sans text-sm text-red-300/70 leading-relaxed text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}" dir="auto">{{ $origBody }}</pre>
                </div>
                <div class="bg-emerald-500/[0.03] border border-emerald-500/10 rounded-xl p-4">
                    <pre class="whitespace-pre-wrap font-sans text-sm text-emerald-300/70 leading-relaxed">{{ $correctedBody }}</pre>
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div class="bg-surface-bg/30 border-t border-surface-border px-6 py-4 flex flex-wrap items-center gap-3">
                {{-- Copy --}}
                <button @click="
                    const subject = @js($correctedSubject);
                    const body = document.getElementById('email-body-{{ $index }}').innerText;
                    navigator.clipboard.writeText('{{ __('generator.subject') }}: ' + subject + '\n\n' + body).then(() => {
                        copied = true; setTimeout(() => copied = false, 2000);
                    });
                "
                class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200"
                :class="copied ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-violet-500/10 text-violet-400 hover:bg-violet-500/20 border border-violet-500/30'">
                    <span x-text="copied ? '{{ __('generator.copied') }}' : '📋 {{ __('generator.btn_copy_email') }}'"></span>
                </button>

                {{-- Quick Campaign --}}
                @if($accounts->isNotEmpty() && $email->sending_status !== 'queued' && $email->sending_status !== 'sent')
                <div class="relative" x-data="{ showModal: false }">
                    <button @click="showModal = !showModal" @click.away="showModal = false" class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 text-white hover:opacity-90 shadow-lg shadow-cyan-500/20 transition-all">
                        {{ __('generator.quick_campaign') }}
                    </button>
                    <div x-show="showModal" x-transition class="absolute bottom-full left-0 mb-2 w-72 bg-surface-card border border-surface-border rounded-xl overflow-hidden shadow-2xl z-50">
                        <form action="{{ route('email.quick_campaign', $email->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="variant_index" value="{{ $index }}">
                            <div class="px-3 py-2 text-xs font-semibold text-content-muted bg-surface-bg border-b border-surface-border uppercase tracking-wide">
                                {{ __('generator.select_accounts_send_later') }}
                            </div>
                            <div class="p-3 space-y-2 max-h-48 overflow-y-auto">
                                @foreach($accounts as $acc)
                                <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-surface-bg transition-colors cursor-pointer">
                                    <input type="checkbox" name="account_ids[]" value="{{ $acc->id }}" checked class="w-4 h-4 text-cyan-500 bg-surface-bg border-surface-border rounded">
                                    <div class="min-w-0">
                                        <p class="text-xs font-medium text-content-primary truncate">{{ $acc->display_name }}</p>
                                        <p class="text-[10px] text-content-muted font-mono truncate">{{ $acc->email }}</p>
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            <div class="px-3 py-2 border-t border-surface-border">
                                <button type="submit" class="w-full px-3 py-2 text-xs font-semibold rounded-lg bg-gradient-to-r from-cyan-600 to-blue-600 text-white hover:opacity-90">
                                    {{ __('generator.btn_create_send_later') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @elseif($email->sending_status === 'queued')
                <span class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-amber-400 bg-amber-500/10 border border-amber-500/30 rounded-xl">{{ __('generator.campaign_created') }}</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Create Campaign from ALL Variants --}}
    @if(count($variantsToDisplay) > 1 && $accounts->isNotEmpty() && $email->sending_status !== 'queued')
    <div class="bg-gradient-to-br from-violet-500/5 to-fuchsia-500/5 border border-violet-500/20 backdrop-blur-sm rounded-2xl p-6" x-data="{ showAccounts: false }">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                    {{ __('generator.create_campaign_all_variants') }} ({{ count($variantsToDisplay) }} {{ trans_choice('generator.variant', count($variantsToDisplay)) }})
                </h3>
                <p class="text-xs text-content-muted mt-1">{{ __('generator.all_variants_desc') }}</p>
            </div>
            <button @click="showAccounts = !showAccounts" class="px-4 py-2 text-sm font-medium rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white hover:opacity-90 shadow-lg shadow-violet-500/20 transition-all">
                {{ __('generator.btn_create_multi_campaign') }}
            </button>
        </div>
        <div x-show="showAccounts" x-transition class="mt-4 border-t border-violet-500/20 pt-4">
            <form action="{{ route('email.campaign_from_variants', $email->id) }}" method="POST" class="space-y-3">
                @csrf
                <p class="text-xs text-content-muted">{{ __('generator.select_sending_accounts') }}</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach($accounts as $acc)
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-surface-border bg-surface-bg/20 cursor-pointer hover:bg-surface-bg has-[:checked]:border-violet-500/50 has-[:checked]:bg-violet-500/10 transition-all">
                        <input type="checkbox" name="account_ids[]" value="{{ $acc->id }}" checked class="w-4 h-4 text-violet-500 bg-surface-bg border-surface-border rounded">
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-content-primary truncate">{{ $acc->display_name }}</p>
                            <p class="text-[10px] text-content-muted truncate">{{ $acc->email }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                <button type="submit" class="w-full px-4 py-2.5 text-sm font-semibold rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white hover:opacity-90 transition-opacity">
                    {{ __('generator.btn_create_campaign_variants', ['count' => count($variantsToDisplay)]) }}
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- Metadata --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-surface-card border border-surface-border rounded-2xl p-5 text-center">
            <p class="text-xs text-content-muted uppercase tracking-wider mb-1">{{ __('generator.model') }}</p>
            <p class="text-sm font-mono text-cyan-600 dark:text-cyan-400">{{ $email->gemini_model }}</p>
        </div>
        <div class="bg-surface-card border border-surface-border rounded-2xl p-5 text-center">
            <p class="text-xs text-content-muted uppercase tracking-wider mb-1">{{ __('generator.tokens') }}</p>
            <p class="text-sm font-mono text-violet-600 dark:text-violet-400">{{ number_format($email->tokens_used ?? 0) }}</p>
        </div>
        <div class="bg-surface-card border border-surface-border rounded-2xl p-5 text-center">
            <p class="text-xs text-content-muted uppercase tracking-wider mb-1">{{ __('generator.time') }}</p>
            <p class="text-sm text-content-secondary font-mono">{{ number_format($email->generation_time_ms) }}ms</p>
        </div>
        <div class="bg-surface-card border border-surface-border rounded-2xl p-5 text-center">
            <p class="text-xs text-content-muted uppercase tracking-wider mb-1">{{ __('generator.tone') }}</p>
            <p class="text-sm text-fuchsia-600 dark:text-fuchsia-400 capitalize">{{ $email->tone }}</p>
        </div>
    </div>
    
    @if($email->target_website)
    <div class="bg-surface-card border border-surface-border rounded-2xl p-5">
        <p class="text-xs text-content-muted uppercase tracking-wider mb-1">{{ __('generator.prospect_website') }}</p>
        <p class="text-sm text-content-secondary font-mono text-left" dir="ltr">{{ $email->target_website }}</p>
    </div>
    @endif

    {{-- Prompt Used --}}
    <details class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden group">
        <summary class="px-6 py-4 cursor-pointer select-none flex items-center justify-between hover:bg-surface-bg transition-colors">
            <span class="flex items-center gap-2 text-sm font-medium text-content-muted">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" /></svg>
                {{ __('generator.view_prompt') }}
            </span>
        </summary>
                <div class="px-6 pb-6 border-t border-surface-border pt-4 space-y-4">
                    <div>
                        <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider mb-2 block">System Prompt (Rules & Persona)</span>
                        <pre class="whitespace-pre-wrap text-xs text-content-muted font-mono leading-relaxed bg-surface-bg/30 rounded-xl p-4 max-h-40 overflow-y-auto border border-surface-border/50">{{ $email->system_prompt ?? 'System prompt not recorded.' }}</pre>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider mb-2 block">User Prompt (Data & Guidelines)</span>
                        <pre class="whitespace-pre-wrap text-xs text-content-muted font-mono leading-relaxed bg-surface-bg/30 rounded-xl p-4 max-h-40 overflow-y-auto border border-surface-border/50">{{ $email->full_prompt_sent }}</pre>
                    </div>
                </div>
    </details>
</div>
@endsection
