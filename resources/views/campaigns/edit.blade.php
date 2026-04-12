@extends('layouts.app')
@section('title', __('campaign.edit_title') . ' — ' . $campaign->name . ' — ColdForge')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-content-primary">{{ __('campaign.edit_title') }}</h1>
            <p class="text-sm text-content-muted mt-1">{{ $campaign->name }}</p>
        </div>
        <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm text-content-muted hover:text-content-primary transition">← {{ __('campaign.details') }}</a>
    </div>

    @php
        $campaignAccounts = $campaign->warming_account_ids ?? [];
        $recipientsList = is_array($campaign->recipients) ? implode("\n", $campaign->recipients) : '';
    @endphp

    <form action="{{ route('campaigns.update', $campaign) }}" method="POST" class="space-y-6" x-data="{ autoFollowup: {{ $campaign->auto_followup ? 'true' : 'false' }} }">
        @csrf
        @method('PUT')

        {{-- Campaign Name --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                📝 {{ __('campaign.campaign_info') }}
            </h2>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-content-muted">{{ __('campaign.campaign_name') }}</label>
                <input type="text" name="name" required value="{{ old('name', $campaign->name) }}"
                       class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30 transition-all">
                @error('name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Email Content --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                ✉️ {{ __('campaign.email_content') }}
            </h2>

            @if($campaign->email_variants)
                @foreach($campaign->email_variants as $vi => $v)
                <div class="bg-surface-bg/30 border border-surface-border rounded-xl p-3 space-y-1">
                    <span class="text-xs font-mono text-violet-500 dark:text-violet-400">{{ __('generator.variant') }} #{{ $vi + 1 }}</span>
                    <p class="text-sm text-content-primary font-{{ app()->getLocale() == 'en' ? 'medium' : 'bold' }} text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}">{{ $v['subject'] ?? '' }}</p>
                    <p class="text-xs text-content-muted line-clamp-2 text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}">{{ Str::limit($v['body'] ?? '', 100) }}</p>
                </div>
                @endforeach
            @else
                <div class="space-y-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-content-muted">{{ __('campaign.subject') }}</label>
                        <input type="text" name="custom_subject" value="{{ old('custom_subject', $campaign->custom_subject) }}"
                               class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30 transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-content-muted">{{ __('campaign.email_body') }}</label>
                        <textarea name="custom_body" rows="8"
                                  class="w-full px-4 py-3 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30 transition-all font-mono leading-relaxed resize-y text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}" dir="ltr">{{ old('custom_body', $campaign->custom_body) }}</textarea>
                    </div>
                </div>
            @endif
        </div>

        {{-- Recipients --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">👥 {{ __('campaign.target_recipients') }}</h2>
            <div class="space-y-1.5">
                <textarea name="recipients_text" rows="5" required
                          class="w-full px-4 py-3 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30 transition-all font-mono leading-relaxed resize-y text-left" dir="ltr">{{ old('recipients_text', $recipientsList) }}</textarea>
                @error('recipients_text') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Scheduling --}}
        <div class="bg-gradient-to-br from-cyan-500/5 to-blue-500/5 border border-cyan-500/20 backdrop-blur-sm rounded-2xl p-6 space-y-5">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">📅 {{ __('campaign.schedule_send_later') }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Accounts --}}
                <div class="space-y-1.5 sm:col-span-2">
                    <label class="text-xs font-medium text-cyan-600 dark:text-cyan-400">{{ __('campaign.sending_accounts') }}</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
                        @foreach($accounts as $acc)
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-surface-border bg-surface-bg/20 cursor-pointer hover:bg-surface-bg transition-all has-[:checked]:border-cyan-500/50 has-[:checked]:bg-cyan-500/10">
                            <input type="checkbox" name="warming_account_ids[]" value="{{ $acc->id }}"
                                   class="w-4 h-4 text-cyan-500 bg-surface-bg border-surface-border rounded focus:ring-cyan-500 focus:ring-2 focus:ring-offset-surface-bg"
                                   @if(in_array($acc->id, $campaignAccounts)) checked @endif>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-content-primary truncate">{{ $acc->display_name }}</p>
                                <p class="text-[10px] text-content-muted truncate" dir="ltr">{{ $acc->email }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('warming_account_ids') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Timezone --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.timezone') }}</label>
                    <select name="timezone" required class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30" dir="ltr">
                        <option value="Asia/Riyadh" @if(($campaign->timezone ?? '') === 'Asia/Riyadh') selected @endif>Riyadh (Asia)</option>
                        <option value="Asia/Dubai" @if(($campaign->timezone ?? '') === 'Asia/Dubai') selected @endif>Dubai (Asia)</option>
                        <option value="Asia/Baghdad" @if(($campaign->timezone ?? '') === 'Asia/Baghdad') selected @endif>Baghdad (Asia)</option>
                        <option value="Africa/Cairo" @if(($campaign->timezone ?? '') === 'Africa/Cairo') selected @endif>Cairo (Africa)</option>
                        <option value="Europe/London" @if(($campaign->timezone ?? '') === 'Europe/London') selected @endif>London (Europe)</option>
                        <option value="America/New_York" @if(($campaign->timezone ?? '') === 'America/New_York') selected @endif>New York (America)</option>
                        <option value="America/Chicago" @if(($campaign->timezone ?? '') === 'America/Chicago') selected @endif>Chicago (America)</option>
                        <option value="America/Los_Angeles" @if(($campaign->timezone ?? '') === 'America/Los_Angeles') selected @endif>Los Angeles (America)</option>
                        <option value="America/Detroit" @if(($campaign->timezone ?? '') === 'America/Detroit') selected @endif>Detroit (America)</option>
                        <option value="Europe/Berlin" @if(($campaign->timezone ?? '') === 'Europe/Berlin') selected @endif>Berlin (Europe)</option>
                        <option value="Europe/Istanbul" @if(($campaign->timezone ?? '') === 'Europe/Istanbul') selected @endif>Istanbul (Europe)</option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.start_send_date') }}</label>
                    <input type="date" name="send_start_date" required value="{{ old('send_start_date', optional($campaign->send_start_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.start_send_time') }}</label>
                    <input type="time" name="send_start_time" required value="{{ old('send_start_time', $campaign->send_start_time ?? '09:00') }}"
                           class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.end_send_time') }}</label>
                    <input type="time" name="send_end_time" required value="{{ old('send_end_time', $campaign->send_end_time ?? '17:00') }}"
                           class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                </div>

                <div class="sm:col-span-2 grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-content-muted">{{ __('campaign.min_delay') }}</label>
                        <input type="number" name="min_delay_minutes" required value="{{ old('min_delay_minutes', $campaign->min_delay_minutes ?? 5) }}" min="2" max="60"
                               class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-content-muted">{{ __('campaign.max_delay') }}</label>
                        <input type="number" name="max_delay_minutes" required value="{{ old('max_delay_minutes', $campaign->max_delay_minutes ?? 10) }}" min="2" max="120"
                               class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30">
                    </div>
                </div>
            </div>
        </div>

        {{-- Follow-Up --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-content-primary">🔄 {{ __('campaign.follow_up_settings') }}</h2>
                <label class="flex items-center gap-2 cursor-pointer">
                    <span class="text-[11px] text-content-muted">{{ __('campaign.auto_follow_up') }}</span>
                    <input type="checkbox" name="auto_followup" value="1" x-model="autoFollowup"
                           class="w-4 h-4 rounded bg-surface-bg border-surface-border text-amber-500 focus:ring-amber-500/30">
                </label>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.wait_days') }}</label>
                    <input type="number" name="followup_wait_days" value="{{ old('followup_wait_days', $campaign->followup_wait_days ?? 3) }}" min="1" max="30"
                           class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.max_follow_ups') }}</label>
                    <input type="number" name="max_followups" value="{{ old('max_followups', $campaign->max_followups ?? 3) }}" min="1" max="5"
                           class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm text-content-muted hover:text-content-primary transition">{{ __('app.cancel') }}</a>
            <button type="submit" class="px-6 py-3 text-sm font-semibold rounded-xl bg-gradient-to-r from-amber-600 to-orange-500 text-white hover:opacity-90 transition-opacity flex items-center gap-2 shadow-lg shadow-amber-500/20">
                💾 {{ __('campaign.update_campaign') }}
            </button>
        </div>
    </form>
</div>
@endsection
