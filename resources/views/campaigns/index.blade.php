@extends('layouts.app')
@section('title', __('campaign.index_title') . ' — ColdForge')

@section('content')
<div class="space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-content-primary flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center shadow-lg shadow-violet-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                </div>
                {{ __('campaign.index_title') }}
            </h1>
            <p class="text-sm text-content-muted mt-1">{{ __('campaign.index_desc') }}</p>
        </div>
        <a href="{{ route('campaigns.create') }}" class="px-4 py-2.5 text-sm font-medium rounded-xl bg-gradient-to-r from-violet-600 to-purple-600 text-white hover:opacity-90 transition-opacity flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('campaign.new') }}
        </a>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach([
            [__('campaign.total_campaigns'), $stats['total'], 'text-violet-400'],
            [__('campaign.running_campaigns'), $stats['running'], 'text-emerald-400'],
            [__('campaign.scheduled_campaigns'), $stats['scheduled'], 'text-cyan-400'],
            [__('campaign.completed_campaigns'), $stats['completed'], 'text-green-400'],
        ] as [$label, $value, $colorClass])
        <div class="bg-surface-card border border-surface-border rounded-xl p-4 text-center">
            <p class="text-[11px] text-content-muted uppercase tracking-wider">{{ $label }}</p>
            <p class="text-2xl font-bold {{ $colorClass }} mt-1">{{ $value }}</p>
        </div>
        @endforeach
    </div>

    {{-- Campaigns List --}}
    <div class="space-y-3">
        @forelse($campaigns as $campaign)
        <a href="{{ route('campaigns.show', $campaign) }}" class="block bg-surface-card border border-surface-border rounded-2xl p-5 hover:border-violet-500/30 transition-colors group">
            <div class="flex items-center justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-3">
                        <h3 class="text-sm font-semibold text-content-primary group-hover:text-violet-400 transition-colors truncate">{{ $campaign->name }}</h3>
                        @php
                            $statusClasses = [
                                'draft' => 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
                                'scheduled' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
                                'running' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                'paused' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                'completed' => 'bg-green-500/10 text-green-400 border-green-500/20',
                                'failed' => 'bg-red-500/10 text-red-400 border-red-500/20',
                            ];
                            $progressColors = [
                                'draft' => 'text-zinc-400', 'scheduled' => 'text-cyan-400',
                                'running' => 'text-emerald-400', 'paused' => 'text-amber-400',
                                'completed' => 'text-green-400', 'failed' => 'text-red-400',
                            ];
                        @endphp
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $statusClasses[$campaign->status] ?? $statusClasses['draft'] }} shrink-0">
                            {{ __('campaign.' . $campaign->status) ?? $campaign->status_label }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4 mt-2 text-[11px] text-content-muted">
                        @if($campaign->accounts && $campaign->accounts->count() > 0)
                            <span title="{{ $campaign->accounts->pluck('email')->join(', ') }}">📧 {{ $campaign->accounts->count() }} {{ __('campaign.accounts') }}</span>
                        @else
                            <span>📧 —</span>
                        @endif
                        <span>👥 {{ $campaign->total_recipients }} {{ __('campaign.recipients') }}</span>
                        <span dir="ltr">📅 {{ $campaign->send_start_date?->format('M d') ?? '—' }} · {{ $campaign->send_start_time }} → {{ $campaign->send_end_time }}</span>
                        <span dir="ltr">⏱ {{ $campaign->min_delay_minutes }}-{{ $campaign->max_delay_minutes }} {{ __('campaign.minutes') }}</span>
                        @if($campaign->followUps->count() > 0)
                            <span class="text-amber-400" dir="ltr">🔄 {{ $campaign->followUps->count() }} Follow-Up</span>
                        @endif
                    </div>
                </div>
                <div class="text-{{ app()->getLocale() == 'ar' ? 'left' : 'right' }} shrink-0">
                    <p class="text-lg font-bold {{ $progressColors[$campaign->status] ?? 'text-zinc-400' }}">{{ $campaign->progress_percent }}%</p>
                    <p class="text-[11px] text-content-muted" dir="ltr">{{ $campaign->sent_count }}/{{ $campaign->total_recipients }}</p>
                </div>
            </div>
            @if($campaign->total_recipients > 0)
            <div class="mt-3 w-full bg-surface-bg border border-surface-border rounded-full h-1.5 flex overflow-hidden">
                @if($campaign->sent_percent > 0)
                <div class="bg-emerald-500 h-full transition-all" style="width: {{ $campaign->sent_percent }}%"></div>
                @endif
                @if($campaign->failed_percent > 0)
                <div class="bg-red-500 h-full transition-all" style="width: {{ $campaign->failed_percent }}%"></div>
                @endif
            </div>
            @endif
        </a>
        @empty
        <div class="text-center py-16 bg-surface-card border border-surface-border rounded-2xl">
            <svg class="w-16 h-16 mx-auto text-zinc-700" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
            <p class="text-content-muted font-medium mt-4">{{ __('campaign.no_campaigns') }}</p>
            <p class="text-sm text-content-muted mt-1">{{ __('campaign.no_campaigns_desc') }}</p>
            <a href="{{ route('campaigns.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 text-sm font-medium rounded-lg bg-violet-500/10 text-violet-400 hover:bg-violet-500/20 transition">
                {{ __('campaign.create_first_campaign') }}
            </a>
        </div>
        @endforelse
    </div>

    {{ $campaigns->links() }}
</div>
@endsection
