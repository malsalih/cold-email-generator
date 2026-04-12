@extends('layouts.app')
@section('title', __('warming.sending_settings') . ' — Email Warming')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-content-primary">{{ __('warming.sending_settings') }}</h1>
            <p class="text-sm text-content-muted mt-1">{{ __('warming.sending_settings_desc') }}</p>
        </div>
        <a href="{{ route('warming.dashboard') }}" class="text-sm text-content-muted hover:text-content-primary transition">{{ __('warming.back_to_dashboard') }}</a>
    </div>

    <form action="{{ route('warming.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Send Mode --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-5">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                {{ __('warming.send_mode') }}
            </h2>

            <div class="grid gap-3">
                @foreach([
                    ['auto', __('warming.mode_auto_title'), __('warming.mode_auto_desc'), 'emerald'],
                    ['manual_send', __('warming.mode_manual_send_title'), __('warming.mode_manual_send_desc'), 'amber'],
                    ['full_manual', __('warming.mode_full_manual_title'), __('warming.mode_full_manual_desc'), 'red'],
                ] as [$value, $label, $desc, $color])
                <label class="flex items-start gap-4 p-4 rounded-xl border cursor-pointer transition-all
                    {{ $sendMode === $value ? "bg-{$color}-500/10 border-{$color}-500/30" : 'bg-surface-bg border-surface-border hover:border-orange-500/30' }}">
                    <input type="radio" name="send_mode" value="{{ $value }}" {{ $sendMode === $value ? 'checked' : '' }}
                           class="mt-1 accent-orange-500">
                    <div>
                        <p class="text-sm font-semibold text-content-primary">{{ $label }}</p>
                        <p class="text-xs text-content-muted mt-0.5">{{ $desc }}</p>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Bot Control --}}
        <div class="bg-surface-card border border-surface-border rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-content-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
                {{ __('warming.bot_control') }}
            </h2>
            <p class="text-xs text-content-muted">{{ __('warming.bot_control_desc') }}</p>
            <div class="flex items-center gap-3">
                <a href="{{ route('warming.bot.start') }}" onclick="event.preventDefault(); document.getElementById('start-bot-form').submit();"
                   class="px-4 py-2.5 text-sm font-medium rounded-xl bg-gradient-to-r from-emerald-600 to-green-600 text-white hover:opacity-90 transition-opacity flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
                    {{ __('warming.btn_start_bot') }}
                </a>
                <a href="{{ route('warming.bot.stop') }}" onclick="event.preventDefault(); document.getElementById('stop-bot-form').submit();"
                   class="px-4 py-2.5 text-sm font-medium rounded-xl bg-red-500/10 text-red-400 hover:bg-red-500/20 border border-red-500/20 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9Z" /></svg>
                    {{ __('warming.btn_stop_bot') }}
                </a>
            </div>
        </div>

        <button type="submit" class="px-6 py-3 text-sm font-semibold rounded-xl bg-gradient-to-r from-orange-600 to-red-600 text-white hover:opacity-90 transition-opacity shadow-lg shadow-orange-500/20">
            {{ __('warming.btn_save_settings') }}
        </button>
    </form>

    {{-- Hidden forms for bot control --}}
    <form id="start-bot-form" action="{{ route('warming.bot.start') }}" method="POST" class="hidden">@csrf</form>
    <form id="stop-bot-form" action="{{ route('warming.bot.stop') }}" method="POST" class="hidden">@csrf</form>
</div>
@endsection
