@extends('layouts.app')
@section('title', $campaign->name . ' — ColdForge')

@section('content')
<div class="max-w-4xl mx-auto space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-white">{{ $campaign->name }}</h1>
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
                    {{ $campaign->status_label }}
                </span>
                @if($campaign->is_follow_up)
                <span class="px-2 py-1 text-[10px] font-medium rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    Follow-Up #{{ $campaign->followup_number }}
                </span>
                @endif
            </div>
            @if($campaign->accounts && $campaign->accounts->count() > 0)
            <p class="text-sm text-zinc-400 mt-1 max-w-xl text-wrap leading-relaxed">
                📧 {{ $campaign->accounts->pluck('email')->join('، ') }}<br>
                🌍 {{ $campaign->timezone }}
            </p>
            @endif
        </div>
        <a href="{{ route('campaigns.index') }}" class="text-sm text-zinc-400 hover:text-white transition">← الحملات</a>
    </div>

    {{-- Action Buttons --}}
    <div class="flex flex-wrap gap-3">
        @if(in_array($campaign->status, ['draft', 'scheduled']))
        <form action="{{ route('campaigns.launch', $campaign) }}" method="POST">
            @csrf
            <button type="submit" class="px-5 py-2.5 text-sm font-bold rounded-xl bg-gradient-to-r from-emerald-600 to-green-500 text-white hover:from-emerald-500 hover:to-green-400 transition-all shadow-lg shadow-emerald-500/20 flex items-center gap-2">
                🚀 تشغيل الحملة (Send Later)
            </button>
        </form>
        <a href="{{ route('campaigns.edit', $campaign) }}" class="px-4 py-2.5 text-sm font-medium rounded-xl bg-violet-500/10 text-violet-400 border border-violet-500/20 hover:bg-violet-500/20 transition flex items-center gap-2">
            ✏️ تعديل الحملة
        </a>
        @endif

        @if($campaign->status === 'running')
        <form action="{{ route('campaigns.pause', $campaign) }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2.5 text-sm font-medium rounded-xl bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition">⏸ إيقاف مؤقت</button>
        </form>
        @endif

        @if($campaign->status === 'paused')
        <form action="{{ route('campaigns.resume', $campaign) }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2.5 text-sm font-medium rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition">▶️ استئناف</button>
        </form>
        @endif

        @if($campaign->status === 'completed' && $campaign->followUps->count() < $campaign->max_followups)
        <a href="{{ route('campaigns.follow_up', $campaign) }}" class="px-5 py-2.5 text-sm font-bold rounded-xl bg-gradient-to-r from-amber-600 to-orange-500 text-white hover:from-amber-500 hover:to-orange-400 transition-all shadow-lg shadow-amber-500/20 flex items-center gap-2">
            🔄 إرسال Follow-Up
        </a>
        @endif

        <form action="{{ route('campaigns.destroy', $campaign) }}" method="POST" onsubmit="return confirm('حذف الحملة؟')">
            @csrf @method('DELETE')
            <button type="submit" class="px-4 py-2.5 text-sm font-medium rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition">🗑 حذف</button>
        </form>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-zinc-900/50 border border-zinc-800/50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-white">{{ $campaign->total_recipients }}</p>
            <p class="text-xs text-zinc-500">إجمالي</p>
        </div>
        <div class="bg-zinc-900/50 border border-zinc-800/50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-emerald-400">{{ $campaign->sent_count }}</p>
            <p class="text-xs text-zinc-500">مرسل</p>
        </div>
        <div class="bg-zinc-900/50 border border-zinc-800/50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-red-400">{{ $campaign->failed_count }}</p>
            <p class="text-xs text-zinc-500">فشل</p>
        </div>
        <div class="bg-zinc-900/50 border border-zinc-800/50 rounded-xl p-4 text-center">
            <p class="text-2xl font-bold text-cyan-400">{{ $campaign->progress_percent }}%</p>
            <p class="text-xs text-zinc-500">تقدم</p>
        </div>
    </div>

    {{-- Progress Bar --}}
    <div class="w-full bg-zinc-800 rounded-full h-2">
        <div class="bg-gradient-to-r from-cyan-500 to-emerald-500 h-2 rounded-full transition-all" style="width: {{ $campaign->progress_percent }}%"></div>
    </div>

    {{-- Schedule Info --}}
    <div class="bg-zinc-900/50 border border-zinc-800/50 rounded-2xl p-5 space-y-3">
        <h3 class="text-sm font-semibold text-white flex items-center gap-2">
            📅 معلومات الجدولة
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-zinc-500">تاريخ البدء</span>
                <p class="text-white font-mono">{{ $campaign->send_start_date?->format('Y-m-d') ?? '-' }}</p>
            </div>
            <div>
                <span class="text-zinc-500">ساعات الإرسال</span>
                <p class="text-white font-mono">{{ $campaign->send_start_time }} → {{ $campaign->send_end_time }}</p>
            </div>
            <div>
                <span class="text-zinc-500">فاصل زمني</span>
                <p class="text-white font-mono">{{ $campaign->min_delay_minutes }}-{{ $campaign->max_delay_minutes }} دقيقة</p>
            </div>
            <div>
                <span class="text-zinc-500">Follow-Up</span>
                <p class="text-white font-mono">{{ $campaign->auto_followup ? 'تلقائي' : 'يدوي' }} · {{ $campaign->followUps->count() }}/{{ $campaign->max_followups }}</p>
            </div>
        </div>
    </div>

    {{-- Follow-Up Chain --}}
    @if($campaign->followUps->count() > 0)
    <div class="bg-zinc-900/50 border border-zinc-800/50 rounded-2xl p-5 space-y-3">
        <h3 class="text-sm font-semibold text-white flex items-center gap-2">🔄 سلسلة Follow-Up</h3>
        <div class="space-y-2">
            @foreach($campaign->followUps as $fu)
            <a href="{{ route('campaigns.show', $fu) }}" class="flex items-center justify-between p-3 bg-black/20 rounded-lg border border-zinc-800/30 hover:border-amber-500/20 transition group">
                <div>
                    <p class="text-sm text-white group-hover:text-amber-400 transition">Follow-Up #{{ $fu->followup_number }}</p>
                    <p class="text-xs text-zinc-500">{{ $fu->total_recipients }} مستلم · {{ $fu->sent_count }} مرسل</p>
                </div>
                <span class="px-2 py-1 text-[10px] font-medium rounded-full {{ $statusClasses[$fu->status] ?? $statusClasses['draft'] }}">{{ $fu->status_label }}</span>
            </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Email Timeline --}}
    <div class="bg-zinc-900/50 border border-zinc-800/50 rounded-2xl p-5 space-y-3">
        <h3 class="text-sm font-semibold text-white flex items-center gap-2">📋 جدول الإرسال</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-zinc-500 border-b border-zinc-800/50">
                        <th class="text-right py-2 px-3">#</th>
                        <th class="text-right py-2 px-3">المستلم</th>
                        <th class="text-right py-2 px-3">حساب الإرسال</th>
                        <th class="text-right py-2 px-3">وقت Send Later</th>
                        <th class="text-right py-2 px-3">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaign->logs as $i => $log)
                    <tr class="border-b border-zinc-800/30 hover:bg-zinc-800/20">
                        <td class="py-2 px-3 text-zinc-600">{{ $i + 1 }}</td>
                        <td class="py-2 px-3 font-mono text-white">{{ $log->recipient_email }}</td>
                        <td class="py-2 px-3 text-xs text-zinc-400">{{ $log->account->email ?? '-' }}</td>
                        <td class="py-2 px-3 font-mono text-cyan-400">{{ $log->schedule_send_at?->format('M d, H:i') ?? $log->scheduled_at?->format('M d, H:i') ?? '-' }}</td>
                        <td class="py-2 px-3">
                            @php
                                $logClasses = [
                                    'pending' => 'bg-zinc-500/10 text-zinc-400',
                                    'processing' => 'bg-cyan-500/10 text-cyan-400',
                                    'sent' => 'bg-emerald-500/10 text-emerald-400',
                                    'failed' => 'bg-red-500/10 text-red-400',
                                    'paused' => 'bg-amber-500/10 text-amber-400',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $logClasses[$log->status] ?? $logClasses['pending'] }}">{{ $log->status }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Content Preview --}}
    <div class="bg-zinc-900/50 border border-zinc-800/50 rounded-2xl p-5 space-y-3">
        <h3 class="text-sm font-semibold text-white">📝 محتوى الحملة</h3>
        <div class="space-y-2">
            <div>
                <span class="text-xs text-zinc-500">الموضوع:</span>
                <p class="text-sm text-white">{{ $campaign->getSubject() }}</p>
            </div>
            <div>
                <span class="text-xs text-zinc-500">النص:</span>
                <div class="mt-1 p-3 bg-black/20 rounded-lg text-sm text-zinc-300 leading-relaxed whitespace-pre-wrap font-mono text-xs">{!! nl2br(e(Str::limit($campaign->getBody(), 500))) !!}</div>
            </div>
        </div>
    </div>
</div>
@endsection
