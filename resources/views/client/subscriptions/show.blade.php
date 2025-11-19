<x-client-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('client.home') }}">اللوحة</a></li>
                <li class="breadcrumb-item"><a href="{{ route('client.subscriptions') }}">اشتراكاتي</a></li>
                <li class="breadcrumb-item" aria-current="page">#{{ $subscription->id }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">تفاصيل الاشتراك</h2>
            </div>
        </div>
    </div>

    @if (session('ok'))
        <div class="alert alert-success mb-4">{{ session('ok') }}</div>
    @endif

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 lg:col-span-4">
            <div class="card p-6 space-y-4">
                <div>
                    <p class="text-sm text-gray-500">القالب</p>
                    <p class="font-semibold text-lg">{{ $subscription->template?->translation()?->name ?? $subscription->template?->name ?? '-' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <p class="text-gray-400 text-xs">الخطة</p>
                        <p class="font-medium">{{ $subscription->plan?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">نوع الخطة</p>
                        <p class="font-medium">
                            {{ $subscription->plan?->isHosting() ? 'استضافة / WordPress' : 'داخل Palgoals' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">النطاق</p>
                        <p class="font-medium break-words">{{ $subscription->domain_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">حالة التفعيل</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            @if ($subscription->provisioning_status === 'active') bg-emerald-100 text-emerald-800
                            @elseif($subscription->provisioning_status === 'failed') bg-red-100 text-red-800
                            @else bg-yellow-100 text-yellow-800 @endif">
                            {{ __($subscription->provisioning_status) }}
                        </span>
                    </div>
                </div>
                <div class="text-xs text-gray-400">
                    <p>أُنشئ في {{ optional($subscription->provisioned_at)->format('Y-m-d H:i') ?? '—' }}</p>
                    <p>آخر تزامن: {{ optional($subscription->last_synced_at)->diffForHumans() ?? '—' }}</p>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8">
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">الصفحات المنشأة</h3>
                        <p class="text-sm text-gray-500">يمكنك تعديل هذه الصفحات لاحقاً من أدوات البناء.</p>
                    </div>
                    <a href="{{ route('client.subscriptions') }}" class="btn btn-outline-primary text-sm">العودة للقائمة</a>
                </div>

                @forelse ($subscription->pages as $page)
                    @php
                        $pageTrans = $page->translations->firstWhere('locale', $locale)
                            ?? $page->translations->first();
                    @endphp
                    <div class="border rounded-xl mb-4">
                        <div class="px-4 py-3 bg-gray-50 rounded-t-xl flex items-center justify-between">
                            <div>
                                <h4 class="font-semibold text-gray-800">
                                    {{ $pageTrans->title ?? $page->slug }}
                                    @if ($page->is_home)
                                        <span class="text-xs text-primary ms-2">الصفحة الرئيسية</span>
                                    @endif
                                </h4>
                                <p class="text-xs text-gray-500">
                                    slug: {{ $pageTrans->slug ?? $page->slug }}
                                </p>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $page->is_active ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-600' }}">
                                {{ $page->is_active ? 'نشطة' : 'معطلة' }}
                            </span>
                        </div>
                        <div class="p-4 space-y-4">
                            @forelse ($page->sections as $section)
                                @php
                                    $sectionTrans = $section->translations->firstWhere('locale', $locale)
                                        ?? $section->translations->first();
                                    $content = $sectionTrans->content ?? [];
                                    $isEditing = old('section_id') == $section->id;
                                    $oldTitle = $isEditing ? old('title') : null;
                                    $oldContent = $isEditing ? old('content') : null;
                                @endphp
                                <div class="border border-dashed rounded-lg p-4">
                                    <p class="text-xs uppercase tracking-wide text-gray-400">
                                        {{ $section->key ?? 'section' }} · ترتيب {{ $section->sort_order }}
                                    </p>
                                    <h5 class="text-base font-semibold mt-1">{{ $sectionTrans->title ?? '---' }}</h5>
                                    <form method="POST"
                                        action="{{ route('client.subscriptions.sections.update', [$subscription, $section]) }}"
                                        class="mt-4 space-y-3">
                                        @csrf
                                        <input type="hidden" name="section_id" value="{{ $section->id }}">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">عنوان القسم</label>
                                            <input type="text" name="title"
                                                value="{{ $oldTitle ?? $sectionTrans->title }}"
                                                class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-primary/20"
                                                placeholder="مثال: رسوم التصميم تبدأ من ...">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 mb-1">
                                                محتوى يظهر في الموقع (نص عادي أو عناصر متعددة)
                                            </label>
                                            <textarea name="content" rows="5"
                                                class="w-full border rounded-lg px-3 py-2 font-mono text-xs focus:ring focus:ring-primary/20"
                                                placeholder='مثال بسيط: نحن نخدمكم 24/7
مثال متقدم: { "title": "مميزاتنا", "items": [{ "text": "سرعة التنفيذ" }] }'>{{ $oldContent ?? (is_array($content) ? json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (is_string($content) ? $content : '')) }}</textarea>
                                            <p class="text-xs text-gray-400 mt-1">اكتب نصاً عادياً، أو استخدم التنسيق المتقدم (JSON) إذا أردت عناصر متعددة.</p>
                                            @if ($errors->has('content_' . $section->id))
                                                <p class="text-xs text-red-600 mt-1">{{ $errors->first('content_' . $section->id) }}</p>
                                            @endif
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            @if (session('last_section_id') == $section->id)
                                                <span class="text-xs text-emerald-600 self-center">تم الحفظ للتو.</span>
                                            @endif
                                            <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-primary text-white text-sm hover:bg-primary/90">
                                                حفظ التعديلات
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">لا توجد أقسام لهذه الصفحة بعد.</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-12">
                        لا توجد صفحات بعد لهذا الاشتراك. أعد التفعيل أو تواصل مع الدعم.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-client-layout>
