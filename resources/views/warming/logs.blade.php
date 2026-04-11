@extends('layouts.app')
@section('title', 'سجلات الإرسال — Email Warming')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">سجلات الإرسال</h1>
            <p class="text-sm text-zinc-400 mt-1">تتبع كل إيميل تم إرساله عبر نظام التسخين</p>
        </div>
        <a href="{{ route('warming.dashboard') }}" class="text-sm text-zinc-400 hover:text-white transition">← لوحة التحكم</a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('warming.logs') }}" class="flex flex-wrap gap-3">
        <select name="account_id" class="px-4 py-2 bg-zinc-900/50 border border-zinc-800/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/50 appearance-none cursor-pointer">
            <option value="">كل الحسابات</option>
            @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->email }}</option>
            @endforeach
        </select>
        <select name="status" class="px-4 py-2 bg-zinc-900/50 border border-zinc-800/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/50 appearance-none cursor-pointer">
            <option value="">كل الحالات</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>مُرسل</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>فشل</option>
        </select>
        <select name="source" class="px-4 py-2 bg-zinc-900/50 border border-zinc-800/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/50 appearance-none cursor-pointer">
            <option value="">كل المصادر</option>
            <option value="warming" {{ request('source') === 'warming' ? 'selected' : '' }}>تسخين</option>
            <option value="campaign" {{ request('source') === 'campaign' ? 'selected' : '' }}>حملة</option>
        </select>
        <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-zinc-800 hover:bg-zinc-700 border border-zinc-700/50 rounded-xl transition-colors">
            فلترة
        </button>
        @if(request()->hasAny(['account_id', 'status', 'source']))
            <a href="{{ route('warming.logs') }}" class="px-4 py-2 text-sm text-zinc-400 hover:text-white transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                مسح الفلاتر
            </a>
        @endif
    </form>

    {{-- Logs Table --}}
    <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-800/50 bg-zinc-800/30">
                        <th class="text-right text-xs font-medium text-zinc-500 uppercase tracking-wider p-4">الحالة</th>
                        <th class="text-right text-xs font-medium text-zinc-500 uppercase tracking-wider p-4">الحساب</th>
                        <th class="text-right text-xs font-medium text-zinc-500 uppercase tracking-wider p-4">المستلم</th>
                        <th class="text-right text-xs font-medium text-zinc-500 uppercase tracking-wider p-4">العنوان</th>
                        <th class="text-right text-xs font-medium text-zinc-500 uppercase tracking-wider p-4">المصدر</th>
                        <th class="text-right text-xs font-medium text-zinc-500 uppercase tracking-wider p-4">المدة/الوقت</th>
                        <th class="text-right text-xs font-medium text-zinc-500 uppercase tracking-wider p-4">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="border-b border-zinc-800/30 hover:bg-zinc-800/20 transition-colors">
                        <td class="p-4">
                            @php
                                $logBadge = match($log->status) {
                                    'sent' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                    'failed' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                    'pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                    'processing' => 'bg-sky-500/10 text-sky-400 border-sky-500/20',
                                    'bounced' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
                                    default => 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
                                };
                                $logDot = match($log->status) {
                                    'sent' => 'bg-emerald-400', 'failed' => 'bg-red-400',
                                    'pending' => 'bg-amber-400', 'processing' => 'bg-sky-400',
                                    default => 'bg-zinc-400',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[11px] font-medium rounded-full {{ $logBadge }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $logDot }}"></span>
                                {{ $log->status }}
                            </span>
                        </td>
                        <td class="p-4 text-xs text-zinc-300">
                            {{ $log->account->email ?? '—' }}
                        </td>
                        <td class="p-4 text-xs font-mono text-zinc-400">
                            {{ $log->recipient_email }}
                        </td>
                        <td class="p-4 text-xs text-zinc-300 max-w-xs truncate">
                            {{ $log->subject_sent }}
                        </td>
                        <td class="p-4">
                            <span class="text-[11px] px-2 py-0.5 rounded-md {{ $log->source_type === 'warming' ? 'bg-orange-500/10 text-orange-400' : 'bg-violet-500/10 text-violet-400' }}">
                                {{ $log->source_type === 'warming' ? 'تسخين' : 'حملة' }}
                            </span>
                        </td>
                        <td class="p-4 text-xs text-zinc-500">
                            {{ $log->sent_at ? $log->sent_at->diffForHumans() : ($log->scheduled_at ? $log->scheduled_at->format('Y-m-d H:i') : $log->created_at->diffForHumans()) }}
                        </td>
                        <td class="p-4">
                            @if($log->status === 'failed')
                                <form action="{{ route('warming.logs.retry', $log) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-[11px] font-medium rounded-lg bg-orange-500/10 text-orange-400 hover:bg-orange-500/20 transition flex items-center gap-1.5">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                        إعادة محاولة
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @if($log->error_message)
                    <tr class="border-b border-zinc-800/30">
                        <td colspan="7" class="px-4 pb-3 pt-0">
                            <p class="text-[11px] text-red-400 bg-red-500/5 rounded-lg px-3 py-1.5">⚠ {{ $log->error_message }}</p>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="6" class="p-12 text-center text-zinc-500">
                            لا توجد سجلات بعد
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
@endsection
