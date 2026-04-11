@extends('layouts.app')
@section('title', 'الاستراتيجيات — Email Warming')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">استراتيجيات التسخين</h1>
            <p class="text-sm text-zinc-400 mt-1">خطط الزيادة التدريجية لضمان تسليم آمن</p>
        </div>
        <a href="{{ route('warming.dashboard') }}" class="text-sm text-zinc-400 hover:text-white transition">← لوحة التحكم</a>
    </div>

    @foreach($strategies as $strategy)
    <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    {{ $strategy->name }}
                    @if($strategy->is_default)
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-orange-500/10 text-orange-400 border border-orange-500/20">الافتراضية</span>
                    @endif
                </h2>
                @if($strategy->description)
                    <p class="text-sm text-zinc-400 mt-1">{{ $strategy->description }}</p>
                @endif
            </div>
            <div class="text-right text-xs text-zinc-500 space-y-1">
                <p>التأخير: {{ $strategy->min_delay_minutes }}-{{ $strategy->max_delay_minutes }} دقيقة</p>
                <p>ساعات العمل: {{ $strategy->active_hours_start }} - {{ $strategy->active_hours_end }}</p>
            </div>
        </div>

        {{-- Visual Schedule --}}
        <div class="space-y-3">
            <h3 class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">خطة الزيادة التدريجية</h3>
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
                            <span class="text-xs font-medium text-zinc-300">
                                @if($tier['to_day'] >= 999)
                                    يوم {{ $tier['from_day'] }}+
                                @else
                                    يوم {{ $tier['from_day'] }} — {{ $tier['to_day'] }}
                                @endif
                            </span>
                            <span class="text-lg font-bold {{ $textColors[$index % count($textColors)] }}">{{ $tier['daily_sends'] }}</span>
                        </div>
                        <p class="text-[11px] text-zinc-500">إيميل/يوم</p>
                        {{-- Mini bar --}}
                        <div class="mt-3 w-full bg-zinc-800/50 rounded-full h-1">
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
                    <tr class="border-b border-zinc-800/50">
                        <th class="text-left text-xs font-medium text-zinc-500 uppercase tracking-wider pb-3 pr-4">الفترة</th>
                        <th class="text-left text-xs font-medium text-zinc-500 uppercase tracking-wider pb-3 pr-4">عدد الأيام</th>
                        <th class="text-left text-xs font-medium text-zinc-500 uppercase tracking-wider pb-3 pr-4">إيميلات/يوم</th>
                        <th class="text-left text-xs font-medium text-zinc-500 uppercase tracking-wider pb-3">إجمالي الفترة</th>
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
                    <tr class="border-b border-zinc-800/30">
                        <td class="py-2.5 pr-4 text-zinc-300">
                            @if($tier['to_day'] >= 999)
                                يوم {{ $tier['from_day'] }}+
                            @else
                                يوم {{ $tier['from_day'] }} — {{ $tier['to_day'] }}
                            @endif
                        </td>
                        <td class="py-2.5 pr-4 text-zinc-400">{{ $days }} يوم</td>
                        <td class="py-2.5 pr-4 text-orange-400 font-mono">{{ $tier['daily_sends'] }}</td>
                        <td class="py-2.5 text-zinc-400">~{{ $tierTotal }} إيميل</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" class="py-2.5 text-xs font-semibold text-zinc-300">إجمالي أول 30 يوم</td>
                        <td class="py-2.5 text-orange-400 font-bold font-mono">~{{ $grandTotal }} إيميل</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>
@endsection
