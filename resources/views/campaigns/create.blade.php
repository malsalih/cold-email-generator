@extends('layouts.app')
@section('title', 'إنشاء حملة جديدة — ColdForge')

@section('content')
<div class="max-w-3xl mx-auto space-y-8">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">إنشاء حملة جديدة</h1>
            <p class="text-sm text-zinc-400 mt-1">حدد المحتوى، المستلمين، الجدولة، و Follow-Up</p>
        </div>
        <a href="{{ route('campaigns.index') }}" class="text-sm text-zinc-400 hover:text-white transition">← الحملات</a>
    </div>

    @php
        $pName = old('name', $prefill['name'] ?? '');
        $pRecipients = old('recipients_text', $prefill['recipients'] ?? '');
        $pSubject = old('custom_subject', $prefill['subject'] ?? '');
        $pBody = old('custom_body', $prefill['body'] ?? '');
        $pAccounts = old('warming_account_ids', $prefill['accounts'] ?? []);
        $isMultiVariant = old('multi_variant', $prefill['multi_variant'] ?? false);
        $hasAiSource = $preselectedEmail ? true : false;
    @endphp

    <form action="{{ route('campaigns.store') }}" method="POST" class="space-y-6" x-data="{
        source: '{{ $hasAiSource ? 'ai' : ($pSubject ? 'custom' : 'custom') }}',
        selectedEmailId: '{{ $preselectedEmail->id ?? '' }}',
        autoFollowup: false,
    }">
        @csrf
        @if($isMultiVariant)
        <input type="hidden" name="multi_variant" value="1">
        @endif

        {{-- Campaign Name --}}
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                معلومات الحملة
            </h2>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-zinc-400">اسم الحملة</label>
                <input type="text" name="name" required value="{{ $pName }}" placeholder="مثال: حملة عملاء SaaS — أبريل 2026"
                       class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition-all">
                @error('name') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Email Content Source --}}
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                محتوى البريد
                @if($isMultiVariant)
                <span class="text-[10px] px-2 py-0.5 rounded bg-violet-500/20 text-violet-400 border border-violet-500/30">حملة متعددة الرسائل</span>
                @endif
            </h2>

            @if($isMultiVariant && $preselectedEmail)
                {{-- Multi-variant: show all variants preview --}}
                <div class="space-y-3">
                    <p class="text-xs text-zinc-400">سيتم إرسال كل رسالة لمجموعة الزبائن المخصصة لها تلقائياً:</p>
                    @foreach($preselectedEmail->generated_variants as $vi => $v)
                    <div class="bg-zinc-800/30 border border-zinc-700/30 rounded-xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono text-violet-400">Variant #{{ $vi + 1 }}</span>
                            <span class="text-[10px] text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded">{{ count($v['target_emails'] ?? []) }} recipients</span>
                        </div>
                        <p class="text-sm text-white font-medium">{{ $v['subject'] ?? '' }}</p>
                        <p class="text-xs text-zinc-400 line-clamp-2">{{ Str::limit($v['body'] ?? '', 120) }}</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach(($v['target_emails'] ?? []) as $te)
                            <span class="text-[10px] font-mono text-zinc-400 bg-zinc-700/50 px-1.5 py-0.5 rounded">{{ $te }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                    <input type="hidden" name="generated_email_id" value="{{ $preselectedEmail->id }}">
                </div>
            @else
                <div class="flex gap-2">
                    <button type="button" @click="source = 'ai'" :class="source === 'ai' ? 'bg-violet-500/20 text-violet-400 border-violet-500/30' : 'bg-zinc-800/50 text-zinc-500 border-zinc-700/50'" class="px-4 py-2 text-xs font-medium rounded-lg border transition-all">
                        🤖 من إيميل AI مُولّد
                    </button>
                    <button type="button" @click="source = 'custom'" :class="source === 'custom' ? 'bg-violet-500/20 text-violet-400 border-violet-500/30' : 'bg-zinc-800/50 text-zinc-500 border-zinc-700/50'" class="px-4 py-2 text-xs font-medium rounded-lg border transition-all">
                        ✍️ نص مخصص
                    </button>
                </div>

                <div x-show="source === 'ai'" x-transition class="space-y-3">
                    <label class="text-xs font-medium text-zinc-400">اختر إيميل مُولّد</label>
                    <select name="generated_email_id" x-model="selectedEmailId"
                            class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50">
                        <option value="">— اختر إيميل —</option>
                        @foreach($generatedEmails as $gen)
                            <option value="{{ $gen->id }}" {{ ($preselectedEmail && $preselectedEmail->id == $gen->id) ? 'selected' : '' }}>
                                #{{ $gen->id }} — {{ Str::limit($gen->generated_subject, 60) }} ({{ $gen->owned_domain }})
                            </option>
                        @endforeach
                    </select>
                    <input type="hidden" name="variant_index" value="{{ $prefill['variant_index'] ?? 0 }}">
                </div>

                <div x-show="source === 'custom'" x-transition class="space-y-3">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-400">الموضوع</label>
                        <input type="text" name="custom_subject" value="{{ $pSubject }}" placeholder="موضوع البريد..."
                               class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-400">نص البريد</label>
                        <textarea name="custom_body" rows="8" placeholder="اكتب نص البريد هنا..."
                                  class="w-full px-4 py-3 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition-all font-mono leading-relaxed resize-y">{{ $pBody }}</textarea>
                    </div>
                </div>
            @endif
        </div>

        {{-- Recipients --}}
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" /></svg>
                المستلمون
                @if($isMultiVariant)
                <span class="text-[10px] text-amber-400">⚠️ في الحملة المتعددة يتم التوزيع تلقائياً من الرسائل</span>
                @endif
            </h2>
            <div class="space-y-1.5">
                <label class="text-xs font-medium text-zinc-400">إيميلات العملاء المستهدفين</label>
                <textarea name="recipients_text" rows="5" required placeholder="client1@example.com, client2@example.com&#10;client3@example.com"
                          class="w-full px-4 py-3 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white placeholder-zinc-500 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500/50 transition-all font-mono leading-relaxed resize-y">{{ $pRecipients }}</textarea>
                @error('recipients_text') <p class="text-xs text-red-400">{{ $message }}</p> @enderror
                <p class="text-[11px] text-zinc-500">فصل بفواصل (,) أو فاصلة منقوطة (;) أو أسطر جديدة</p>
            </div>
        </div>

        {{-- Send Later Scheduling --}}
        <div class="bg-gradient-to-br from-cyan-500/5 to-blue-500/5 border border-cyan-500/20 backdrop-blur-sm rounded-2xl p-6 space-y-5">
            <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                📅 جدولة Send Later
            </h2>
            <p class="text-xs text-cyan-200/60">كل إيميل سيُجدول بوقت مختلف خلال ساعات العمل عبر Zoho Send Later. لن يُرسل مباشرة.</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Accounts --}}
                <div class="space-y-1.5 sm:col-span-2">
                    <label class="text-xs font-medium text-cyan-400">حسابات الإرسال المشاركة (Load Balancing)</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
                        @foreach($accounts as $acc)
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-zinc-700/50 bg-zinc-800/20 cursor-pointer hover:bg-zinc-800/50 transition-all has-[:checked]:border-cyan-500/50 has-[:checked]:bg-cyan-500/10">
                                <input type="checkbox" name="warming_account_ids[]" value="{{ $acc->id }}" 
                                       class="w-4 h-4 text-cyan-500 bg-zinc-900 border-zinc-700 rounded focus:ring-cyan-500 focus:ring-2 focus:ring-offset-zinc-800"
                                       @if(in_array($acc->id, $pAccounts)) checked @endif>
                                <div class="flex-1 min-w-0">
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
                    <select name="timezone" required
                            class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50">
                        <option value="Asia/Riyadh">(GMT+03:00) الرياض</option>
                        <option value="Asia/Dubai">(GMT+04:00) دبي</option>
                        <option value="Asia/Baghdad">(GMT+03:00) بغداد</option>
                        <option value="Africa/Cairo">(GMT+02:00) القاهرة</option>
                        <option value="Europe/London">(GMT+00:00) لندن</option>
                        <option value="America/New_York">(GMT-05:00) نيويورك</option>
                        <option value="America/Chicago">(GMT-06:00) شيكاغو</option>
                        <option value="America/Los_Angeles">(GMT-08:00) لوس أنجلوس</option>
                        <option value="America/Detroit">(GMT-04:00) ديترويت</option>
                        <option value="Europe/Berlin">(GMT+01:00) برلين</option>
                        <option value="Europe/Istanbul">(GMT+03:00) إسطنبول</option>
                    </select>
                </div>

                {{-- Start Date --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">تاريخ بدء الإرسال</label>
                    <input type="date" name="send_start_date" required value="{{ old('send_start_date', now()->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50 transition-all">
                </div>

                {{-- Start Time --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">ساعة بدء الإرسال</label>
                    <input type="time" name="send_start_time" required value="{{ old('send_start_time', '09:00') }}"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50 transition-all">
                </div>

                {{-- End Time --}}
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">ساعة انتهاء الإرسال</label>
                    <input type="time" name="send_end_time" required value="{{ old('send_end_time', '17:00') }}"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50 transition-all">
                </div>

                <div class="sm:col-span-2 grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-400">أقل فاصل بين الرسائل (دقائق)</label>
                        <input type="number" name="min_delay_minutes" required value="{{ old('min_delay_minutes', 5) }}" min="2" max="60"
                               class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50 transition-all">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-medium text-zinc-400">أكبر فاصل بين الرسائل (دقائق)</label>
                        <input type="number" name="max_delay_minutes" required value="{{ old('max_delay_minutes', 10) }}" min="2" max="120"
                               class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500/50 transition-all">
                    </div>
                </div>
            </div>
        </div>

        {{-- Follow-Up Settings --}}
        <div class="bg-zinc-900/50 backdrop-blur-sm border border-zinc-800/50 rounded-2xl p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold text-white flex items-center gap-2">
                    🔄 إعدادات Follow-Up
                </h2>
                <label class="flex items-center gap-2 cursor-pointer">
                    <span class="text-[11px] text-zinc-400">Follow-Up تلقائي</span>
                    <input type="checkbox" name="auto_followup" value="1" x-model="autoFollowup"
                           class="w-4 h-4 rounded bg-zinc-800 border-zinc-600 text-amber-500 focus:ring-amber-500/50">
                </label>
            </div>
            <p class="text-xs text-zinc-500">بعد اكتمال الحملة يمكنك إرسال Follow-Up يدوياً أو تلقائياً لمن لم يردّ.</p>
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">أيام الانتظار قبل Follow-Up</label>
                    <input type="number" name="followup_wait_days" value="{{ old('followup_wait_days', 3) }}" min="1" max="30"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-medium text-zinc-400">أقصى عدد Follow-Ups</label>
                    <input type="number" name="max_followups" value="{{ old('max_followups', 3) }}" min="1" max="5"
                           class="w-full px-4 py-2.5 bg-zinc-800/50 border border-zinc-700/50 rounded-xl text-white text-sm focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition-all">
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('campaigns.index') }}" class="text-sm text-zinc-400 hover:text-white transition">إلغاء</a>
            <button type="submit" class="px-6 py-3 text-sm font-semibold rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 text-white hover:opacity-90 transition-opacity flex items-center gap-2 shadow-lg shadow-cyan-500/20">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                إنشاء وجدولة الحملة
            </button>
        </div>
    </form>
</div>
@endsection
