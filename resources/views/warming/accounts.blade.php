@extends('layouts.app')
@section('title', 'الحسابات — Email Warming')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">إدارة الحسابات</h1>
            <p class="text-sm text-zinc-400 mt-1">أضف وأدر حسابات Zoho Mail للتسخين</p>
        </div>
        <a href="{{ route('warming.dashboard') }}" class="text-sm text-zinc-400 hover:text-white transition">← لوحة التحكم</a>
    </div>

    {{-- Add Account Form --}}
    <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center justify-between w-full text-left">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                إضافة حساب جديد
            </h2>
            <svg class="w-4 h-4 text-zinc-500 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </button>

        <form action="{{ route('warming.accounts.store') }}" method="POST" x-show="open" x-transition class="mt-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">البريد الإلكتروني</label>
                    <input type="email" name="email" required placeholder="sales@eagnt.com"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/50 transition-all">
                    @error('email') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">الاسم المعروض</label>
                    <input type="text" name="display_name" required placeholder="Mohammed Ali"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/50 transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">الدومين (اختياري)</label>
                    <input type="text" name="domain" placeholder="eagnt.com"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/50 transition-all">
                </div>
            </div>
            <button type="submit" class="px-5 py-2.5 text-sm font-medium rounded-xl bg-gradient-to-r from-orange-600 to-red-600 text-white hover:opacity-90 transition-opacity">
                إضافة الحساب
            </button>
        </form>
    </div>

    {{-- Accounts List --}}
    <div class="space-y-4">
        @forelse($accounts as $account)
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-5 hover:border-zinc-700/50 transition-colors">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                    @php
                        $accStyle = match($account->status) {
                            'active' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'],
                            'warming' => ['bg' => 'bg-amber-500/10', 'text' => 'text-amber-400', 'badge' => 'bg-amber-500/10 text-amber-400 border-amber-500/20'],
                            'error' => ['bg' => 'bg-red-500/10', 'text' => 'text-red-400', 'badge' => 'bg-red-500/10 text-red-400 border-red-500/20'],
                            default => ['bg' => 'bg-zinc-500/10', 'text' => 'text-zinc-400', 'badge' => 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20'],
                        };
                    @endphp
                    <div class="w-12 h-12 rounded-xl {{ $accStyle['bg'] }} flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 {{ $accStyle['text'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-white">{{ $account->display_name }}</p>
                        <p class="text-xs font-mono text-zinc-400">{{ $account->email }}</p>
                        <div class="flex items-center gap-3 mt-2 flex-wrap">
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full {{ $accStyle['badge'] }}">
                                {{ $account->status }}
                            </span>
                            <span class="text-[11px] text-zinc-500">يوم {{ $account->warming_day }}</span>
                            <span class="text-[11px] text-zinc-500">{{ $account->current_day_sent }}/{{ $account->daily_limit }} اليوم</span>
                            <span class="text-[11px] text-zinc-500">إجمالي: {{ $account->total_sent }}</span>
                            @if($account->is_logged_in)
                                <span class="text-[11px] text-emerald-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    مُسجّل الدخول
                                </span>
                            @else
                                <span class="text-[11px] text-amber-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                                    بحاجة لتسجيل الدخول
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    {{-- Login Button --}}
                    <div x-data="{ loading: false, status: '' }">
                        <button type="button" 
                                :disabled="loading"
                                @click="
                                    loading = true;
                                    status = 'جاري فتح المتصفح...';
                                    fetch('{{ route('warming.accounts.login', $account) }}', {
                                        method: 'POST',
                                        headers: { 
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    }).then(r => r.json()).then(data => {
                                        status = 'متصفح مفتوح — سجّل الدخول فيه';
                                        // Poll for login completion every 5 seconds
                                        let checks = 0;
                                        const poll = setInterval(() => {
                                            checks++;
                                            fetch('{{ url('/api/warming/account/' . $account->id . '/session') }}')
                                                .then(r => r.json())
                                                .then(s => {
                                                    if (s.is_logged_in) {
                                                        clearInterval(poll);
                                                        status = '✅ تم تسجيل الدخول!';
                                                        setTimeout(() => location.reload(), 1500);
                                                    }
                                                }).catch(() => {});
                                            if (checks > 60) { // 5 min timeout
                                                clearInterval(poll);
                                                status = 'انتهت المهلة';
                                                loading = false;
                                            }
                                        }, 5000);
                                    }).catch(err => {
                                        status = '❌ خطأ: ' + err.message;
                                        loading = false;
                                    });
                                "
                                class="px-3 py-2 text-xs font-medium rounded-lg transition-all flex items-center gap-1.5"
                                :class="loading 
                                    ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20 cursor-wait' 
                                    : 'bg-cyan-500/10 text-cyan-400 hover:bg-cyan-500/20 border border-cyan-500/20'"
                                title="فتح متصفح Zoho لتسجيل الدخول">
                            <template x-if="!loading">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 1 1 9 0v3.75M3.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H3.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                    تسجيل الدخول
                                </span>
                            </template>
                            <template x-if="loading">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                    <span x-text="status"></span>
                                </span>
                            </template>
                        </button>
                    </div>

                    {{-- Toggle Active/Paused --}}
                    <form action="{{ route('warming.accounts.toggle', $account) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 text-xs font-medium rounded-lg transition-all flex items-center gap-1.5
                            {{ $account->status === 'active' ? 'bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 border border-emerald-500/20' }}">
                            @if($account->status === 'active')
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" /></svg>
                                إيقاف
                            @else
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
                                تفعيل
                            @endif
                        </button>
                    </form>

                    {{-- Delete --}}
                    <form action="{{ route('warming.accounts.delete', $account) }}" method="POST" class="inline" onsubmit="return confirm('حذف هذا الحساب؟')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-2 text-xs font-medium rounded-lg bg-red-500/5 text-red-400 hover:bg-red-500/10 border border-red-500/20 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-16 bg-zinc-900/50 border border-zinc-800/50 rounded-2xl">
            <p class="text-zinc-400 font-medium">لم تضف أي حسابات بعد</p>
            <p class="text-sm text-zinc-500 mt-1">افتح نموذج الإضافة أعلاه للبدء</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
