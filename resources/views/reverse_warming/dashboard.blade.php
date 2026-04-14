@extends('layouts.app')
@section('title', __('warming.rw_title') . ' — ColdForge')

@section('content')
<div class="space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-content-primary flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-500 flex items-center justify-center shadow-lg shadow-blue-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                </div>
                {{ __('warming.rw_title') }}
            </h1>
            <p class="text-sm text-content-muted mt-1 max-w-3xl">{{ __('warming.rw_subtitle') }}</p>
        </div>
        
        <a href="{{ route('reverse_warming.redirect') }}" class="btn-primary bg-blue-600 hover:bg-blue-700 shadow-blue-500/25 flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.187 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/>
            </svg>
            {{ __('warming.rw_btn_connect') }}
        </a>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <div class="bg-surface-card border border-surface-border rounded-xl p-5 hover:border-blue-500/30 transition-colors">
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-blue-500/10 text-blue-500 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                </div>
                <p class="text-sm font-semibold text-content-secondary uppercase tracking-wider">{{ __('warming.rw_connected_accounts') }}</p>
            </div>
            <p class="text-3xl font-bold text-content-primary">{{ $accounts->count() }}</p>
        </div>
        
        <div class="bg-surface-card border border-surface-border rounded-xl p-5 hover:border-emerald-500/30 transition-colors">
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-emerald-500/10 text-emerald-500 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <p class="text-sm font-semibold text-content-secondary uppercase tracking-wider">{{ __('warming.rw_active_senders') }}</p>
            </div>
            <p class="text-3xl font-bold text-content-primary">{{ $accounts->where('is_active', true)->where('status', 'active')->count() }}</p>
        </div>

        <div class="bg-surface-card border border-surface-border rounded-xl p-5 hover:border-purple-500/30 transition-colors">
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-purple-500/10 text-purple-500 rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                </div>
                <p class="text-sm font-semibold text-content-secondary uppercase tracking-wider">{{ __('warming.rw_sent_today') }}</p>
            </div>
            <p class="text-3xl font-bold text-content-primary">{{ $totalSentToday }}</p>
        </div>
    </div>

    {{-- Reverse Campaign Launchpad --}}
    @if($accounts->where('is_active', true)->where('status', 'active')->count() > 0)
    <div class="bg-surface-card border-2 border-blue-500/30 rounded-2xl overflow-hidden shadow-lg shadow-blue-500/5">
        <div class="border-b border-surface-border p-5 bg-gradient-to-r from-blue-500/10 to-transparent">
            <h2 class="text-lg font-bold text-blue-500 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.433 4.433 0 0 0 2.703-2.704 4.5 4.5 0 0 0 4.306-1.758" /></svg>
                {{ __('warming.rw_launchpad_title') }}
            </h2>
            <p class="text-sm text-content-muted mt-1">{{ __('warming.rw_launchpad_desc') }}</p>
        </div>
        
        <form action="{{ route('reverse_warming.start_campaign') }}" method="POST" class="p-6 space-y-6">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-content-primary mb-2">
                    {{ __('warming.rw_target_accounts') }} <span class="text-red-500">*</span>
                </label>
                <div class="max-h-48 overflow-y-auto bg-surface-bg border border-surface-border rounded-xl p-3 space-y-2 relative">
                    @foreach($zohoAccounts as $zoho)
                        <label class="flex items-center gap-3 p-3 hover:bg-surface-nav rounded-lg cursor-pointer border border-transparent hover:border-surface-border transition-all">
                            <input type="checkbox" name="target_accounts[]" value="{{ $zoho->id }}" class="w-4 h-4 text-blue-600 rounded border-surface-border bg-surface-card focus:ring-blue-500 focus:ring-offset-surface-bg" checked>
                            <span class="text-sm font-medium text-content-primary flex-1">{{ $zoho->email }}</span>
                        </label>
                    @endforeach
                    @if($zohoAccounts->isEmpty())
                        <div class="text-sm text-content-secondary text-center py-6 flex flex-col items-center gap-2">
                            <svg class="w-8 h-8 text-surface-border" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            {{ __('warming.rw_no_zoho_accounts') }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-content-primary mb-2">
                        {{ __('warming.rw_email_count') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="email_count" value="5" min="1" max="50" class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary focus:ring-2 focus:ring-blue-500/50 outline-none transition-all">
                    <p class="text-[11px] text-content-muted mt-1.5">{{ __('warming.rw_email_count_help') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-content-primary mb-2">
                        {{ __('warming.rw_delay') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="delay_minutes" value="2" min="0" max="120" class="w-full px-4 py-2.5 bg-surface-bg border border-surface-border rounded-xl text-content-primary focus:ring-2 focus:ring-blue-500/50 outline-none transition-all">
                    <p class="text-[11px] text-content-muted mt-1.5">{{ __('warming.rw_delay_help') }}</p>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="btn-primary w-full md:w-auto" {{ $zohoAccounts->isEmpty() ? 'disabled' : '' }}>
                    <svg class="w-4 h-4 mr-2 inline" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                    {{ __('warming.rw_btn_queue') }}
                </button>
            </div>
        </form>
    </div>
    @else
    <div class="bg-blue-500/10 border border-blue-500/30 rounded-2xl p-6 flex items-start gap-4 shadow-lg shadow-blue-500/5">
        <div class="p-3 bg-blue-500/20 rounded-xl">
            <svg class="w-6 h-6 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
        </div>
        <div>
            <h3 class="font-bold text-blue-400 text-lg">{{ __('warming.rw_launchpad_disabled') }}</h3>
            <p class="text-sm text-blue-300/80 mt-1 leading-relaxed">{{ __('warming.rw_launchpad_disabled_desc') }}</p>
        </div>
    </div>
    @endif

    {{-- Accounts Table --}}
    <div class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden">
        <div class="border-b border-surface-border p-5 bg-surface-nav">
            <h2 class="font-bold text-content-primary">{{ __('warming.rw_gmail_accounts_table') }}</h2>
        </div>
        
        @if($accounts->count() > 0)
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-nav/50 text-xs uppercase text-content-secondary border-b border-surface-border">
                <tr>
                    <th class="px-6 py-4 font-semibold {{ app()->getLocale() == 'ar' ? 'text-right' : 'text-left' }}">{{ __('warming.rw_col_account') }}</th>
                    <th class="px-6 py-4 font-semibold text-center">{{ __('warming.rw_col_daily_limit') }}</th>
                    <th class="px-6 py-4 font-semibold text-center">{{ __('warming.rw_col_status') }}</th>
                    <th class="px-6 py-4 font-semibold text-center">{{ __('warming.rw_col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border whitespace-nowrap">
                @foreach($accounts as $account)
                <tr class="hover:bg-surface-nav/30 transition-colors">
                    <td class="px-6 py-4 font-medium text-content-primary {{ app()->getLocale() == 'ar' ? 'text-right' : 'text-left' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500/20 to-indigo-500/20 text-blue-500 flex items-center justify-center font-bold border border-blue-500/20 shadow-inner">
                                {{ strtoupper(substr($account->email, 0, 1)) }}
                            </div>
                            <div class="flex flex-col">
                                <span>{{ $account->name }}</span>
                                <span class="text-xs text-content-secondary mt-0.5 font-mono">{{ $account->email }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="inline-flex items-center gap-1.5 bg-surface-bg px-3 py-1.5 rounded-lg border border-surface-border">
                            <span class="text-content-primary font-bold text-sm">{{ $account->sent_today }}</span>
                            <span class="text-content-muted text-xs">/ {{ $account->daily_limit }}</span>
                        </div>
                        <div class="w-24 h-1.5 bg-surface-border rounded-full mx-auto mt-2 overflow-hidden shadow-inner">
                            <div class="h-full bg-blue-500 rounded-full transition-all duration-500" style="width: {{ $account->daily_limit > 0 ? min(100, ($account->sent_today / $account->daily_limit) * 100) : 0 }}%"></div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($account->status === 'disconnected')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-red-500/10 text-red-500 border border-red-500/20">
                                {{ __('warming.rw_status_disconnected') }}
                            </span>
                        @elseif($account->is_active)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 {{ app()->getLocale() == 'ar' ? 'ml-1.5' : 'mr-1.5' }} animate-pulse"></span>
                                {{ __('warming.rw_status_active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-zinc-500/10 text-zinc-400 border border-zinc-500/20">
                                {{ __('warming.rw_status_paused') }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <form action="{{ route('reverse_warming.toggle', $account->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors border {{ $account->is_active ? 'border-amber-500/30 text-amber-500 hover:bg-amber-500/10' : 'border-emerald-500/30 text-emerald-500 hover:bg-emerald-500/10' }}">
                                    {{ $account->is_active ? __('warming.rw_btn_pause') : __('warming.rw_btn_resume') }}
                                </button>
                            </form>
                            <form action="{{ route('reverse_warming.destroy', $account->id) }}" method="POST" onsubmit="return confirm('{{ __('warming.rw_confirm_delete') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors border border-red-500/30 text-red-500 hover:bg-red-500/10" title="{{ __('warming.rw_btn_delete') }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="p-12 text-center text-content-secondary flex flex-col items-center">
            <div class="w-16 h-16 bg-surface-border rounded-2xl flex items-center justify-center mb-4 text-zinc-500">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
            </div>
            <p>{{ __('warming.rw_no_gmail_accounts') }}</p>
            <a href="{{ route('reverse_warming.redirect') }}" class="mt-4 text-blue-500 hover:underline font-medium text-sm">{{ __('warming.rw_btn_connect') }} &rarr;</a>
        </div>
        @endif
    </div>

    {{-- Logs Summary --}}
    <div class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden">
        <div class="border-b border-surface-border p-5 bg-surface-nav">
            <h2 class="font-bold text-content-primary">{{ __('warming.rw_logs_title') }}</h2>
        </div>
        @if($logs->count() > 0)
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-nav/50 text-xs uppercase text-content-secondary border-b border-surface-border">
                <tr>
                    <th class="px-6 py-4 {{ app()->getLocale() == 'ar' ? 'text-right' : 'text-left' }}">{{ __('warming.rw_log_time') }}</th>
                    <th class="px-6 py-4 {{ app()->getLocale() == 'ar' ? 'text-right' : 'text-left' }}">{{ __('warming.rw_log_sender') }}</th>
                    <th class="px-6 py-4 {{ app()->getLocale() == 'ar' ? 'text-right' : 'text-left' }}">{{ __('warming.rw_log_receiver') }}</th>
                    <th class="px-6 py-4 {{ app()->getLocale() == 'ar' ? 'text-left' : 'text-left' }}">{{ __('warming.rw_log_status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-border">
                @foreach($logs as $log)
                <tr class="hover:bg-surface-nav/30 transition-colors">
                    <td class="px-6 py-3 text-content-secondary {{ app()->getLocale() == 'ar' ? 'text-right' : 'text-left' }} whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                    <td class="px-6 py-3 text-content-primary font-medium font-mono {{ app()->getLocale() == 'ar' ? 'text-right' : 'text-left' }} whitespace-nowrap">
                        <span class="bg-blue-500/10 text-blue-500 px-2 py-1 rounded-md text-xs border border-blue-500/20">{{ $log->account->email ?? 'N/A' }}</span>
                    </td>
                    <td class="px-6 py-3 text-content-secondary font-mono {{ app()->getLocale() == 'ar' ? 'text-right' : 'text-left' }} whitespace-nowrap">{{ $log->target_email }}</td>
                    <td class="px-6 py-3 text-left">
                        @if($log->status === 'sent')
                            <span class="inline-flex items-center gap-1.5 text-emerald-400 font-medium text-xs">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                {{ __('warming.rw_log_success') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-red-400 font-medium text-xs" title="{{ $log->error_message }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                {{ __('warming.rw_log_failed') }}
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
            <div class="p-8 text-center text-sm text-content-muted flex flex-col items-center gap-3">
                <svg class="w-8 h-8 text-surface-border" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" /></svg>
                {{ __('warming.rw_no_logs') }}
            </div>
        @endif
    </div>
</div>
@endsection
