@extends('layouts.app')
@section('title', __('warming.warming_strategies') . ' — Email Warming')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-content-primary">{{ __('warming.warming_strategies') }}</h1>
            <p class="text-sm text-content-muted mt-1">{{ __('warming.warming_strategies_desc') }}</p>
        </div>
        <a href="{{ route('warming.dashboard') }}" class="text-sm text-content-muted hover:text-content-primary transition">{{ __('warming.back_to_dashboard') }}</a>
    </div>

    @foreach($strategies as $strategy)
    <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-content-primary flex items-center gap-2">
                    {{ $strategy->name }}
                    @if($strategy->is_default)
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-orange-500/10 text-orange-500 border border-orange-500/20">{{ __('warming.default_badge') }}</span>
                    @endif
                </h2>
                @if($strategy->description)
                    <p class="text-sm text-content-muted mt-1">{{ $strategy->description }}</p>
                @endif
            </div>
            <div class="text-{{ app()->getLocale() == 'en' ? 'right' : 'left' }} text-xs text-content-muted space-y-1">
                <p>{{ __('warming.delay_minutes_range', ['min' => $strategy->min_delay_minutes, 'max' => $strategy->max_delay_minutes]) }}</p>
                <p>{{ __('warming.active_hours', ['start' => $strategy->active_hours_start, 'end' => $strategy->active_hours_end]) }}</p>
            </div>
        </div>

        {{-- Visual Schedule --}}
        <div class="space-y-3">
            <h3 class="text-xs font-semibold text-content-muted uppercase tracking-wider">{{ __('warming.ramp_up_plan') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach($strategy->schedule as $index => $tier)
                @php
                    $maxSends = collect($strategy->schedule)->max('daily_sends');
                    $percentage = $maxSends > 0 ? ($tier['daily_sends'] / $maxSends) * 100 : 0;
                    $colors = ['from-orange-500/20 to-orange-500/5', 'from-amber-500/20 to-amber-500/5', 'from-yellow-500/20 to-yellow-500/5', 'from-emerald-500/20 to-emerald-500/5', 'from-cyan-500/20 to-cyan-500/5', 'from-violet-500/20 to-violet-500/5'];
                    $textColors = ['text-orange-400', 'text-amber-400', 'text-yellow-400', 'text-emerald-400', 'text-cyan-400', 'text-violet-400'];
                    $borderColors = ['border-orange-500/20', 'border-amber-500/20', 'border-yellow-500/20', 'border-emerald-500/20', 'border-cyan-500/20', 'border-violet-500/20'];
                @endphp
                <div class="bg-gradient-to-br {{ $colors[$index % count($colors)] }} border {{ $borderColors[$index % count($borderColors)] }} rounded-xl p-4 relative overflow-hidden">
                    <div class="relative">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-content-secondary">
                                @if($tier['to_day'] >= 999)
                                    {{ __('warming.day_plus', ['day' => $tier['from_day']]) }}
                                @else
                                    {{ __('warming.day_to_day', ['from' => $tier['from_day'], 'to' => $tier['to_day']]) }}
                                @endif
                            </span>
                            <span class="text-lg font-bold {{ $textColors[$index % count($textColors)] }}">{{ $tier['daily_sends'] }}</span>
                        </div>
                        <p class="text-[11px] text-content-muted">{{ __('warming.emails_per_day') }}</p>
                        {{-- Mini bar --}}
                        <div class="mt-3 w-full bg-surface-bg border border-surface-border rounded-full h-1">
                            <div class="h-1 rounded-full bg-gradient-to-r from-orange-500 to-red-500 transition-all" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Summary Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-border">
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider pb-3 px-4">{{ __('warming.col_period') }}</th>
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider pb-3 px-4">{{ __('warming.col_days_count') }}</th>
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider pb-3 px-4">{{ __('warming.col_emails_per_day') }}</th>
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider pb-3 px-4">{{ __('warming.col_total_period') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php $grandTotal = 0; @endphp
                    @foreach($strategy->schedule as $tier)
                    @php
                        $days = min($tier['to_day'], 60) - $tier['from_day'] + 1;
                        $tierTotal = $days * $tier['daily_sends'];
                        $grandTotal += $tierTotal;
                    @endphp
                    <tr class="border-b border-surface-border">
                        <td class="py-2.5 px-4 text-content-secondary">
                            @if($tier['to_day'] >= 999)
                                {{ __('warming.day_plus', ['day' => $tier['from_day']]) }}
                            @else
                                {{ __('warming.day_to_day', ['from' => $tier['from_day'], 'to' => $tier['to_day']]) }}
                            @endif
                        </td>
                        <td class="py-2.5 px-4 text-content-muted">{{ __('warming.days_count', ['count' => $days]) }}</td>
                        <td class="py-2.5 px-4 text-orange-500 font-mono">{{ $tier['daily_sends'] }}</td>
                        <td class="py-2.5 px-4 text-content-muted">{{ __('warming.approx_emails', ['count' => $tierTotal]) }}</td>
                    </tr>
                    @endforeach
                    <tr>
                    <tr class="bg-surface-bg/30">
                        <td colspan="3" class="py-2.5 px-4 text-xs font-semibold text-content-secondary">{{ __('warming.total_first_30_days') }}</td>
                        <td class="py-2.5 px-4 text-orange-500 font-bold font-mono">{{ __('warming.approx_emails', ['count' => $grandTotal]) }}</td>
                    </tr>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>
@endsection
