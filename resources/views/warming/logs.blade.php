@extends('layouts.app')
@section('title', __('warming.sending_logs') . ' — Email Warming')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-content-primary">{{ __('warming.sending_logs') }}</h1>
            <p class="text-sm text-content-muted mt-1">{{ __('warming.sending_logs_desc') }}</p>
        </div>
        <a href="{{ route('warming.dashboard') }}" class="text-sm text-content-muted hover:text-content-primary transition">{{ __('warming.back_to_dashboard') }}</a>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('warming.logs') }}" class="flex flex-wrap gap-3">
        <select name="source" class="px-4 py-2 bg-surface-card border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/50 appearance-none cursor-pointer">
            <option value="">{{ __('warming.filter_all_accounts') }}</option>
            @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->email }}</option>
            @endforeach
        </select>
        <select name="status" class="px-4 py-2 bg-surface-card border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/50 appearance-none cursor-pointer">
            <option value="">{{ __('warming.filter_all_statuses') }}</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('warming.status_pending') }}</option>
            <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>{{ __('warming.status_sent') }}</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>{{ __('warming.status_failed') }}</option>
        </select>
        <select name="source" class="px-4 py-2 bg-surface-card border border-surface-border rounded-xl text-content-primary text-sm focus:outline-none focus:ring-2 focus:ring-orange-500/50 appearance-none cursor-pointer">
            <option value="">{{ __('warming.filter_all_sources') }}</option>
            <option value="warming" {{ request('source') === 'warming' ? 'selected' : '' }}>{{ __('warming.source_warming') }}</option>
            <option value="campaign" {{ request('source') === 'campaign' ? 'selected' : '' }}>{{ __('warming.source_campaign') }}</option>
        </select>
        <button type="submit" class="px-5 py-2 text-sm font-medium text-content-secondary bg-surface-bg hover:bg-surface-card border border-surface-border rounded-xl transition-colors">
            {{ __('warming.btn_filter') }}
        </button>
        @if(request()->hasAny(['account_id', 'status', 'source']))
            <a href="{{ route('warming.logs') }}" class="px-4 py-2 text-sm text-content-muted hover:text-content-primary transition flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                {{ __('warming.clear_filters') }}
            </a>
        @endif
    </form>

    {{-- Logs Table --}}
    <div class="bg-surface-card border border-surface-border rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-surface-border bg-surface-bg/30">
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider p-4">{{ __('warming.col_status') }}</th>
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider p-4">{{ __('warming.col_actions') }}</th>
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider p-4">{{ __('warming.col_recipient') }}</th>
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider p-4">{{ __('warming.col_subject') }}</th>
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider p-4">{{ __('warming.col_source') }}</th>
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider p-4">{{ __('warming.col_time') }}</th>
                        <th class="text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }} text-xs font-medium text-content-muted uppercase tracking-wider p-4">{{ __('warming.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr class="border-b border-surface-border hover:bg-surface-bg transition-colors">
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
                        <td class="p-4 text-xs text-content-secondary">
                            {{ $log->account->email ?? '—' }}
                        </td>
                        <td class="p-4 text-xs font-mono text-content-muted">
                            {{ $log->recipient_email }}
                        </td>
                        <td class="p-4 text-xs text-content-secondary max-w-xs truncate">
                            {{ $log->subject_sent }}
                        </td>
                        <td class="p-4">
                            <span class="text-[11px] px-2 py-0.5 rounded-md {{ $log->source_type === 'warming' ? 'bg-orange-500/10 text-orange-600 dark:text-orange-400' : 'bg-violet-500/10 text-violet-600 dark:text-violet-400' }}">
                                {{ $log->source_type === 'warming' ? __('warming.source_warming') : __('warming.source_campaign') }}
                            </span>
                        </td>
                        <td class="p-4 text-xs text-content-muted">
                            {{ $log->sent_at ? $log->sent_at->diffForHumans() : ($log->scheduled_at ? $log->scheduled_at->format('Y-m-d H:i') : $log->created_at->diffForHumans()) }}
                        </td>
                        <td class="p-4">
                            @if($log->status === 'failed')
                                <form action="{{ route('warming.logs.retry', $log) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 text-[11px] font-medium rounded-lg bg-orange-500/10 text-orange-400 hover:bg-orange-500/20 transition flex items-center gap-1.5">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                        {{ __('warming.btn_retry') }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @if($log->error_message)
                    <tr class="border-b border-surface-border">
                        <td colspan="7" class="px-4 pb-3 pt-0">
                            <p class="text-[11px] text-red-400 bg-red-500/5 rounded-lg px-3 py-1.5">⚠ {{ $log->error_message }}</p>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="7" class="p-12 text-center text-content-muted">
                            {{ __('warming.no_logs_yet') }}
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
