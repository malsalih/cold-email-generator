@extends('layouts.app')
@section('title', __('campaign.send_followup') . ' — ' . $campaign->name . ' — ColdForge')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-content-primary">🔄 {{ __('campaign.send_followup') }}</h1>
            <p class="text-sm text-content-muted mt-1">{{ __('campaign.parent_campaign_name') }} {{ $campaign->name }}</p>
        </div>
        <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm text-content-muted hover:text-content-primary transition">← {{ __('campaign.details') }}</a>
    </div>

    @if($errors->any())
    <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-6">
        <ul class="list-disc list-inside text-sm text-red-500">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if(empty($availableRecipients))
    <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-6 text-center">
        <p class="text-amber-600 dark:text-amber-400 font-medium">{{ __('campaign.no_recipients_followup') }}</p>
        <p class="text-xs text-amber-600/60 dark:text-amber-200/60 mt-1">{{ __('campaign.no_recipients_desc') }}</p>
    </div>
    @else
    <form action="{{ route('campaigns.follow_up.store', $campaign) }}" method="POST" class="space-y-6" x-data="{
        selectAll: true,
        selected: @json($availableRecipients),
        allRecipients: @json($availableRecipients),
        toggleAll() {
            if (this.selectAll) {
                this.selected = [...this.allRecipients];
            } else {
                this.selected = [];
            }
        }
    }">
        @csrf

        {{-- Recipients Selection --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                    👥 {{ __('campaign.select_recipients_followup') }}
                </h2>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="selectAll" @change="toggleAll()"
                           class="w-4 h-4 rounded bg-surface-bg border-surface-border text-amber-500 focus:ring-amber-500/30">
                    <span class="text-xs text-content-muted">{{ __('campaign.select_all') }} (<span x-text="selected.length"></span>/{{ count($availableRecipients) }})</span>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[300px] overflow-y-auto w-full">
                @foreach($availableRecipients as $email)
                <label class="flex items-center gap-3 p-2.5 bg-surface-bg/20 rounded-lg border border-surface-border hover:border-amber-500/20 transition cursor-pointer">
                    <input type="checkbox" name="selected_recipients[]" value="{{ $email }}"
                           x-model="selected"
                           class="w-4 h-4 rounded bg-surface-bg border-surface-border text-amber-500 focus:ring-amber-500/30">
                    <span class="text-xs font-mono text-content-primary truncate" dir="ltr">{{ $email }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Follow-Up Content --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4" x-data="{ aiLoading: false, aiError: '' }">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">✍️ {{ __('campaign.followup_content') }}</h2>
                <button type="button" x-data @click="
                    aiLoading = true; aiError = '';
                    const btn = $el;
                    fetch('{{ route('campaigns.generate_followup_email', $campaign) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    })
                    .then(r => r.json())
                    .then(data => {
                        aiLoading = false;
                        if (data.success) {
                            document.querySelector('input[name=custom_subject]').value = data.subject;
                            document.querySelector('textarea[name=custom_body]').value = data.body;
                            $refs.promptContainer.innerHTML = data.prompt_sent;
                            $refs.promptSection.classList.remove('hidden');
                        } else {
                            aiError = data.error || '{{ __('campaign.error_generation_failed') }}';
                        }
                    })
                    .catch(e => { aiLoading = false; aiError = e.message; });
                "
                class="flex items-center gap-2 px-4 py-2 text-xs font-medium rounded-xl transition-all duration-200"
                :class="aiLoading ? 'bg-violet-500/20 text-violet-400 border border-violet-500/30 cursor-wait' : 'bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white hover:opacity-90 shadow-lg shadow-violet-500/20'"
                :disabled="aiLoading">
                    <template x-if="!aiLoading">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                            {{ __('campaign.generate_ai') }}
                        </span>
                    </template>
                    <template x-if="aiLoading">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            {{ __('campaign.generating') }}
                        </span>
                    </template>
                </button>
            </div>
            <template x-if="aiError">
                <div class="px-3 py-2 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-500" x-text="aiError"></div>
            </template>

            <details x-ref="promptSection" class="hidden bg-surface-bg border border-surface-border rounded-xl overflow-hidden group">
                <summary class="px-4 py-3 cursor-pointer select-none flex items-center justify-between hover:bg-surface-bg/50 transition-colors">
                    <span class="text-xs font-medium text-content-muted flex items-center gap-2">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" /></svg>
                        View Prompt Sent
                    </span>
                </summary>
                <div class="px-4 pb-4 border-t border-surface-border pt-2">
                    <pre x-ref="promptContainer" class="whitespace-pre-wrap text-[10px] text-content-muted font-mono leading-relaxed bg-surface-card/50 rounded-lg p-3 max-h-40 overflow-y-auto border border-surface-border/50"></pre>
                </div>
            </details>
            <p class="text-[11px] text-content-muted">{{ __('campaign.ai_hint') }}</p>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-content-muted">{{ __('campaign.subject') }}</label>
                <input type="text" name="custom_subject" required value="{{ old('custom_subject', $campaign->getSubject()) }}"
                       class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 transition-all text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}" dir="ltr">
            </div>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-content-muted">{{ __('campaign.body') }}</label>
                <textarea name="custom_body" rows="8" required
                          class="w-full px-4 py-3 bg-surface-bg border border-surface-border rounded-xl text-content-primary placeholder-content-muted text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 transition-all font-mono leading-relaxed resize-y text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}" dir="ltr">{{ old('custom_body') }}</textarea>
            </div>
        </div>

        {{-- Scheduling --}}
        <div class="bg-gradient-to-br from-amber-500/5 to-orange-500/5 border border-amber-500/20 backdrop-blur-sm rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">📅 {{ __('campaign.schedule_followup') }}</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.start_send_date') }}</label>
                    <input type="date" name="send_start_date" required value="{{ now()->addDays($campaign->followup_wait_days)->format('Y-m-d') }}"
                           class="w-full px-3 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.start_send_time') }}</label>
                    <input type="time" name="send_start_time" required value="{{ $campaign->send_start_time }}"
                           class="w-full px-3 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.end_send_time') }}</label>
                    <input type="time" name="send_end_time" required value="{{ $campaign->send_end_time }}"
                           class="w-full px-3 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                </div>
                <div class="space-y-1.5 grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-medium text-content-muted">{{ __('campaign.min_delay') }}</label>
                        <input type="number" name="min_delay_minutes" required value="{{ $campaign->min_delay_minutes }}" min="2" max="60"
                               class="w-full px-2 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-1 focus:ring-amber-500/30">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-content-muted">{{ __('campaign.max_delay') }}</label>
                        <input type="number" name="max_delay_minutes" required value="{{ $campaign->max_delay_minutes }}" min="2" max="120"
                               class="w-full px-2 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-1 focus:ring-amber-500/30">
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm text-content-muted hover:text-content-primary transition">{{ __('app.cancel') }}</a>
            <button type="submit" class="px-6 py-3 text-sm font-semibold rounded-xl bg-gradient-to-r from-amber-600 to-orange-500 text-white hover:opacity-90 transition-opacity flex items-center gap-2 shadow-lg shadow-amber-500/20">
                🔄 {{ __('campaign.create_followup_num') }}{{ $nextFollowUpNumber }}
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
