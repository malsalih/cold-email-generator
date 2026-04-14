@extends('layouts.app')

@section('title', $email->generated_subject . ' — ColdForge')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    {{-- Back & Actions --}}
    <div class="flex items-center justify-between">
        <a href="{{ route('email.history') }}"
           class="flex items-center gap-2 text-sm text-content-muted hover:text-content-primary transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
            {{ __('generator.back_to_history') }}
        </a>
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
                        class="flex items-center gap-2 px-3 py-2 text-xs font-medium text-red-500 hover:text-red-400 bg-red-500/5 hover:bg-red-500/10 border border-red-500/20 hover:border-red-500/30 rounded-lg transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    {{ __('generator.btn_delete_record') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Info Banner --}}
    <div class="bg-surface-card border border-surface-border rounded-2xl p-5">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-[11px] text-content-muted uppercase tracking-wider">{{ __('generator.selling_domain') }}</p>
                <p class="text-sm font-mono text-violet-500 dark:text-violet-400 mt-1">{{ $email->owned_domain }}</p>
            </div>
            <div>
                <p class="text-[11px] text-content-muted uppercase tracking-wider">{{ __('generator.tone') }}</p>
                <p class="text-sm text-content-primary capitalize mt-1">{{ $email->tone }}</p>
            </div>
            <div>
                <p class="text-[11px] text-content-muted uppercase tracking-wider">{{ __('generator.generated_at') }}</p>
                <p class="text-sm text-content-primary mt-1">{{ $email->created_at->format('M d, Y H:i') }}</p>
            </div>
            <div>
                <p class="text-[11px] text-content-muted uppercase tracking-wider">{{ __('generator.generation_time') }}</p>
                <p class="text-sm text-content-primary mt-1">{{ number_format($email->generation_time_ms) }}ms</p>
            </div>
        </div>
        @if($email->target_website || $email->product_service)
        <div class="mt-4 pt-4 border-t border-surface-border grid grid-cols-1 sm:grid-cols-2 gap-4">
            @if($email->target_website)
            <div>
                <p class="text-[11px] text-content-muted uppercase tracking-wider">{{ __('generator.prospect_website') }}</p>
                <p class="text-sm font-mono text-content-primary mt-1">{{ $email->target_website }}</p>
            </div>
            @endif
            @if($email->product_service)
            <div>
                <p class="text-[11px] text-content-muted uppercase tracking-wider">{{ __('generator.domain_niche') }}</p>
                <p class="text-sm text-content-primary mt-1">{{ $email->product_service }}</p>
            </div>
            @endif
        </div>
        @endif
    </div>

    @php
        $variantsToDisplay = is_array($email->generated_variants) ? $email->generated_variants : [
            [
                'target_email' => 'General Template',
                'subject' => $email->generated_subject,
                'body' => $email->generated_body
            ]
        ];
    @endphp

    <div class="space-y-6">
        @foreach($variantsToDisplay as $index => $variant)
        {{-- Email Preview Card --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden" x-data="{ copied: false }">
            <div class="bg-surface-bg/50 border-b border-surface-border px-6 py-4">
                <div class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-content-muted uppercase tracking-wider shrink-0">{{ __('generator.subject') }}</span>
                        <span class="text-sm text-content-primary font-medium">{{ $variant['subject'] ?? 'No Subject' }}</span>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-xs font-medium text-content-muted uppercase tracking-wider shrink-0 mt-0.5">{{ __('generator.to') }}</span>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(($variant['target_emails'] ?? [$variant['target_email'] ?? 'Unknown']) as $ve)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-surface-bg border border-surface-border text-[11px] font-mono text-content-secondary group-hover:text-content-primary transition-colors text-wrap break-all">{{ $ve }}</span>
                            @endforeach
                            @if(count($variant['target_emails'] ?? []) > 1)
                            <span class="text-[10px] font-mono text-cyan-600 dark:text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded-md">{{ __('generator.recipients', ['count' => count($variant['target_emails'])]) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="px-6 py-6 border-b border-surface-border/30 bg-surface-bg/10">
                <pre class="whitespace-pre-wrap font-sans text-sm text-content-secondary leading-relaxed bg-transparent border-0 p-0 m-0" id="email-body-{{ $index }}">{{ $variant['body'] ?? '' }}</pre>
            </div>
            <div class="bg-surface-bg/30 border-t border-surface-border px-6 py-4 flex flex-wrap items-center gap-3">
                <button @click="
                    const subject = @js($variant['subject'] ?? '');
                    const body = document.getElementById('email-body-{{ $index }}').innerText;
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
                            {{ __('generator.btn_copy_full') }}
                        </span>
                    </template>
                    <template x-if="copied">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('generator.copied') }}
                        </span>
                    </template>
                </button>

                @if($accounts->isEmpty())
                    <a href="{{ route('warming.accounts') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-orange-600 dark:text-orange-400 bg-orange-500/10 border border-orange-500/20 rounded-xl hover:bg-orange-500/20 transition-all">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        {{ __('generator.btn_add_sending_account') }}
                    </a>
                @else
                    @if($email->sending_status === 'sent')
                        <span class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                            {{ __('generator.sent_successfully') }}
                        </span>
                    @elseif($email->sending_status === 'queued' || $email->sending_status === 'sending')
                        <span class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium text-amber-600 dark:text-amber-400 bg-amber-500/10 border border-amber-500/30 rounded-xl">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                            {{ __('generator.sending_queued') }}
                        </span>
                    @else
                        {{-- Quick Campaign Button --}}
                        <div class="relative" x-data="{ showModal: false }">
                            <button @click="showModal = !showModal" @click.away="showModal = false" class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 bg-gradient-to-r from-cyan-600 to-blue-600 text-white hover:opacity-90 shadow-lg shadow-cyan-500/20">
                                {{ __('generator.quick_campaign') }}
                            </button>
                            <div x-show="showModal" x-transition class="absolute bottom-full left-0 mb-2 w-72 bg-surface-card border border-surface-border rounded-xl overflow-hidden shadow-2xl z-50">
                                <form action="{{ route('email.quick_campaign', $email->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="variant_index" value="{{ $index }}">
                                    <div class="px-3 py-2 text-xs font-semibold text-content-muted bg-surface-bg border-b border-surface-border uppercase tracking-wider">
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
                    @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- User Instructions --}}
    <details class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden group">
        <summary class="px-6 py-4 cursor-pointer select-none flex items-center justify-between hover:bg-surface-bg transition-colors">
            <span class="flex items-center gap-2 text-sm font-medium text-content-muted">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                {{ __('generator.original_instructions') }}
            </span>
            <svg class="w-4 h-4 text-content-muted opacity-50 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </summary>
        <div class="px-6 pb-6 border-t border-surface-border pt-4">
            <p class="text-sm text-content-secondary leading-relaxed">{{ $email->user_instructions }}</p>
        </div>
    </details>

    {{-- Prompt Sent --}}
    <details class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden group">
        <summary class="px-6 py-4 cursor-pointer select-none flex items-center justify-between hover:bg-surface-bg transition-colors">
            <span class="flex items-center gap-2 text-sm font-medium text-content-muted">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" /></svg>
                {{ __('generator.view_prompt') }}
            </span>
            <svg class="w-4 h-4 text-content-muted opacity-50 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
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
