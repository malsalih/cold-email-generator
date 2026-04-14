@extends('layouts.app')
@section('title', 'Email Warming — ColdForge')

@section('content')
<div class="space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-content-primary flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-red-500 flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z" /></svg>
                </div>
                {{ __('warming.title') }}
            </h1>
            <p class="text-sm text-content-muted mt-1">{{ __('warming.subtitle') }}</p>
        </div>
        {{-- Sub Navigation --}}
        <div class="flex items-center gap-1 bg-surface-card rounded-xl p-1 border border-surface-border">
            @foreach([
                ['warming.dashboard', __('warming.dashboard')],
                ['warming.accounts', __('warming.accounts')],
                ['warming.templates', __('warming.templates')],
                ['warming.strategies', __('warming.strategies')],
                ['warming.logs', __('warming.logs')],
                ['warming.settings', __('warming.settings')],
            ] as [$route, $label])
                <a href="{{ route($route) }}"
                   class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all {{ request()->routeIs($route) ? 'bg-orange-500/10 text-orange-600 dark:bg-orange-500/20 dark:text-orange-400' : 'text-content-muted hover:text-content-primary' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
        @foreach([
            [__('warming.stats_total_accounts'), $stats['total_accounts'], 'text-violet-400', $stats['active_accounts'] . ' ' . __('warming.stats_active')],
            [__('warming.stats_sent_today'), $stats['sent_today'], 'text-emerald-400', null],
            [__('warming.stats_sent_week'), $stats['sent_this_week'], 'text-cyan-400', null],
            [__('warming.stats_total_sent'), $stats['total_sent'], 'text-orange-400', null],
            [__('warming.stats_failed_today'), $stats['failed_today'], $stats['failed_today'] > 0 ? 'text-red-400' : 'text-content-muted', null],
            [__('warming.stats_unused_templates'), $stats['unused_templates'], 'text-fuchsia-400', $stats['templates_count'] . ' ' . __('warming.stats_total_templates')],
            [__('warming.stats_pending'), $stats['pending_jobs'], 'text-amber-400', null],
            [__('warming.stats_processing'), $stats['processing_jobs'], 'text-sky-400', null],
        ] as [$label, $value, $colorClass, $sub])
        <div class="bg-surface-card border border-surface-border rounded-xl p-4 text-center hover:border-orange-500/30 transition-colors">
            <p class="text-[11px] text-content-muted uppercase tracking-wider">{{ $label }}</p>
            <p class="text-2xl font-bold {{ $colorClass }} mt-1">{{ $value }}</p>
            @if($sub)
                <p class="text-[10px] text-content-muted mt-0.5">{{ $sub }}</p>
            @endif
        </div>
        @endforeach
    </div>

    {{-- Bot Live Status Card --}}
    <div class="bg-surface-card border border-surface-border rounded-2xl p-5" id="bot-status-card">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="w-3 h-3 rounded-full {{ $botStatus['is_online'] ? 'bg-emerald-500' : 'bg-surface-border' }}" id="bot-indicator"></div>
                    @if($botStatus['is_online'])
                    <div class="absolute inset-0 w-3 h-3 rounded-full bg-emerald-400 animate-ping opacity-50"></div>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-bold text-content-primary">{{ __('warming.bot_status') }}</h3>
                    <p class="text-xs text-content-muted" id="bot-message">{{ $botStatus['current_message'] }}</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-center">
                    <p class="text-lg font-bold text-emerald-400" id="bot-sent">{{ $botStatus['session_sent'] }}</p>
                    <p class="text-[10px] text-content-muted">{{ __('warming.bot_sent') }}</p>
                </div>
                <div class="text-center">
                    <p class="text-lg font-bold text-red-400" id="bot-failed">{{ $botStatus['session_failed'] }}</p>
                    <p class="text-[10px] text-content-muted">{{ __('warming.bot_failed') }}</p>
                </div>
                <button onclick="document.getElementById('bot-log-modal').classList.toggle('hidden')"
                        class="px-3 py-2 text-xs font-medium rounded-lg bg-surface-bg text-content-secondary hover:bg-surface-card transition border border-surface-border">
                    {{ __('warming.bot_logs_btn') }}
                </button>
                <div class="flex gap-1">
                    <form action="{{ route('warming.bot.start') }}" method="POST" class="inline">@csrf
                        <button type="submit" class="px-3 py-2 text-xs font-medium rounded-lg bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 transition border border-emerald-500/20">{{ __('warming.bot_start') }}</button>
                    </form>
                    <form action="{{ route('warming.bot.stop') }}" method="POST" class="inline">@csrf
                        <button type="submit" class="px-3 py-2 text-xs font-medium rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 transition border border-red-500/20">{{ __('warming.bot_stop') }}</button>
                    </form>
                </div>
            </div>
        </div>
        </div>

        {{-- Embedded Live Terminal Log --}}
        <div class="mt-4 pt-4 border-t border-surface-border">
            <h3 class="text-xs font-bold text-content-secondary mb-3 uppercase tracking-wider flex items-center justify-between">
                {{ __('warming.bot_log_title') }} (Live Execution)
                <span class="flex h-2 w-2 relative">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
            </h3>
            <div class="space-y-1 h-32 overflow-y-auto pr-2" id="bot-log-list">
                @foreach($botStatus['session_logs'] as $bl)
                <div class="flex items-start gap-2 py-1 border-b border-surface-border/50">
                    <span class="text-xs shrink-0">{{ $bl->event_icon }}</span>
                    <div class="min-w-0 flex-1">
                        @php
                            $botEventClasses = [
                                'emerald' => 'text-emerald-400', 'red' => 'text-red-400',
                                'cyan' => 'text-cyan-400', 'amber' => 'text-amber-400',
                                'zinc' => 'text-content-muted', 'sky' => 'text-sky-400',
                                'orange' => 'text-orange-400', 'violet' => 'text-violet-400',
                            ];
                        @endphp
                        <p class="text-xs font-mono {{ $botEventClasses[$bl->event_color] ?? 'text-content-muted' }}">{{ $bl->message }}</p>
                        <p class="text-[9px] text-content-muted opacity-50">{{ $bl->created_at->format('H:i:s') }}</p>
                    </div>
                </div>
                @endforeach
                @if($botStatus['session_logs']->isEmpty())
                <p class="text-xs text-content-muted text-center py-4 font-mono">{{ __('warming.no_logs_yet') }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Account Status Cards --}}
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                {{ __('warming.accounts_status') }}
            </h2>

            @forelse($accounts as $account)
            @php
                $dayTarget = $strategy->getDailyLimitForDay($account->warming_day);
                $dayProgress = $dayTarget > 0 ? ($account->current_day_sent / $dayTarget) * 100 : 0;
                $dayRemaining = max(0, $dayTarget - $account->current_day_sent);
            @endphp
            <div class="bg-surface-card border border-surface-border rounded-xl p-4 hover:border-orange-500/30 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @php
                            $accStatusClasses = [
                                'active' => ['bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-400', 'badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20'],
                                'warming' => ['bg' => 'bg-amber-500/10', 'text' => 'text-amber-400', 'badge' => 'bg-amber-500/10 text-amber-400 border-amber-500/20'],
                                'paused' => ['bg' => 'bg-zinc-500/10', 'text' => 'text-zinc-400', 'badge' => 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20'],
                                'error' => ['bg' => 'bg-red-500/10', 'text' => 'text-red-400', 'badge' => 'bg-red-500/10 text-red-400 border-red-500/20'],
                            ];
                            $accStyle = $accStatusClasses[$account->status] ?? $accStatusClasses['paused'];
                        @endphp
                        <div class="w-10 h-10 rounded-lg {{ $accStyle['bg'] }} flex items-center justify-center">
                            <svg class="w-5 h-5 {{ $accStyle['text'] }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-content-primary">{{ $account->display_name }}</p>
                            <p class="text-xs font-mono text-content-muted">{{ $account->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}">
                            <p class="text-xs text-content-muted opacity-80">{{ __('warming.day') }} {{ $account->warming_day }} · {{ __('warming.target') }} {{ $dayTarget }} {{ __('warming.target_messages') }}</p>
                            <p class="text-xs text-content-muted">{{ $account->current_day_sent }}/{{ $dayTarget }} {{ __('warming.sent') }} · {{ $dayRemaining }} {{ __('warming.remaining') }}</p>
                        </div>
                        <span class="px-2 py-1 text-[10px] font-medium rounded-full {{ $accStyle['badge'] }}">
                            {{ $account->status }}
                        </span>
                        @if($account->status === 'active' && $dayRemaining > 0)
                        <form action="{{ route('warming.accounts.daily_round', $account) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-gradient-to-r from-orange-500 to-red-500 text-white hover:from-orange-400 hover:to-red-400 transition-all shadow shadow-orange-500/20 whitespace-nowrap">
                                {{ __('warming.start_today_round') }}
                            </button>
                        </form>
                        @elseif($dayRemaining === 0)
                        <div class="flex items-center gap-2 block">
                            <span class="hidden sm:inline px-3 py-1.5 text-[11px] font-medium rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ __('warming.day_completed', ['day' => $account->warming_day]) }}</span>
                            <form action="{{ route('warming.accounts.next_day', $account) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 text-[11px] font-bold rounded-lg bg-surface-bg border border-surface-border text-content-secondary hover:text-content-primary hover:bg-surface-card transition shadow-sm">
                                    {{ __('warming.start_next_day', ['day' => $account->warming_day + 1]) }}
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="mt-3 w-full bg-surface-bg border border-surface-border rounded-full h-1.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 h-1.5 rounded-full transition-all" style="width: {{ min($dayProgress, 100) }}%"></div>
                </div>
                
                {{-- Account Daily Activity Squares (Last 14 days) --}}
                <div class="flex items-center gap-1 mt-3 pt-3 border-t border-surface-border justify-end overflow-hidden flex-wrap">
                    <span class="text-[10px] text-content-muted ml-2 {{ app()->getLocale() == 'en' ? 'mr-2 ml-0' : '' }}">{{ __('warming.activity_14_days') }}</span>
                    @foreach($account->getRecentHistory(14) as $date => $sentCount)
                        @php
                            $intensity = 'bg-surface-bg border border-surface-border'; // 0
                            if ($sentCount > 0 && $sentCount <= 5) $intensity = 'bg-emerald-500/10 dark:bg-emerald-900/60';
                            elseif ($sentCount > 5 && $sentCount <= 15) $intensity = 'bg-emerald-500/30 dark:bg-emerald-700/80';
                            elseif ($sentCount > 15 && $sentCount <= 30) $intensity = 'bg-emerald-500';
                            elseif ($sentCount > 30) $intensity = 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.4)]';
                        @endphp
                        <div class="w-3.5 h-3.5 rounded-[2px] {{ $intensity }} transition" title="{{ $date }}: {{ $sentCount }} {{ __('warming.sent') }}" style="max-width: 14px;"></div>
                    @endforeach
                </div>
            </div>
            @empty
            <div class="text-center py-12 bg-surface-card border border-surface-border rounded-xl">
                <p class="text-content-muted font-medium mt-3">{{ __('warming.no_accounts') }}</p>
                <a href="{{ route('warming.accounts') }}" class="inline-flex items-center gap-2 mt-3 px-4 py-2 text-sm font-medium rounded-lg bg-orange-500/10 text-orange-400 hover:bg-orange-500/20 transition">
                    {{ __('warming.add_first_account') }}
                </a>
            </div>
            @endforelse
        </div>

        {{-- Sidebar: Recent Activity + Strategy --}}
        <div class="space-y-4">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-content-muted" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                {{ __('warming.recent_activity') }}
            </h2>

            <div class="bg-surface-card border border-surface-border rounded-xl divide-y divide-surface-border">
                @forelse($recentLogs as $log)
                <div class="p-3 flex items-center gap-3">
                    @php
                        $logDotClasses = ['sent' => 'bg-emerald-400', 'failed' => 'bg-red-400', 'pending' => 'bg-amber-400', 'processing' => 'bg-sky-400'];
                        $logBadgeClasses = ['sent' => 'bg-emerald-500/10 text-emerald-400', 'failed' => 'bg-red-500/10 text-red-400', 'pending' => 'bg-amber-500/10 text-amber-400', 'processing' => 'bg-sky-500/10 text-sky-400'];
                    @endphp
                    <div class="w-2 h-2 rounded-full {{ $logDotClasses[$log->status] ?? 'bg-content-muted' }} shrink-0"></div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-content-secondary truncate">{{ $log->subject_sent }}</p>
                        <p class="text-[11px] text-content-muted">{{ $log->recipient_email }} · {{ $log->created_at->diffForHumans() }}</p>
                    </div>
                    <span class="text-[10px] px-1.5 py-0.5 rounded {{ $logBadgeClasses[$log->status] ?? 'bg-surface-bg text-content-muted border border-surface-border' }}">{{ $log->status }}</span>
                </div>
                @empty
                <div class="p-6 text-center">
                    <p class="text-xs text-content-muted">{{ __('warming.no_logs_yet') }}</p>
                </div>
                @endforelse
            </div>

            @if($recentLogs->count() > 0)
            <a href="{{ route('warming.logs') }}" class="block text-center text-xs text-orange-400 hover:text-orange-300 transition">
                {{ __('warming.view_all_logs') }}
            </a>
            @endif

            {{-- Strategy Info --}}
            <div class="bg-surface-card border border-surface-border rounded-xl p-4 space-y-3">
                <h3 class="text-xs font-semibold text-content-primary flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Z" /></svg>
                    {{ __('warming.current_strategy') }}
                </h3>
                <p class="text-xs text-content-muted">{{ $strategy->name }}</p>
                <div class="space-y-1">
                    @foreach($strategy->schedule as $tier)
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="text-content-muted">{{ __('warming.day_range', ['from' => $tier['from_day'], 'to' => $tier['to_day']]) }}</span>
                        <span class="text-orange-400 font-mono">{{ $tier['daily_sends'] }} {{ __('warming.sends_per_day') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Start Warming --}}
    <form action="{{ route('warming.quick_start') }}" method="POST" class="bg-gradient-to-br from-emerald-500/10 to-teal-500/5 border border-emerald-500/20 backdrop-blur-sm rounded-2xl p-6 shadow-xl shadow-emerald-500/5">
        @csrf
        <div class="flex flex-col sm:flex-row gap-6">
            <div class="sm:w-1/3 space-y-3">
                <h2 class="text-base font-bold text-emerald-400 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" /></svg>
                    {{ __('warming.quick_start') }}
                </h2>
                <p class="text-xs text-emerald-100/60 leading-relaxed">{{ __('warming.quick_start_desc') }}</p>
                
                <div class="space-y-1.5 pt-2">
                    <label class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-200/80 uppercase tracking-wider">{{ __('warming.sending_account') }}</label>
                    <select name="warming_account_id" required class="w-full px-3 py-2.5 bg-surface-bg border border-emerald-500/30 rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}" dir="ltr">
                        <option value="">{{ __('warming.select_active_account') }}</option>
                        @foreach($accounts->where('status', 'active') as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->email }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="space-y-1.5 pb-2">
                    <label class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-200/80 uppercase tracking-wider">{{ __('warming.delay_minutes') }}</label>
                    <input type="number" name="delay_minutes" required value="2" min="0" max="1440" class="w-full px-3 py-2.5 bg-surface-bg border border-emerald-500/30 rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}" dir="ltr">
                </div>
            </div>
            
            <div class="sm:w-2/3 flex flex-col space-y-3">
                <div class="space-y-1.5 flex-1 flex flex-col">
                    <div class="flex items-center justify-between">
                        <label class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-200/80 uppercase tracking-wider">{{ __('warming.recipient_emails') }}</label>
                        @if($savedRecipients->count() > 0)
                        <button type="button" onclick="loadSavedRecipients()" class="text-[10px] px-2 py-1 rounded bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20 transition border border-emerald-500/20">
                            {{ __('warming.load_saved', ['count' => $savedRecipients->count()]) }}
                        </button>
                        @endif
                    </div>
                    <textarea id="target-emails" name="target_emails" required placeholder="{{ __('warming.emails_placeholder') }}" class="w-full flex-1 min-h-[100px] px-4 py-3 bg-surface-bg border border-emerald-500/30 rounded-xl text-content-primary placeholder-emerald-500/50 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/50 font-mono leading-relaxed resize-y text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}" dir="ltr"></textarea>
                </div>
                
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-6 py-3 text-sm font-bold rounded-xl bg-gradient-to-r from-emerald-600 to-green-500 text-white hover:from-emerald-500 hover:to-green-400 transition-all flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/20">
                        {{ __('warming.start_quick_round') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    {{-- Saved Recipients Manager --}}
    <div class="bg-surface-card border border-surface-border rounded-2xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-content-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" /></svg>
                {{ __('warming.saved_recipients') }}
            </h2>
            <button type="button" onclick="document.getElementById('save-recipients-form').classList.toggle('hidden')" class="text-xs px-3 py-1.5 rounded-lg bg-violet-500/10 text-violet-400 hover:bg-violet-500/20 transition border border-violet-500/20">
                {{ __('warming.add_recipients') }}
            </button>
        </div>

        {{-- Add Recipients Form (hidden by default) --}}
        <form id="save-recipients-form" action="{{ route('warming.recipients.save') }}" method="POST" class="hidden mb-4 p-4 bg-surface-bg rounded-xl border border-surface-border space-y-3">
            @csrf
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="text-[10px] text-content-muted uppercase">{{ __('warming.emails_comma') }}</label>
                    <textarea name="emails" required rows="2" placeholder="{{ __('warming.emails_placeholder') }}" class="w-full mt-1 px-3 py-2 bg-surface-card border border-surface-border rounded-lg text-content-primary text-sm font-mono focus:outline-none focus:ring-1 focus:ring-violet-500/50 text-left" dir="ltr"></textarea>
                </div>
                <div class="w-32">
                    <label class="text-[10px] text-content-muted uppercase">{{ __('warming.group') }}</label>
                    <input type="text" name="group" value="default" class="w-full mt-1 px-3 py-2 bg-surface-card border border-surface-border rounded-lg text-content-primary text-sm focus:outline-none focus:ring-1 focus:ring-violet-500/50 text-left" dir="ltr">
                </div>
            </div>
            <button type="submit" class="px-4 py-2 text-xs font-medium rounded-lg bg-violet-500/20 text-violet-400 hover:bg-violet-500/30 transition">
                {{ __('warming.save_recipients') }}
            </button>
        </form>

        {{-- Recipients List --}}
        @if($savedRecipients->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
            @foreach($savedRecipients as $rec)
            <div class="flex items-center justify-between p-2.5 bg-surface-bg rounded-lg border border-surface-border group">
                <div class="min-w-0">
                    <p class="text-xs text-content-primary font-mono truncate">{{ $rec->email }}</p>
                    <p class="text-[10px] text-content-muted">{{ $rec->group }}</p>
                </div>
                <form action="{{ route('warming.recipients.delete', $rec) }}" method="POST" class="opacity-0 group-hover:opacity-100 transition">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-400 text-xs p-1">✕</button>
                </form>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-xs text-content-muted text-center py-4">{{ __('warming.no_saved_recipients') }}</p>
        @endif
    </div>
</div>

{{-- Auto-refresh bot status every 5 seconds --}}
<script>
    setInterval(async () => {
        try {
            const res = await fetch('/api/warming/bot-status');
            const data = await res.json();
            
            const indicator = document.getElementById('bot-indicator');
            const message = document.getElementById('bot-message');
            const sent = document.getElementById('bot-sent');
            const failed = document.getElementById('bot-failed');
            
            if (data.is_online) {
                indicator.className = 'w-3 h-3 rounded-full bg-emerald-400';
            } else {
                indicator.className = 'w-3 h-3 rounded-full bg-surface-border';
            }
            
            message.textContent = data.current_message || '{{ __("warming.bot_offline") }}';
            sent.textContent = data.session_sent || 0;
            failed.textContent = data.session_failed || 0;
            
            // Update log list
            const logList = document.getElementById('bot-log-list');
            if (data.session_logs && data.session_logs.length > 0) {
                logList.innerHTML = data.session_logs.map(l => `
                    <div class="flex items-start gap-2 py-1 border-b border-surface-border/50">
                        <span class="text-xs shrink-0">${getEventIcon(l.event)}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-mono ${getEventColor(l.event)}">${l.message}</p>
                            <p class="text-[9px] text-content-muted opacity-50">${new Date(l.created_at).toLocaleTimeString('en-GB')}</p>
                        </div>
                    </div>
                `).join('');
            }
        } catch (e) { /* silent */ }
    }, 3000);
    
    function getEventIcon(event) {
        const icons = { started:'🚀', job_picked:'📋', composing:'✏️', fields_filled:'✅', waiting_user:'✋', sent:'📨', failed:'❌', idle:'💤', stopped:'🛑', error:'⚠️', verified:'🔍' };
        return icons[event] || '📝';
    }

    function getEventColor(event) {
        const colors = { started:'text-emerald-400', job_picked:'text-cyan-400', composing:'text-amber-400', fields_filled:'text-sky-400', waiting_user:'text-orange-400', sent:'text-emerald-500', failed:'text-red-400', idle:'text-zinc-500', stopped:'text-red-500', error:'text-red-500', verified:'text-violet-400' };
        return colors[event] || 'text-content-muted';
    }

    function loadSavedRecipients() {
        const savedEmails = @json($savedRecipients->pluck('email'));
        const textarea = document.getElementById('target-emails');
        const existing = textarea.value.trim();
        const newEmails = savedEmails.join(', ');
        textarea.value = existing ? existing + ', ' + newEmails : newEmails;
    }
</script>
@endsection
