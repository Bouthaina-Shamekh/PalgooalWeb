@php
    // تحديد RTL: نحاول من جدول اللغات ثم نعمل fallback حسب كود اللغة
    $active = $activeLang ?? app()->getLocale();
    $isRtl = optional(
        $languages instanceof \Illuminate\Support\Collection
            ? $languages->firstWhere('code', $active)
            : collect($languages)->firstWhere('code', $active),
    )->is_rtl;
    if ($isRtl === null) {
        $isRtl = in_array($active, ['ar', 'fa', 'ur', 'he']);
    }
@endphp

<div class="space-y-6" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
    <h2 class="text-xl font-bold mb-4">إدارة سكشنات الصفحة</h2>

    {{-- رسائل --}}
    @if (session()->has('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded">{{ session('success') }}</div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-100 text-red-800 px-4 py-2 rounded">{{ session('error') }}</div>
    @endif

    {{-- عرض السكشنات الحالية --}}
    @foreach ($sections as $section)
        @switch($section->key)
            @case('hero')
                <livewire:admin.sections.hero-section :section="$section" wire:key="hero-{{ $section->id }}" />
            @break

            @case('features')
                <livewire:admin.sections.features-section :section="$section" wire:key="features-{{ $section->id }}" />
            @break

            @case('features-2')
                <livewire:admin.sections.features2-section :section="$section" wire:key="features-2-{{ $section->id }}" />
            @break

            @case('features-3')
                <livewire:admin.sections.features-section :section="$section" wire:key="features-3-{{ $section->id }}" />
            @break

            @case('cta')
                <livewire:admin.sections.cta-section :section="$section" wire:key="cta-{{ $section->id }}" />
            @break

            @case('banner')
                <livewire:admin.sections.banner-section :section="$section" wire:key="banner-{{ $section->id }}" />
            @break

            @case('services')
                <livewire:admin.sections.services-section :section="$section" wire:key="services-{{ $section->id }}" />
            @break

            @case('works')
                <livewire:admin.sections.works-section :section="$section" wire:key="works-{{ $section->id }}" />
            @break

            @case('home-works')
                <livewire:admin.sections.home-works-section :section="$section" wire:key="home-works-{{ $section->id }}" />
            @break

            @case('templates')
                <livewire:admin.sections.templates-section :section="$section" wire:key="templates-{{ $section->id }}" />
            @break

            @case('testimonials')
                <livewire:admin.sections.testimonials-section :section="$section"
                    wire:key="testimonials-{{ $section->id }}" />
            @break

            @case('blog')
                <livewire:admin.sections.blogs-section :section="$section" wire:key="blog-{{ $section->id }}" />
            @break

            @case('search-domain')
                <livewire:admin.sections.search-domain-section :section="$section"
                    wire:key="search-domain-{{ $section->id }}" />
            @break

            @case('hosting-plans')
                <livewire:admin.sections.hosting-plan-section :section="$section"
                    wire:key="hosting-plans-{{ $section->id }}" />
            @break

            @case('faq')
                <livewire:admin.sections.faq-section :section="$section" wire:key="faq-{{ $section->id }}" />
            @break

            @case('templates-pages')
                <livewire:admin.sections.templates-pages-section :section="$section"
                    wire:key="templates-pages-{{ $section->id }}" />
            @break

            @default
                <div class="p-4 bg-gray-100 rounded shadow">سكشن غير مدعوم حالياً: {{ $section->key }}</div>
        @endswitch
    @endforeach

    {{-- زر فتح الودجتس (سايدبار) --}}
    <div class="mt-10 border-t pt-6 flex items-center gap-3">
        <button type="button" wire:click="openPalette"
            class="bg-primary text-white px-6 py-2 rounded hover:bg-primary/90 transition">
            + أضف سكشن
        </button>
    </div>

    {{-- الشريط الجانبي (Widget Palette) --}}
    <div wire:key="sections-palette"
         class="fixed inset-y-0 z-50 w-[380px] max-w-[90vw] bg-white dark:bg-gray-900 shadow-2xl
               transition-transform duration-300
               {{ $isRtl ? 'left-0 border-r' : 'right-0 border-l' }}
           flex flex-col h-screen overflow-hidden"
        style="{{ $showPalette
            ? 'transform: translateX(0);'
            : ($isRtl
                ? 'transform: translateX(-105%);'
                : 'transform: translateX(105%);') }}"
        aria-hidden="{{ $showPalette ? 'false' : 'true' }}">
        {{-- رأس اللوحة --}}
        <div class="shrink-0 flex items-center justify-between px-4 py-3 border-b">
            <h3 class="text-base font-semibold">أضف سكشن</h3>
            <button type="button" class="text-gray-500 hover:text-gray-700" wire:click="closePalette"
                aria-label="إغلاق">✕</button>
        </div>

        {{-- أدوات --}}
        <div class="shrink-0 p-4 space-y-3 border-b">
            <input type="search" wire:model.live.debounce.300ms="paletteSearch" class="w-full border rounded px-3 py-2"
                placeholder="ابحث (مثال: hero, features, blog)">

            <input type="number" wire:model="paletteOrder" class="w-full border rounded px-3 py-2"
                placeholder="الترتيب (اختياري)">
        </div>

        {{-- شبكة الودجتس (هي التي تتمدّد وتسكرول) --}}
        <div class="flex-1 min-h-0 overflow-y-auto p-4">
            <div class="grid grid-cols-1 gap-3">
                @forelse ($paletteKeys as $k)
                    @php
                        $meta = $keyMeta[$k] ?? ['label' => $k, 'desc' => '', 'unique' => false, 'thumb' => null];
                    @endphp

                    <div class="border rounded-xl hover:shadow-md transition overflow-hidden" wire:key="palette-item-{{ $k }}">
                        @if (!empty($meta['thumb']))
                            <div class="aspect-[16/9] bg-gray-100">
                                <img src="{{ $meta['thumb'] }}" alt="{{ $meta['label'] }}"
                                    class="w-full h-full object-cover">
                            </div>
                        @endif

                        <div class="p-3 space-y-2">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="font-semibold">{{ $meta['label'] }}</div>
                                    @if ($meta['desc'])
                                        <div class="text-xs text-gray-500 mt-1">{{ $meta['desc'] }}</div>
                                    @endif
                                    @if (!empty($meta['unique']))
                                        <span
                                            class="inline-block mt-2 text-[11px] px-2 py-0.5 rounded bg-amber-100 text-amber-800">
                                            فريد (مرة واحدة)
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center justify-end">
                                <button type="button" wire:click.stop="addFromPalette('{{ $k }}')"
                                    wire:loading.attr="disabled" wire:target="addFromPalette"
                                    class="px-3 py-1.5 rounded bg-primary text-white text-sm hover:bg-primary/90">
                                    إضافة
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-8">لا توجد نتائج مطابقة لبحثك.</div>
                @endforelse
            </div>
        </div>
    </div>


    {{-- خلفية لإغلاق اللوحة عند الضغط خارجها --}}
    @if ($showPalette)
        <button type="button" class="fixed inset-0 z-40 bg-black/40" wire:click="closePalette"
            aria-label="إغلاق اللوحة"></button>
    @endif
</div>
