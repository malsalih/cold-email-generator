@extends('layouts.app')
@section('title', $campaign->name . ' — ColdForge')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-content-primary">{{ $campaign->name }}</h1>
                @php
                    $statusClasses = [
                        'draft' => 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
                        'scheduled' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
                        'running' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                        'paused' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                        'completed' => 'bg-green-500/10 text-green-400 border-green-500/20',
                        'failed' => 'bg-red-500/10 text-red-400 border-red-500/20',
                    ];
                @endphp
                <span class="px-3 py-1 text-xs font-medium rounded-full {{ $statusClasses[$campaign->status] ?? $statusClasses['draft'] }}">
                    {{ __('campaign.' . $campaign->status) ?? $campaign->status_label }}
                </span>
                @if($campaign->is_follow_up)
                <span class="px-2 py-1 text-[10px] font-medium rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    {{ __('campaign.follow_up') }} #{{ $campaign->followup_number }}
                </span>
                @endif
            </div>
            @if($campaign->accounts && $campaign->accounts->count() > 0)
            <p class="text-sm text-content-muted mt-1 max-w-xl text-wrap leading-relaxed">
                📧 {{ $campaign->accounts->pluck('email')->join(app()->getLocale() == 'ar' ? '، ' : ', ') }}<br>
                🌍 {{ $campaign->timezone }}
            </p>
            @endif
        </div>
        <div class="flex flex-col items-end gap-2">
            <a href="{{ route('campaigns.index') }}" class="text-sm text-content-muted hover:text-content-primary transition">← {{ __('campaign.details') }}</a>
            @if($campaign->is_follow_up && $campaign->parentCampaign)
                <a href="{{ route('campaigns.show', $campaign->parentCampaign) }}" class="text-xs font-medium text-amber-500/80 hover:text-amber-400 transition flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19.5v-15m0 0l-6.75 6.75M12 4.5l6.75 6.75" /></svg>
                    {{ __('campaign.parent_campaign') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex flex-wrap gap-3">
        @if(in_array($campaign->status, ['draft', 'scheduled']))
        <form action="{{ route('campaigns.launch', $campaign) }}" method="POST">
            @csrf
            <button type="submit" class="px-5 py-2.5 text-sm font-bold rounded-xl bg-gradient-to-r from-emerald-600 to-green-500 text-white hover:from-emerald-500 hover:to-green-400 transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                🚀 {{ __('campaign.running') }} (Send Later)
            </button>
        </form>
        <a href="{{ route('campaigns.edit', $campaign) }}" class="px-4 py-2.5 text-sm font-medium rounded-xl bg-violet-500/10 text-violet-400 border border-violet-500/20 hover:bg-violet-500/20 transition flex items-center gap-2">
            ✏️ {{ __('campaign.edit_title') }}
        </a>
        @endif

        @if($campaign->status === 'running')
        <form action="{{ route('campaigns.pause', $campaign) }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2.5 text-sm font-medium rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition">⏸ {{ __('campaign.paused') }}</button>
        </form>
        @endif

        @if($campaign->status === 'paused')
        <form action="{{ route('campaigns.resume', $campaign) }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2.5 text-sm font-medium rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition">▶️ {{ __('campaign.resume') }}</button>
        </form>
        @endif

        @if($campaign->status === 'completed' && $campaign->followUps->count() < $campaign->max_followups)
        <a href="{{ route('campaigns.follow_up', $campaign) }}" class="px-5 py-2.5 text-sm font-bold rounded-xl bg-gradient-to-r from-amber-600 to-orange-500 text-white hover:from-amber-500 hover:to-orange-400 transition-all shadow-lg shadow-amber-500/20 flex items-center gap-2">
            🔄 {{ __('campaign.send_followup') }}
        </a>
        @endif

        <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('{{ __('campaign.confirm_delete') }}')">
            @csrf @method('DELETE')
            <button type="submit" class="px-4 py-2.5 text-sm font-medium rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition">🗑 {{ __('campaign.delete') }}</button>
        </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-surface-card border border-surface-border rounded-xl p-4 text-center group hover:border-orange-500/30 transition-colors">
            <p class="text-2xl font-bold text-content-primary">{{ $campaign->total_recipients }}</p>
            <p class="text-xs text-content-muted">{{ __('campaign.total') }}</p>
        </div>
        <div class="bg-surface-card border border-surface-border rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-emerald-500 dark:text-emerald-400">{{ $campaign->sent_count }}</p>
            <p class="text-xs text-content-muted">{{ __('campaign.sent') }}</p>
        </div>
        <div class="bg-surface-card border border-surface-border rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-500 dark:text-red-400">{{ $campaign->failed_count }}</p>
            <p class="text-xs text-content-muted">{{ __('campaign.failed') }}</p>
        </div>
        <div class="bg-surface-card border border-surface-border rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-content-muted opacity-80">{{ $campaign->pending_count }}</p>
            <p class="text-xs text-content-muted">{{ __('campaign.pending') }}</p>
        </div>
        <div class="bg-surface-card border border-surface-border rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-cyan-400">{{ $campaign->progress_percent }}%</p>
            <p class="text-xs text-content-muted">{{ __('campaign.processed') }}</p>
        </div>
    </div>

    {{-- Segmented Progress Bar --}}
    <div class="w-full bg-surface-bg border border-surface-border rounded-full h-2.5 flex overflow-hidden">
        @if($campaign->sent_percent > 0)
        <div class="bg-emerald-500 h-full transition-all" style="width: {{ $campaign->sent_percent }}%" title="{{ __('campaign.sent') }}: {{ $campaign->sent_percent }}%"></div>
        @endif
        @if($campaign->failed_percent > 0)
        <div class="bg-red-500 h-full transition-all" style="width: {{ $campaign->failed_percent }}%" title="{{ __('campaign.failed') }}: {{ $campaign->failed_percent }}%"></div>
        @endif
        {{-- Remaining gray area represents pending --}}
    </div>

    {{-- Schedule Info --}}
    <div class="bg-surface-card border border-surface-border rounded-2xl p-5 space-y-3">
        <h3 class="text-sm font-semibold text-content-primary flex items-center gap-2">
            📅 {{ __('campaign.schedule_info') }}
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-content-muted">{{ __('campaign.follow_up') }}</span>
                <p class="text-content-primary font-mono">{{ $campaign->send_start_date?->format('Y-m-d') ?? '-' }}</p>
            </div>
            <div>
                <span class="text-content-muted">{{ __('campaign.send_hours') }}</span>
                <p class="text-content-primary font-mono">{{ $campaign->send_start_time }} → {{ $campaign->send_end_time }}</p>
            </div>
            <div>
                <span class="text-content-muted">{{ __('campaign.delay_interval') }}</span>
                <p class="text-content-primary font-mono">{{ $campaign->min_delay_minutes }}-{{ $campaign->max_delay_minutes }} {{ __('campaign.minutes') }}</p>
            </div>
            <div>
                <span class="text-content-muted">{{ __('campaign.follow_up') }}</span>
                <p class="text-content-primary font-mono">
                    {{ $campaign->auto_followup ? __('campaign.auto') : __('campaign.manual') }}
                    @if($campaign->auto_followup)
                        · {{ $campaign->followUps->count() }}/{{ $campaign->max_followups }}
                    @else
                        @if($campaign->followUps->count() > 0)
                            ({{ $campaign->followUps->count() }})
                        @endif
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Follow-Up Chain --}}
    @if($campaign->followUps->count() > 0)
    <div class="bg-surface-card border border-surface-border rounded-2xl p-6">
        <h3 class="text-sm font-semibold text-content-primary flex items-center gap-2 mb-6">
            <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" /></svg>
            {{ __('campaign.follow_up_timeline') }}
        </h3>
        
        <div class="relative max-w-2xl">
            <!-- Vertical Line -->
            <div class="absolute w-0.5 bg-gradient-to-b from-amber-500/50 to-surface-border rounded-full" dir="ltr" style="{{ app()->getLocale() == 'ar' ? 'right: 1.5rem; top: 1.5rem; bottom: 1.5rem;' : 'left: 1.5rem; top: 1.5rem; bottom: 1.5rem;' }}"></div>
            
            <div class="space-y-6 relative" style="{{ app()->getLocale() == 'en' ? 'padding-left: 3.5rem;' : 'padding-right: 3.5rem;' }}">
                @foreach($campaign->followUps as $fu)
                <div class="relative flex items-center gap-6 group">
                    <!-- Timeline Node -->
                    <div class="absolute w-10 h-10 rounded-full bg-surface-bg border-[3px] {{ in_array($fu->status, ['completed', 'running']) ? 'border-amber-500 shadow-[0_0_15px_rgba(245,158,11,0.2)]' : 'border-surface-border' }} flex items-center justify-center shrink-0 z-10 transition-colors group-hover:border-amber-400"
                         style="{{ app()->getLocale() == 'ar' ? 'right: 0.25rem;' : 'left: 0.25rem;' }}">
                        <span class="text-[10px] font-bold {{ in_array($fu->status, ['completed', 'running']) ? 'text-amber-500' : 'text-content-muted' }}">#{{ $fu->followup_number }}</span>
                    </div>

                    <!-- Card -->
                    <a href="{{ route('campaigns.show', $fu) }}" class="flex-1 block p-4 bg-surface-card rounded-xl border border-surface-border hover:border-amber-500/30 hover:bg-surface-bg transition-all shadow-sm">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h4 class="text-sm font-semibold text-content-primary group-hover:text-amber-500 transition">{{ $fu->name }}</h4>
                                <p class="text-[11px] text-content-muted mt-0.5">{{ $fu->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="px-2.5 py-1 text-[10px] font-medium rounded-full {{ $statusClasses[$fu->status] ?? $statusClasses['draft'] }}">{{ __('campaign.' . $fu->status) ?? $fu->status_label }}</span>
                        </div>
                        
                        <div class="flex items-center gap-4 text-xs font-mono">
                            <div class="text-content-secondary"><span class="text-content-muted">👥 {{ __('campaign.total') }}:</span> {{ $fu->total_recipients }}</div>
                            <div class="text-emerald-500"><span class="text-content-muted">✅ {{ __('campaign.sent') }}:</span> {{ $fu->sent_count }}</div>
                            <div class="text-red-500"><span class="text-content-muted">❌ {{ __('campaign.failed') }}:</span> {{ $fu->failed_count }}</div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Email Timeline --}}
    <div class="bg-surface-card border border-surface-border rounded-2xl p-5 space-y-3">
        <h3 class="text-sm font-semibold text-content-primary flex items-center gap-2">📋 {{ __('campaign.sending_table') }}</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-content-muted border-b border-surface-border text-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }}">
                        <th class="py-2 px-3">#</th>
                        <th class="py-2 px-3">{{ __('campaign.recipient') }}</th>
                        <th class="py-2 px-3">{{ __('campaign.sending_account') }}</th>
                        <th class="py-2 px-3">{{ __('campaign.send_later_time') }}</th>
                        <th class="py-2 px-3">{{ __('campaign.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaign->logs as $i => $log)
                    <tr class="border-b border-surface-border hover:bg-surface-bg transition-colors">
                        <td class="py-3 px-3 text-content-muted align-top">{{ $i + 1 }}</td>
                        <td class="py-3 px-3 font-mono text-content-primary align-top">
                            {{ $log->recipient_email }}
                            @if($log->status === 'failed' && $log->error_message)
                                <div class="mt-1 text-[10px] text-red-500/80 bg-red-500/10 px-2 py-1 rounded-md border border-red-500/20 break-all">
                                    <span class="font-bold">{{ __('campaign.fail_reason') }}:</span> {{ $log->error_message }}
                                </div>
                            @endif
                        </td>
                        <td class="py-3 px-3 text-xs text-content-muted align-top">{{ $log->account->email ?? '-' }}</td>
                        <td class="py-3 px-3 font-mono text-cyan-400 align-top">{{ $log->schedule_send_at?->format('M d, H:i') ?? $log->scheduled_at?->format('M d, H:i') ?? '-' }}</td>
                        <td class="py-3 px-3 align-top">
                            @php
                                $logClasses = [
                                    'pending' => 'bg-zinc-500/10 text-zinc-400',
                                    'processing' => 'bg-cyan-500/10 text-cyan-400',
                                    'sent' => 'bg-emerald-500/10 text-emerald-400',
                                    'failed' => 'bg-red-500/10 text-red-400',
                                    'paused' => 'bg-amber-500/10 text-amber-400',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $logClasses[$log->status] ?? $logClasses['pending'] }}">{{ __('campaign.' . $log->status) ?? $log->status }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Content Preview --}}
    <div class="bg-surface-card border border-surface-border rounded-2xl p-5 space-y-3">
        <h3 class="text-sm font-semibold text-content-primary">📝 {{ __('campaign.email_content') }}</h3>
        <div class="space-y-4">
            <div>
                <span class="text-xs text-content-muted">{{ __('campaign.body') }}:</span>
                <p class="text-sm text-content-primary mt-1 text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}">{{ $campaign->getSubject() }}</p>
            </div>
            <div>
            <div>
                <span class="text-xs text-content-muted">{{ __('campaign.body') }}:</span>
                <div class="mt-1 p-4 bg-surface-bg border border-surface-border rounded-lg text-sm text-content-secondary leading-relaxed whitespace-pre-wrap font-mono text-xs text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}" dir="auto">{!! nl2br(e(Str::limit($campaign->getBody(), 1000))) !!}</div>
            </div>
        </div>
    </div>
</div>
@endsection
