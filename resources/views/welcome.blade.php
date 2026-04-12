<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <script>
        (function() {
            const theme = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (theme === 'dark') document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
        })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('welcome.title') }} — {{ __('welcome.subtitle') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-surface-bg text-content-primary antialiased overflow-x-hidden transition-colors duration-300">
    {{-- Glow Effects --}}
    <div class="fixed top-[-10%] left-[-10%] w-[40%] h-[40%] bg-violet-600/10 dark:bg-violet-600/20 blur-[120px] rounded-full"></div>
    <div class="fixed bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-cyan-600/10 dark:bg-cyan-600/20 blur-[120px] rounded-full"></div>

    {{-- Navigation --}}
    <nav class="relative z-50 flex items-center justify-between px-6 py-8 max-w-7xl mx-auto">
        <div class="flex items-center gap-2">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-cyan-600 flex items-center justify-center shadow-lg shadow-violet-500/20">
                <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
            </div>
            <span class="text-xl font-bold tracking-tight">{{ __('welcome.title') }}</span>
        </div>

        <div class="flex items-center gap-6">
            {{-- Language Switcher --}}
            <div class="flex items-center bg-surface-card border border-surface-border rounded-lg p-1 transition-colors">
                <a href="{{ route('lang.switch', 'en') }}" class="px-3 py-1 text-xs font-medium rounded-md transition {{ app()->getLocale() == 'en' ? 'bg-surface-bg text-content-primary shadow-sm' : 'text-content-muted hover:text-content-primary' }}">EN</a>
                <a href="{{ route('lang.switch', 'ar') }}" class="px-3 py-1 text-xs font-medium rounded-md transition {{ app()->getLocale() == 'ar' ? 'bg-surface-bg text-content-primary shadow-sm' : 'text-content-muted hover:text-content-primary' }}">العربية</a>
            </div>

            @if (Route::has('login'))
                <div class="hidden sm:flex items-center gap-4">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm font-medium text-content-muted hover:text-content-primary transition">{{ __('welcome.dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium text-content-muted hover:text-content-primary transition">{{ __('welcome.login') }}</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="px-5 py-2.5 bg-content-primary text-surface-bg rounded-full font-semibold text-sm hover:opacity-90 transition">{{ __('welcome.register') }}</a>
                        @endif
                    @endauth
                </div>
            @endif
        </div>
    </nav>

    {{-- Hero Section --}}
    <main class="relative z-10 px-6 pt-20 pb-32 max-w-7xl mx-auto text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-violet-500/10 border border-violet-500/20 text-violet-400 text-xs font-semibold mb-8 animate-fade-in">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-violet-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-violet-500"></span>
            </span>
            {{ __('welcome.subtitle') }}
        </div>

        <h1 class="text-5xl md:text-7xl font-bold tracking-tight leading-[1.1] mb-6">
            {!! str_replace(['AI', 'الذكاء الاصطناعي'], '<span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-cyan-400">\$0</span>', __('welcome.hero_title')) !!}
        </h1>
        
        <p class="text-content-muted text-lg md:text-xl max-w-2xl mx-auto mb-12 leading-relaxed">
            {{ __('welcome.hero_desc') }}
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('register') }}" class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-violet-600 to-cyan-600 rounded-full font-bold text-lg hover:opacity-90 shadow-xl shadow-violet-500/20 transition-all">
                {{ __('welcome.get_started') }}
            </a>
            <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-4 bg-surface-card border border-surface-border rounded-full font-bold text-lg hover:bg-surface-bg transition-all">
                {{ __('welcome.login') }}
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-32">
            <div class="p-8 rounded-3xl bg-surface-card border border-surface-border backdrop-blur-sm hover:border-violet-500/30 transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400 mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z" /></svg>
                </div>
                <h3 class="text-xl font-bold mb-4">{{ __('welcome.features.warming') }}</h3>
                <p class="text-content-muted text-sm leading-relaxed">{{ __('welcome.features.warming_desc') }}</p>
            </div>

            <div class="p-8 rounded-3xl bg-surface-card border border-surface-border backdrop-blur-sm hover:border-cyan-500/30 transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                </div>
                <h3 class="text-xl font-bold mb-4">{{ __('welcome.features.generator') }}</h3>
                <p class="text-content-muted text-sm leading-relaxed">{{ __('welcome.features.generator_desc') }}</p>
            </div>

            <div class="p-8 rounded-3xl bg-surface-card border border-surface-border backdrop-blur-sm hover:border-violet-400/30 transition-all group">
                <div class="w-12 h-12 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-500 dark:text-violet-400 mb-6 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                </div>
                <h3 class="text-xl font-bold mb-4">{{ __('welcome.features.campaigns') }}</h3>
                <p class="text-content-muted text-sm leading-relaxed">{{ __('welcome.features.campaign_desc') }}</p>
            </div>
        </div>
    </main>

    <footer class="relative z-10 border-t border-zinc-900 py-12 text-center text-zinc-500 text-sm">
        <p>&copy; {{ date('Y') }} {{ __('welcome.title') }}. {{ __('welcome.footer') }}</p>
    </footer>
</body>
</html>
