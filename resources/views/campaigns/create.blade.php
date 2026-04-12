@extends('layouts.app')
@section('title', __('campaign.create_title') . ' — ColdForge')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-content-primary">{{ __('campaign.create_title') }}</h1>
            <p class="text-sm text-content-muted mt-1">{{ __('campaign.create_desc') }}</p>
        </div>
        <a href="{{ route('campaigns.index') }}" class="text-sm text-content-muted hover:text-content-primary transition">← {{ __('app.campaigns') }}</a>
    </div>

    @php
        $pName = old('name', $prefill['name'] ?? '');
        $pRecipients = old('recipients_text', $prefill['recipients'] ?? '');
        $pSubject = old('custom_subject', $prefill['subject'] ?? '');
        $pBody = old('custom_body', $prefill['body'] ?? '');
        $pAccounts = old('warming_account_ids', $prefill['accounts'] ?? []);
        $isMultiVariant = old('multi_variant', $prefill['multi_variant'] ?? false);
        $hasAiSource = $preselectedEmail ? true : false;
    @endphp

    <form action="{{ route('campaigns.store') }}" method="POST" class="space-y-6" x-data="{
        source: '{{ $hasAiSource ? 'ai' : ($pSubject ? 'custom' : 'custom') }}',
        selectedEmailId: '{{ $preselectedEmail->id ?? '' }}',
        autoFollowup: false,
    }">
        @csrf
        @if($isMultiVariant)
        <input type="hidden" name="multi_variant" value="1">
        @endif

        {{-- Campaign Name --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-violet-500 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                {{ __('campaign.campaign_info') }}
            </h2>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-content-muted">{{ __('campaign.campaign_name') }}</label>
                <input type="text" name="name" required value="{{ $pName }}" placeholder="{{ __('campaign.campaign_name_ph') }}"
                       class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary placeholder-content-muted text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30 transition-all">
                @error('name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Email Content Source --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-violet-500 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                {{ __('campaign.email_content') }}
            </h2>

            @if($isMultiVariant && $preselectedEmail)
                {{-- Multi-variant: show all variants preview --}}
                <div class="space-y-3">
                    @foreach($preselectedEmail->generated_variants as $vi => $v)
                    <div class="bg-surface-bg/30 border border-surface-border rounded-xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono text-violet-500 dark:text-violet-400">{{ __('generator.variant') }} #{{ $vi + 1 }}</span>
                            <span class="text-[10px] text-cyan-600 dark:text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded" dir="ltr">{{ count($v['target_emails'] ?? []) }} {{ __('campaign.recipients') }}</span>
                        </div>
                        <p class="text-sm text-content-primary font-medium">{{ $v['subject'] ?? '' }}</p>
                        <p class="text-xs text-content-muted line-clamp-2">{{ Str::limit($v['body'] ?? '', 120) }}</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach(($v['target_emails'] ?? []) as $te)
                            <span class="text-[10px] font-mono text-content-muted bg-surface-bg border border-surface-border px-1.5 py-0.5 rounded">{{ $te }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                    <input type="hidden" name="generated_email_id" value="{{ $preselectedEmail->id }}">
                </div>
            @else
                <div class="flex gap-2">
                    <button type="button" @click="source = 'ai'" :class="source === 'ai' ? 'bg-violet-500/20 text-violet-600 dark:text-violet-400 border-violet-500/30' : 'bg-surface-bg text-content-muted border-surface-border'" class="px-4 py-2 text-xs font-medium rounded-lg border transition-all">
                        {{ __('campaign.from_ai') }}
                    </button>
                    <button type="button" @click="source = 'custom'" :class="source === 'custom' ? 'bg-violet-500/20 text-violet-600 dark:text-violet-400 border-violet-500/30' : 'bg-surface-bg text-content-muted border-surface-border'" class="px-4 py-2 text-xs font-medium rounded-lg border transition-all">
                        {{ __('campaign.custom_text') }}
                    </button>
                </div>

                <div x-show="source === 'ai'" x-transition class="space-y-3">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.choose_ai_email') }}</label>
                    <select name="generated_email_id" x-model="selectedEmailId"
                            class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30">
                        <option value="">—</option>
                        @foreach($generatedEmails as $gen)
                            <option value="{{ $gen->id }}" {{ ($preselectedEmail && $preselectedEmail->id == $gen->id) ? 'selected' : '' }}>
                                #{{ $gen->id }} — {{ Str::limit($gen->generated_subject, 60) }} ({{ $gen->owned_domain }})
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="variant_index" value="{{ $prefill['variant_index'] ?? 0 }}">
                </div>

                <div x-show="source === 'custom'" x-transition class="space-y-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-content-muted">{{ __('campaign.subject') }}</label>
                        <input type="text" name="custom_subject" value="{{ $pSubject }}"
                               class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary placeholder-content-muted text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30 transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-content-muted">{{ __('campaign.email_body') }}</label>
                        <textarea name="custom_body" rows="8"
                                  class="w-full px-4 py-3 text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} bg-surface-bg border border-surface-border rounded-xl text-content-primary placeholder-content-muted text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30 transition-all font-mono leading-relaxed resize-y" dir="ltr">{{ $pBody }}</textarea>
                    </div>
                </div>
            @endif
        </div>

        {{-- Recipients --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-violet-500 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                {{ __('campaign.target_recipients') }}
            </h2>
            <div class="space-y-1.5">
                <textarea name="recipients_text" rows="5" required placeholder="client1@example.com, client2@example.com&#10;client3@example.com"
                          class="w-full px-4 py-3 bg-surface-bg border border-surface-border rounded-xl text-content-primary placeholder-content-muted text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/30 transition-all font-mono leading-relaxed resize-y text-left" dir="ltr">{{ $pRecipients }}</textarea>
                @error('recipients_text') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                <p class="text-[11px] text-content-muted">{{ __('campaign.recipients_hint') }}</p>
            </div>
        </div>

        {{-- Send Later Scheduling --}}
        <div class="bg-gradient-to-br from-cyan-500/5 to-blue-500/5 border border-cyan-500/20 backdrop-blur-sm rounded-2xl p-6 space-y-5">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                {{ __('campaign.schedule_send_later') }}
            </h2>
            <p class="text-xs text-content-muted">{{ __('campaign.schedule_desc') }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Accounts --}}
                <div class="space-y-1.5 sm:col-span-2">
                    <label class="text-xs font-medium text-cyan-600 dark:text-cyan-400">{{ __('campaign.sending_accounts') }}</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
                        @foreach($accounts as $acc)
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-surface-border bg-surface-bg/20 cursor-pointer hover:bg-surface-bg transition-all has-[:checked]:border-cyan-500/50 has-[:checked]:bg-cyan-500/10">
                                <input type="checkbox" name="warming_account_ids[]" value="{{ $acc->id }}" 
                                       class="w-4 h-4 text-cyan-500 bg-surface-bg border-surface-border rounded focus:ring-cyan-500 focus:ring-2 focus:ring-offset-surface-bg"
                                       @if(in_array($acc->id, $pAccounts)) checked @endif>
                                <div class="flex-1 min-w-0">
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
                    <select name="timezone" required
                            class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30" dir="ltr">
                        <option value="Asia/Riyadh">(GMT+03:00) Riyadh</option>
                        <option value="Asia/Dubai">(GMT+04:00) Dubai</option>
                        <option value="Asia/Baghdad">(GMT+03:00) Baghdad</option>
                        <option value="Africa/Cairo">(GMT+02:00) Cairo</option>
                        <option value="Europe/London">(GMT+00:00) London</option>
                        <option value="America/New_York">(GMT-05:00) New York</option>
                        <option value="America/Chicago">(GMT-06:00) Chicago</option>
                        <option value="America/Los_Angeles">(GMT-08:00) Los Angeles</option>
                        <option value="America/Detroit">(GMT-04:00) Detroit</option>
                        <option value="Europe/Berlin">(GMT+01:00) Berlin</option>
                        <option value="Europe/Istanbul">(GMT+03:00) Istanbul</option>
                    </select>
                </div>

                {{-- Start Date --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.start_send_date') }}</label>
                    <input type="date" name="send_start_date" required value="{{ old('send_start_date', now()->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30 transition-all">
                </div>

                {{-- Start Time --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.start_send_time') }}</label>
                    <input type="time" name="send_start_time" required value="{{ old('send_start_time', '09:00') }}"
                           class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30 transition-all">
                </div>

                {{-- End Time --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.end_send_time') }}</label>
                    <input type="time" name="send_end_time" required value="{{ old('send_end_time', '17:00') }}"
                           class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30 transition-all">
                </div>

                <div class="sm:col-span-2 grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-content-muted">{{ __('campaign.min_delay') }}</label>
                        <input type="number" name="min_delay_minutes" required value="{{ old('min_delay_minutes', 5) }}" min="2" max="60"
                               class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30 transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-content-muted">{{ __('campaign.max_delay') }}</label>
                        <input type="number" name="max_delay_minutes" required value="{{ old('max_delay_minutes', 10) }}" min="2" max="120"
                               class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/30 transition-all">
                    </div>
                </div>
            </div>
        </div>

        {{-- Follow-Up Settings --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                    🔄 {{ __('campaign.follow_up_settings') }}
                </h2>
                <label class="flex items-center gap-2 cursor-pointer">
                    <span class="text-[11px] text-content-muted">{{ __('campaign.auto_follow_up') }}</span>
                    <input type="checkbox" name="auto_followup" value="1" x-model="autoFollowup"
                           class="w-4 h-4 rounded bg-surface-bg border-surface-border text-amber-500 focus:ring-amber-500/30">
                </label>
            </div>
            <p class="text-xs text-content-muted">{{ __('campaign.follow_up_desc') }}</p>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.wait_days') }}</label>
                    <input type="number" name="followup_wait_days" value="{{ old('followup_wait_days', 3) }}" min="1" max="30"
                           class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-content-muted">{{ __('campaign.max_follow_ups') }}</label>
                    <input type="number" name="max_followups" value="{{ old('max_followups', 3) }}" min="1" max="5"
                           class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 transition-all">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('campaigns.index') }}" class="text-sm text-content-muted hover:text-content-primary transition">{{ __('app.cancel') }}</a>
            <button type="submit" class="px-6 py-3 text-sm font-semibold rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 text-white hover:opacity-90 transition-opacity flex items-center gap-2 shadow-lg shadow-cyan-500/20">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                {{ __('campaign.create_and_schedule') }}
            </button>
        </div>
    </form>
</div>
@endsection
