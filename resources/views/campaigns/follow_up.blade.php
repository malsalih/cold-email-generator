@extends('layouts.app')
@section('title', 'Follow-Up — ' . $campaign->name . ' — ColdForge')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">🔄 إرسال Follow-Up</h1>
            <p class="text-sm text-zinc-400 mt-1">الحملة الأم: {{ $campaign->name }}</p>
        </div>
        <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm text-zinc-400 hover:text-white transition">← تفاصيل الحملة</a>
    </div>

    @if(empty($availableRecipients))
    <div class="bg-amber-500/10 border border-amber-500/20 rounded-2xl p-6 text-center">
        <p class="text-amber-400 font-medium">لا يوجد مستلمين متاحين لـ Follow-Up</p>
        <p class="text-xs text-amber-200/60 mt-1">جميع المستلمين حصلوا على Follow-Up بالفعل أو لا يوجد رسائل مرسلة بعد.</p>
    </div>
    @else
    <form action="{{ route('campaigns.follow_up.store', $campaign) }}" method="POST" class="space-y-6" x-data="{
        selectAll: true,
        selected: @json($availableRecipients),
        allRecipients: @json($availableRecipients),
        toggleAll() {
            if (this.selectAll) {
                this.selected = [...this.allRecipients];
            } else {
                this.selected = [];
            }
        }
    }">
        @csrf

        {{-- Recipients Selection --}}
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                    👥 اختر المستلمين الذين لم يردوا
                </h2>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" x-model="selectAll" @change="toggleAll()"
                           class="w-4 h-4 rounded bg-zinc-800 border-zinc-600 text-amber-500 focus:ring-amber-500/50">
                    <span class="text-xs text-zinc-400">تحديد الكل (<span x-text="selected.length"></span>/{{ count($availableRecipients) }})</span>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-[300px] overflow-y-auto">
                @foreach($availableRecipients as $email)
                <label class="flex items-center gap-3 p-2.5 bg-black/20 rounded-lg border border-zinc-800/30 hover:border-amber-500/20 transition cursor-pointer">
                    <input type="checkbox" name="selected_recipients[]" value="{{ $email }}"
                           x-model="selected"
                           class="w-4 h-4 rounded bg-zinc-800 border-zinc-600 text-amber-500 focus:ring-amber-500/50">
                    <span class="text-xs font-mono text-white truncate">{{ $email }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Follow-Up Content --}}
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4" x-data="{ aiLoading: false, aiError: '' }">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-white flex items-center gap-2">✍️ محتوى Follow-Up</h2>
                <button type="button" @click="
                    aiLoading = true; aiError = '';
                    fetch('{{ route('campaigns.generate_followup_email', $campaign) }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    })
                    .then(r => r.json())
                    .then(data => {
                        aiLoading = false;
                        if (data.success) {
                            document.querySelector('input[name=custom_subject]').value = data.subject;
                            document.querySelector('textarea[name=custom_body]').value = data.body;
                        } else {
                            aiError = data.error || 'فشل في إنشاء الرسالة';
                        }
                    })
                    .catch(e => { aiLoading = false; aiError = e.message; });
                "
                class="flex items-center gap-2 px-4 py-2 text-xs font-medium rounded-xl transition-all duration-200"
                :class="aiLoading ? 'bg-violet-500/20 text-violet-400 border border-violet-500/30 cursor-wait' : 'bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white hover:opacity-90 shadow-lg shadow-violet-500/20'"
                :disabled="aiLoading">
                    <template x-if="!aiLoading">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                            🤖 إنشاء بالذكاء الاصطناعي
                        </span>
                    </template>
                    <template x-if="aiLoading">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            يتم الإنشاء...
                        </span>
                    </template>
                </button>
            </div>
            <template x-if="aiError">
                <div class="px-3 py-2 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-400" x-text="aiError"></div>
            </template>
            <p class="text-[11px] text-zinc-500">💡 اضغط "إنشاء بالذكاء الاصطناعي" لإنشاء رسالة متابعة ذكية مبنية على الرسالة الأصلية التي أُرسلت</p>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-zinc-400">الموضوع</label>
                <input type="text" name="custom_subject" required value="{{ old('custom_subject', 'Re: ' . $campaign->getSubject()) }}"
                       class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all">
            </div>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-zinc-400">النص</label>
                <textarea name="custom_body" rows="8" required placeholder="مرحباً مجدداً,&#10;&#10;أردت المتابعة بخصوص..."
                          class="w-full px-4 py-3 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all font-mono leading-relaxed resize-y">{{ old('custom_body') }}</textarea>
            </div>
        </div>

        {{-- Scheduling --}}
        <div class="bg-gradient-to-br from-amber-500/5 to-orange-500/5 border border-amber-500/20 backdrop-blur-sm rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">📅 جدولة Follow-Up</h2>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">تاريخ البدء</label>
                    <input type="date" name="send_start_date" required value="{{ now()->addDays($campaign->followup_wait_days)->format('Y-m-d') }}"
                           class="w-full px-3 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">ساعة البدء</label>
                    <input type="time" name="send_start_time" required value="{{ $campaign->send_start_time }}"
                           class="w-full px-3 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">ساعة الانتهاء</label>
                    <input type="time" name="send_end_time" required value="{{ $campaign->send_end_time }}"
                           class="w-full px-3 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50">
                </div>
                <div class="space-y-1.5 grid grid-cols-2 gap-2">
                    <div>
                        <label class="text-xs font-medium text-zinc-400">أقل فاصل</label>
                        <input type="number" name="min_delay_minutes" required value="{{ $campaign->min_delay_minutes }}" min="2" max="60"
                               class="w-full px-2 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-amber-500/50">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-zinc-400">أكبر فاصل</label>
                        <input type="number" name="max_delay_minutes" required value="{{ $campaign->max_delay_minutes }}" min="2" max="120"
                               class="w-full px-2 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-1 focus:ring-amber-500/50">
                    </div>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm text-zinc-400 hover:text-white transition">إلغاء</a>
            <button type="submit" class="px-6 py-3 text-sm font-semibold rounded-xl bg-gradient-to-r from-amber-600 to-orange-500 text-white hover:opacity-90 transition-opacity flex items-center gap-2 shadow-lg shadow-amber-500/20">
                🔄 إنشاء Follow-Up #{{ $nextFollowUpNumber }}
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
