@extends('layouts.app')
@section('title', 'القوالب — Email Warming')

@section('content')
<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">قوالب التسخين</h1>
            <p class="text-sm text-zinc-400 mt-1">إيميلات شخصية طبيعية تُستخدم في عملية التسخين</p>
        </div>
        <a href="{{ route('warming.dashboard') }}" class="text-sm text-zinc-400 hover:text-white transition">← لوحة التحكم</a>
    </div>

    {{-- Add Template Form --}}
    <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center justify-between w-full text-left">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-fuchsia-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                إنشاء قالب جديد
            </h2>
            <svg class="w-4 h-4 text-zinc-500 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
        </button>

        <form action="{{ route('warming.templates.store') }}" method="POST" x-show="open" x-transition class="mt-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">اسم القالب</label>
                    <input type="text" name="name" required placeholder="تحية شخصية ودية"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-fuchsia-500/50 transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">الفئة</label>
                    <select name="category" class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-fuchsia-500/50 transition-all appearance-none cursor-pointer">
                        @foreach($categories as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-zinc-400">العنوان <span class="text-zinc-600">(يمكنك استخدام {name}, {company}, {date})</span></label>
                <input type="text" name="subject" required placeholder="مرحباً {name} — كيف الحال؟"
                       class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-fuchsia-500/50 transition-all">
            </div>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-zinc-400">المحتوى</label>
                <textarea name="body" required rows="6" placeholder="مرحباً {name}،&#10;&#10;أتمنى أن تكون بخير. أردت فقط أن أطمئن عليك وأسأل كيف الأمور عندكم في {company}.&#10;&#10;تحياتي،&#10;محمد"
                          class="w-full px-4 py-3 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm leading-relaxed focus:outline-none focus:ring-2 focus:ring-fuchsia-500/50 transition-all resize-none"></textarea>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="px-5 py-2.5 text-sm font-medium rounded-xl bg-gradient-to-r from-fuchsia-600 to-violet-600 text-white hover:opacity-90 transition-opacity">
                    حفظ القالب
                </button>
                <p class="text-[11px] text-zinc-500">المتغيرات المتاحة: <code class="text-fuchsia-400">{name}</code> <code class="text-fuchsia-400">{company}</code> <code class="text-fuchsia-400">{date}</code> <code class="text-fuchsia-400">{day}</code> <code class="text-fuchsia-400">{time}</code></p>
            </div>
        </form>
    </div>

    {{-- Templates Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse($templates as $template)
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-5 hover:border-zinc-700/50 transition-colors group" x-data="{ editing: false }">
            {{-- View Mode --}}
            <div x-show="!editing">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-fuchsia-500/10 text-fuchsia-400 border border-fuchsia-500/20">
                            {{ $categories[$template->category] ?? $template->category }}
                        </span>
                        @if(!$template->is_active)
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-zinc-700/50 text-zinc-400">معطل</span>
                        @endif
                    </div>
                    <span class="text-[11px] text-zinc-500">استُخدم {{ $template->times_used }} مرة</span>
                </div>
                <h3 class="text-sm font-semibold text-white">{{ $template->name }}</h3>
                <p class="text-xs text-zinc-400 mt-1 font-mono">العنوان: {{ $template->subject }}</p>
                <p class="text-xs text-zinc-500 mt-2 line-clamp-3 leading-relaxed">{{ $template->body_preview }}</p>

                @if(!empty($template->variables))
                <div class="flex flex-wrap gap-1 mt-3">
                    @foreach($template->variables as $var)
                        <code class="text-[10px] px-1.5 py-0.5 rounded bg-fuchsia-500/10 text-fuchsia-400">{{ $var }}</code>
                    @endforeach
                </div>
                @endif

                <div class="flex items-center gap-2 mt-4 pt-3 border-t border-zinc-800/50">
                    <button @click="editing = true" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-zinc-800/50 text-zinc-400 hover:text-white hover:bg-zinc-800 transition-all">تعديل</button>
                    <form action="{{ route('warming.templates.delete', $template) }}" method="POST" class="inline" onsubmit="return confirm('حذف هذا القالب؟')">
                        @csrf @method('DELETE')
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-lg text-red-400 hover:bg-red-500/10 transition-all">حذف</button>
                    </form>
                </div>
            </div>

            {{-- Edit Mode --}}
            <form x-show="editing" x-transition action="{{ route('warming.templates.update', $template) }}" method="POST" class="space-y-3">
                @csrf @method('PUT')
                <input type="text" name="name" value="{{ $template->name }}" class="w-full px-3 py-2 bg-zinc-800/50 border border-zinc-700/50 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-fuchsia-500/50">
                <select name="category" class="w-full px-3 py-2 bg-zinc-800/50 border border-zinc-700/50 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-fuchsia-500/50 appearance-none">
                    @foreach($categories as $val => $label)
                        <option value="{{ $val }}" {{ $template->category === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="subject" value="{{ $template->subject }}" class="w-full px-3 py-2 bg-zinc-800/50 border border-zinc-700/50 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-fuchsia-500/50">
                <textarea name="body" rows="4" class="w-full px-3 py-2 bg-zinc-800/50 border border-zinc-700/50 rounded-lg text-white text-sm focus:outline-none focus:ring-2 focus:ring-fuchsia-500/50 resize-none">{{ $template->body }}</textarea>
                <label class="flex items-center gap-2 text-xs text-zinc-400">
                    <input type="checkbox" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }} class="rounded border-zinc-600 bg-zinc-800 text-fuchsia-500 focus:ring-fuchsia-500/50">
                    مفعّل
                </label>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 text-xs font-medium rounded-lg bg-fuchsia-600 text-white hover:bg-fuchsia-500 transition">حفظ</button>
                    <button type="button" @click="editing = false" class="px-4 py-2 text-xs font-medium rounded-lg bg-zinc-800 text-zinc-400 hover:text-white transition">إلغاء</button>
                </div>
            </form>
        </div>
        @empty
        <div class="col-span-2 text-center py-16 bg-zinc-900/50 border border-zinc-800/50 rounded-2xl">
            <p class="text-zinc-400 font-medium">لم تنشئ أي قوالب بعد</p>
            <p class="text-sm text-zinc-500 mt-1">افتح نموذج الإنشاء أعلاه لإضافة قالب إيميل للتسخين</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
