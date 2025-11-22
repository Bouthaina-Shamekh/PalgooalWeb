@php
    use App\Models\Service;
@endphp
<x-template.layouts.index-layouts title="بال قول لتكنولوجيا المعلومات - مواقع الكترونية واستضافة عربية"
    description="شركة فلسطينية متخصصة في برمجة وتصميم المواقع الالكترونية..."
    keywords="خدمات حجز دومين , افضل شركة برمجيات , استضافة مواقع , ..."
    ogImage="{{ asset('assets/images/services.jpg') }}">

    <!-- Banner Section -->
    <section
        class="bg-primary text-white py-28 px-4 sm:px-12 lg:px-36 flex flex-col items-center justify-center text-center overflow-hidden">
        <!-- المحتوى -->
        <div class="relative z-10 max-w-3xl mx-auto">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold font-almarai mb-6 drop-shadow-lg animate-fade-in">
                أعمالنا الرقمية المميزة
            </h1>
            <p class="text-lg md:text-2xl text-gray-100/90 font-cairo mb-8 leading-relaxed animate-fade-in">
                استكشف مجموعة من المشاريع الرقمية التي نفذناها باحترافية عالية وجودة تصميم متقدمة لعملائنا في مختلف
                القطاعات.
            </p>
            <!-- Breadcrumb -->
            <nav class="text-sm text-gray-200 animate-fade-in" aria-label="breadcrumb">
                <ol class="inline-flex items-center justify-center gap-2 rtl:space-x-reverse">
                    <li>
                        <a href="/" class="hover:text-secondary transition-colors font-medium">الرئيسية</a>
                    </li>
                    <li>/</li>
                    <li class="text-white font-semibold">
                        <a href="" class="hover:text-secondary transition-colors">أعمالنا</a>
                    </li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Our Business Single Section -->
    <section class="py-20 px-4 sm:px-8 lg:px-36 bg-white dark:bg-[#18181b]" dir="auto">
        <!-- المحتوى الرئيسي -->
        <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <!-- صورة المشروع -->
            <div
                class="rounded-3xl overflow-hidden shadow-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-[#23232a] aspect-[4/3] transition-transform duration-300 hover:scale-[1.02]">
                <img src="{{ asset('storage/' . $portfolio->default_image) }}"
                    alt="عرض {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->title }}"
                    class="w-full h-full object-contain" loading="lazy" decoding="async" />
            </div>
            <!-- تفاصيل المشروع -->
            <div>
                <h2
                    class="text-3xl md:text-4xl font-extrabold text-primary dark:text-white mb-4 font-almarai leading-tight">
                    {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->title }}
                </h2>
                <div class="flex flex-wrap gap-4 text-sm text-gray-500 dark:text-gray-300 mb-6">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-secondary" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" />
                        </svg>
                        مدة التنفيذ:
                        <span
                            class="font-semibold text-primary dark:text-yellow-400">{{ $portfolio->implementation_period_days }}
                            يوم</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-secondary" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        الحالة:
                        <span class="font-semibold text-green-600 dark:text-green-400">
                            {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->status }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-secondary" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14c-4.418 0-8 1.79-8 4v2h16v-2c0-2.21-3.582-4-8-4z" />
                        </svg>
                        العميل:
                        <span class="font-semibold text-primary dark:text-yellow-400">{{ $portfolio->client }}</span>
                    </div>
                </div>
                <p class="text-base sm:text-lg text-gray-700 dark:text-gray-200 leading-relaxed mb-6">
                    {{ $portfolio->translations()->where('locale', app()->getLocale())->first()->description }}
                </p>
                <div class="flex flex-wrap gap-3 mb-6">
                    @php
                        $types = explode(
                            ',',
                            $portfolio
                                ->translations()
                                ->where('locale', app()->getLocale())
                                ->first()?->type ?? '',
                        );
                    @endphp

                    @foreach ($types as $type)
                        <span class="badge">{{ trim($type) }}</span>
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ $portfolio->translations()->where('locale', app()->getLocale())->first()->link }}"
                        target="_blank" rel="noopener" class="btn-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 3v4a1 1 0 001 1h4" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 8V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2H7a2 2 0 01-2-2V8z" />
                        </svg>
                        زيارة الموقع
                    </a>
                    <a href="#contact" class="btn-outline">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        اطلب مشروع مشابه
                    </a>
                </div>
            </div>
        </div>
        <!-- المعرض -->
        <div class="mt-20">
            <h3 class="text-2xl font-bold text-primary dark:text-white mb-8 text-center">معرض صور المشروع</h3>
            @php
                // تطبيع حقل الصور لقبول JSON أو CSV أو مصفوفة
                $images = [];
                $rawImages = $portfolio->images ?? null;
                if (is_string($rawImages)) {
                    $decoded = json_decode($rawImages, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $images = $decoded;
                    } else {
                        $csv = array_filter(array_map('trim', explode(',', $rawImages)));
                        $images = $csv ?: (strlen($rawImages) ? [$rawImages] : []);
                    }
                } elseif (is_array($rawImages)) {
                    $images = $rawImages;
                } elseif ($rawImages instanceof \Illuminate\Support\Collection) {
                    $images = $rawImages->toArray();
                }
                // إزالة القيم الفارغة
                $images = array_values(array_filter($images, fn($v) => !empty($v)));
            @endphp

            @if (count($images))
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    @foreach ($images as $i => $img)
                        <a data-fancybox="gallery" href="{{ asset('storage/' . $img) }}">
                            <img src="{{ asset('storage/' . $img) }}" alt="صورة للمشروع رقم {{ $i + 1 }}"
                                loading="lazy" decoding="async"
                                class="w-full aspect-[4/3] object-contain rounded-xl shadow-md border border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-[#23232a] transition-transform duration-300 hover:scale-105" />
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-template.layouts.index-layouts>
