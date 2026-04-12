@extends('layouts.app')

@section('title', 'Email History — ColdForge')

@section('content')
<div class="space-y-8">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-content-primary">{{ __('generator.email_history') }}</h1>
            <p class="text-sm text-content-muted mt-1">{{ __('generator.email_history_desc') }}</p>
        </div>
        <a href="{{ route('email.index') }}"
           class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl bg-gradient-to-r from-violet-600 to-cyan-600 text-white hover:opacity-90 transition-opacity shadow-lg shadow-violet-500/20">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('generator.btn_generate_new') }}
        </a>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('email.history') }}" class="flex gap-3">
        <div class="relative flex-1">
            <svg class="w-4 h-4 text-zinc-500 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="{{ __('generator.search_placeholder') }}"
                   class="w-full pl-10 pr-4 py-2.5 bg-surface-card border border-surface-border rounded-xl text-content-primary placeholder-content-muted text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500/50 transition-all text-{{ app()->getLocale() == 'en' ? 'left' : 'right' }}">
        </div>
        <button type="submit"
                class="px-5 py-2.5 text-sm font-medium text-content-secondary bg-surface-bg hover:bg-surface-card border border-surface-border rounded-xl transition-colors">
            {{ __('generator.btn_search') }}
        </button>
        @if(request('search'))
            <a href="{{ route('email.history') }}"
               class="px-4 py-2.5 text-sm font-medium text-content-muted hover:text-content-primary bg-surface-bg hover:bg-surface-card border border-surface-border rounded-xl transition-all flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                {{ __('generator.btn_clear') }}
            </a>
        @endif
    </form>

    {{-- Email Grid --}}
    @if($emails->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($emails as $email)
            <a href="{{ route('email.show', $email->id) }}"
               class="bg-surface-card border border-surface-border rounded-2xl p-5 hover:border-violet-500/30 hover:bg-surface-bg transition-all duration-200 group block">
                <div class="space-y-3">
                    {{-- Domain badge --}}
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-violet-500/10 border border-violet-500/20 text-[11px] font-mono text-violet-400">
                            {{ $email->owned_domain }}
                        </span>
                        <span class="text-[11px] text-content-muted">{{ $email->created_at->diffForHumans() }}</span>
                    </div>

                    {{-- Subject --}}
                    <h3 class="text-sm font-semibold text-content-secondary group-hover:text-content-primary transition-colors line-clamp-2">
                        {{ $email->generated_subject }}
                    </h3>

                    {{-- Preview --}}
                    <p class="text-xs text-content-muted line-clamp-3 leading-relaxed">
                        {{ $email->body_preview }}
                    </p>

                    {{-- Meta --}}
                    <div class="flex items-center gap-3 pt-2 border-t border-surface-border">
                        <span class="text-[11px] text-content-muted flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                            @if(is_array($email->generated_variants))
                                {{ __('generator.unique_variants', ['count' => count($email->generated_variants)]) }}
                            @else
                                {{ __('generator.targets_count', ['count' => count($email->target_emails ?? [])]) }}
                            @endif
                        </span>
                        <span class="text-[11px] text-zinc-500 capitalize flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" /></svg>
                            {{ $email->tone }}
                        </span>
                        @if($email->tokens_used)
                        <span class="text-[11px] text-content-muted flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                            {{ number_format($email->tokens_used) }}
                        </span>
                        @endif
                    </div>
                </div>
            </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
            {{ $emails->links() }}
        </div>
    @else
        <div class="text-center py-20 space-y-4">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-surface-bg border border-surface-border flex items-center justify-center">
                <svg class="w-8 h-8 text-content-muted opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 9v.906a2.25 2.25 0 0 1-1.183 1.981l-6.478 3.488M2.25 9v.906a2.25 2.25 0 0 0 1.183 1.981l6.478 3.488m8.839 2.51-4.66-2.51m0 0-1.023-.55a2.25 2.25 0 0 0-2.134 0l-1.022.55m0 0-4.661 2.51m16.5 1.615a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V8.844a2.25 2.25 0 0 1 1.183-1.981l7.5-4.039a2.25 2.25 0 0 1 2.134 0l7.5 4.039a2.25 2.25 0 0 1 1.183 1.98V19.5Z" /></svg>
            </div>
            <div>
                <p class="text-content-muted font-medium">{{ __('generator.no_emails_found') }}</p>
                <p class="text-sm text-content-muted mt-1">
                    @if(request('search'))
                        {{ __('generator.no_results_matching', ['search' => request('search')]) }}
                    @else
                        {{ __('generator.generate_first_prompt') }}
                    @endif
                </p>
            </div>
            <a href="{{ route('email.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium rounded-xl bg-gradient-to-r from-violet-600 to-cyan-600 text-white hover:opacity-90 transition-opacity mt-2">
                {{ __('generator.btn_generate_first') }}
            </a>
        </div>
    @endif
</div>
@endsection
