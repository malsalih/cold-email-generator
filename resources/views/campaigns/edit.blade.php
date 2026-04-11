@extends('layouts.app')
@section('title', 'تعديل الحملة — ' . $campaign->name . ' — ColdForge')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">✏️ تعديل الحملة</h1>
            <p class="text-sm text-zinc-400 mt-1">{{ $campaign->name }}</p>
        </div>
        <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm text-zinc-400 hover:text-white transition">← تفاصيل الحملة</a>
    </div>

    @php
        $campaignAccounts = $campaign->warming_account_ids ?? [];
        $recipientsList = is_array($campaign->recipients) ? implode("\n", $campaign->recipients) : '';
    @endphp

    <form action="{{ route('campaigns.update', $campaign) }}" method="POST" class="space-y-6" x-data="{ autoFollowup: {{ $campaign->auto_followup ? 'true' : 'false' }} }">
        @csrf
        @method('PUT')

        {{-- Campaign Name --}}
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                📝 معلومات الحملة
            </h2>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-zinc-400">اسم الحملة</label>
                <input type="text" name="name" required value="{{ old('name', $campaign->name) }}"
                       class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition-all">
                @error('name') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Email Content --}}
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                ✉️ محتوى البريد
                @if($campaign->email_variants)
                <span class="text-[10px] px-2 py-0.5 rounded bg-violet-500/20 text-violet-400 border border-violet-500/30">حملة متعددة الرسائل — التعديل على الرسائل غير مدعوم</span>
                @endif
            </h2>

            @if($campaign->email_variants)
                <p class="text-xs text-zinc-400">هذه حملة متعددة الرسائل. يتم التوزيع تلقائياً حسب الـ variants المحفوظة.</p>
                @foreach($campaign->email_variants as $vi => $v)
                <div class="bg-zinc-800/30 border border-zinc-700/30 rounded-xl p-3 space-y-1">
                    <span class="text-xs font-mono text-violet-400">Variant #{{ $vi + 1 }}</span>
                    <p class="text-sm text-white">{{ $v['subject'] ?? '' }}</p>
                    <p class="text-xs text-zinc-400 line-clamp-2">{{ Str::limit($v['body'] ?? '', 100) }}</p>
                </div>
                @endforeach
            @else
                <div class="space-y-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-400">الموضوع</label>
                        <input type="text" name="custom_subject" value="{{ old('custom_subject', $campaign->custom_subject) }}"
                               class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-400">نص البريد</label>
                        <textarea name="custom_body" rows="8"
                                  class="w-full px-4 py-3 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition-all font-mono leading-relaxed resize-y">{{ old('custom_body', $campaign->custom_body) }}</textarea>
                    </div>
                </div>
            @endif
        </div>

        {{-- Recipients --}}
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">👥 المستلمون</h2>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-zinc-400">إيميلات العملاء المستهدفين</label>
                <textarea name="recipients_text" rows="5" required
                          class="w-full px-4 py-3 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition-all font-mono leading-relaxed resize-y">{{ old('recipients_text', $recipientsList) }}</textarea>
                @error('recipients_text') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Scheduling --}}
        <div class="bg-gradient-to-br from-cyan-500/5 to-blue-500/5 border border-cyan-500/20 backdrop-blur-sm rounded-2xl p-6 space-y-5">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">📅 جدولة Send Later</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Accounts --}}
                <div class="space-y-1.5 sm:col-span-2">
                    <label class="text-xs font-medium text-cyan-400">حسابات الإرسال</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
                        @foreach($accounts as $acc)
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-zinc-700/50 bg-zinc-800/20 cursor-pointer hover:bg-zinc-800/50 transition-all has-[:checked]:border-cyan-500/50 has-[:checked]:bg-cyan-500/10">
                            <input type="checkbox" name="warming_account_ids[]" value="{{ $acc->id }}"
                                   class="w-4 h-4 text-cyan-500 bg-zinc-900 border-zinc-700 rounded"
                                   @if(in_array($acc->id, $campaignAccounts)) checked @endif>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-white truncate">{{ $acc->display_name }}</p>
                                <p class="text-[10px] text-zinc-400 truncate">{{ $acc->email }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('warming_account_ids') <p class="text-xs text-red-400 mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Timezone --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">المنطقة الزمنية</label>
                    <select name="timezone" required class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                        @foreach(['Asia/Riyadh' => 'الرياض', 'Asia/Dubai' => 'دبي', 'Asia/Baghdad' => 'بغداد', 'Africa/Cairo' => 'القاهرة', 'Europe/London' => 'لندن', 'America/New_York' => 'نيويورك', 'America/Chicago' => 'شيكاغو', 'America/Los_Angeles' => 'لوس أنجلوس', 'America/Detroit' => 'ديترويت', 'Europe/Berlin' => 'برلين', 'Europe/Istanbul' => 'إسطنبول'] as $tz => $label)
                        <option value="{{ $tz }}" @if(($campaign->timezone ?? '') === $tz) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">تاريخ البدء</label>
                    <input type="date" name="send_start_date" required value="{{ old('send_start_date', optional($campaign->send_start_date)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">ساعة البدء</label>
                    <input type="time" name="send_start_time" required value="{{ old('send_start_time', $campaign->send_start_time ?? '09:00') }}"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">ساعة الانتهاء</label>
                    <input type="time" name="send_end_time" required value="{{ old('send_end_time', $campaign->send_end_time ?? '17:00') }}"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                </div>

                <div class="sm:col-span-2 grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-400">أقل فاصل (دقائق)</label>
                        <input type="number" name="min_delay_minutes" required value="{{ old('min_delay_minutes', $campaign->min_delay_minutes ?? 5) }}" min="2" max="60"
                               class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-400">أكبر فاصل (دقائق)</label>
                        <input type="number" name="max_delay_minutes" required value="{{ old('max_delay_minutes', $campaign->max_delay_minutes ?? 10) }}" min="2" max="120"
                               class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                    </div>
                </div>
            </div>
        </div>

        {{-- Follow-Up --}}
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-white">🔄 إعدادات Follow-Up</h2>
                <label class="flex items-center gap-2 cursor-pointer">
                    <span class="text-[11px] text-zinc-400">Follow-Up تلقائي</span>
                    <input type="checkbox" name="auto_followup" value="1" x-model="autoFollowup"
                           class="w-4 h-4 rounded bg-zinc-800 border-zinc-600 text-amber-500 focus:ring-amber-500/50">
                </label>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">أيام الانتظار</label>
                    <input type="number" name="followup_wait_days" value="{{ old('followup_wait_days', $campaign->followup_wait_days ?? 3) }}" min="1" max="30"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">أقصى عدد Follow-Ups</label>
                    <input type="number" name="max_followups" value="{{ old('max_followups', $campaign->max_followups ?? 3) }}" min="1" max="5"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('campaigns.show', $campaign) }}" class="text-sm text-zinc-400 hover:text-white transition">إلغاء</a>
            <button type="submit" class="px-6 py-3 text-sm font-semibold rounded-xl bg-gradient-to-r from-amber-600 to-orange-500 text-white hover:opacity-90 transition-opacity flex items-center gap-2 shadow-lg shadow-amber-500/20">
                💾 حفظ التغييرات وإعادة الجدولة
            </button>
        </div>
    </form>
</div>
@endsection
