@php
    use Illuminate\Support\Str;
    use Carbon\Carbon;

    $basePrice = (float) ($template->price ?? 0);
    $discRaw = $template->discount_price; // قد تكون null أو 0 أو "0.00"
    $discPrice = is_null($discRaw) ? null : (float) $discRaw;

    // يوجد خصم فقط إذا كان discount_price رقمًا > 0 وأقل من السعر الأصلي
    $hasDiscount = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;

    // السعر النهائي
    $finalPrice = $hasDiscount ? $discPrice : $basePrice;

    // نسبة الخصم (بدون max(1,...))
    $discountPerc = $hasDiscount && $basePrice > 0 ? (int) round((($basePrice - $discPrice) / $basePrice) * 100) : 0;

    // أظهر العدّاد فقط إذا فيه خصم فعلي وتاريخ انتهاء
    $endsAt = $hasDiscount && !empty($template->discount_ends_at) ? Carbon::parse($template->discount_ends_at) : null;

    $shortDesc = Str::limit(strip_tags($translation?->description ?? ''), 160);

    // حمِّل details سواء Array من الـ casts أو JSON نصّي
    $payload = is_array($translation?->details)
        ? $translation->details
        : (json_decode($translation->details ?? '[]', true) ?:
        []);

    $features = collect($payload['features'] ?? [])
        ->filter(fn($f) => is_array($f) && !empty($f['title']))
        ->values();

    $gallery = collect($payload['gallery'] ?? [])
        ->filter(fn($g) => is_array($g) && !empty($g['src']))
        ->values();

    // المواصفات (للصندوق)
    $specs = collect($payload['specs'] ?? [])
        ->filter(fn($s) => is_array($s) && !empty($s['name']) && !empty($s['value']))
        ->values();

    // Details (اسم/قيمة)
    $detailsList = collect($payload['details'] ?? [])
        ->filter(function ($d) {
            if (!is_array($d)) {
                return false;
            }
            $hasNameOrLabel = !empty($d['name']) || !empty($d['label']);
            $val = isset($d['value']) ? trim((string) $d['value']) : '';
            return $hasNameOrLabel && $val !== '';
        })
        ->values();

    // الوسوم
    $tags = collect($payload['tags'] ?? [])
        ->filter(fn($t) => is_string($t) && trim($t) !== '')
        ->map(fn($t) => trim($t))
        ->unique()
        ->values();
@endphp

<x-template.layouts.index-layouts
    title="{{ $translation?->name ?? t('Frontend.Template', 'Template') }} - {{ t('Frontend.Palgoals', 'Palgoals') }}"
    description="{{ $shortDesc }}" keywords="خدمات حجز دومين , افضل شركة برمجيات , استضافة مواقع , ..."
    ogImage="{{ asset('storage/' . $template->image) }}">

    <!-- Hero Section for Product Detail -->
    <section class="relative bg-primary py-20 px-4 sm:px-8 lg:px-24 shadow-md text-white overflow-hidden" dir="auto">
        <div class="relative z-10 max-w-4xl mx-auto text-center">
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-snug drop-shadow-lg mb-4">
                {{ $translation?->name }}
            </h1>
            <p class="text-lg sm:text-xl font-light text-white/90 max-w-3xl mx-auto">
                {{ $shortDesc }}
            </p>
        </div>
        <!-- داخل الـ Hero Section -->
        <div class="relative z-10 mt-6">
            <nav class="text-sm text-white/80" aria-label="Breadcrumb">
                <ol class="flex flex-wrap justify-center items-center gap-2 ">
                    <li>
                        <a href="{{ url('/') }}"
                            class="hover:underline text-white font-semibold">{{ t('Frontend.Home', 'Home') }}</a>
                        <span class="mx-2">/</span>
                    </li>
                    @php
                    $templatesSlug = page_slug('templates');
                    @endphp
                    <li>
                        <a href="{{ route('frontend.page.show', $templatesSlug) }}"
                            class="hover:underline text-white font-semibold">{{ t('Frontend.Template', 'Template') }}</a>
                        <span class="mx-2">/</span>
                    </li>
                    <li class="text-white/70" aria-current="page">{{ $translation?->name }}</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Section Main Product Display -->
    <section class="py-16 sm:py-20 lg:py-24 px-4 sm:px-6 lg:px-8 bg-slate-50 dark:bg-slate-900">
        <div class="max-w-screen-xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
            <!-- Left  -->
            <div class="lg:col-span-8 flex flex-col gap-8">
                <!-- Preview Box -->
                <div
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-black/20 overflow-hidden">
                    <div class="relative w-full aspect-[5/3] group">
                        <img loading="lazy" src="{{ asset('storage/' . $template->image) }}"
                            alt="{{ $translation?->name }}"
                            class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 ease-in-out group-hover:scale-105">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent"></div>
                        @if ($hasDiscount)
                            <span
                                class="absolute top-4 right-4 bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
                                -{{ $discountPerc }}%
                            </span>
                        @endif
                    </div>
                    <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @php
                            $previewSource = trim((string) ($translation?->preview_url ?? ''));
                            $hasValidPreviewSource = $previewSource !== ''
                                && (filter_var($previewSource, FILTER_VALIDATE_URL) || str_starts_with($previewSource, '//'));
                        @endphp
                        @if ($hasValidPreviewSource)
                            <a href="{{ route('template.preview', $translation->slug) }}" target="_blank"
                                rel="noopener"
                                class="flex items-center justify-center gap-2.5 text-center bg-primary hover:bg-primary/90 text-white py-3 rounded-lg font-bold shadow-lg shadow-primary/30 hover:shadow-primary/40 transition-all duration-300 transform hover:-translate-y-0.5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd"
                                        d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.022 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>{{ t('Frontend.View_Template', 'View Template') }}</span>
                            </a>
                        @endif
                        <a href="#"
                            class="flex items-center justify-center gap-2.5 text-center bg-green-500 hover:bg-green-600 text-white py-3 rounded-lg font-bold shadow-lg shadow-green-500/30 hover:shadow-green-500/40 transition-all duration-300 transform hover:-translate-y-0.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path
                                    d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                            </svg>
                            <span>{{ t('Frontend.Contact_us', 'Contact us') }}</span>
                        </a>
                    </div>
                </div>
                <!-- Features -->
                <div
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-black/20 p-6 sm:p-8">
                    <h3 class="flex items-center gap-3 text-xl font-bold text-gray-800 dark:text-white mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                        {{ t('Frontend.Key_features', 'Key features') }}
                    </h3>
                    @if (count($features))
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            @foreach ($features as $feature)
                                <div
                                    class="bg-slate-50 dark:bg-gray-700/50 p-4 rounded-xl text-center border border-gray-200 dark:border-gray-700 transition-all duration-300 hover:shadow-lg hover:border-primary/50 hover:-translate-y-1">
                                    <div class="text-4xl mb-2">{{ $feature['icon'] ?? '✨' }}</div>
                                    <h4 class="font-bold text-sm text-gray-800 dark:text-white">
                                        {{ $feature['title'] ?? t('Frontend.Professional_design', 'Professional design') }}
                                    </h4>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <!-- Description -->
                <div
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-black/20 p-6 sm:p-8">
                    <h3 class="flex items-center gap-3 text-xl font-bold text-gray-800 dark:text-white mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ t('Frontend.Template_description', 'Template description') }}
                    </h3>
                    <div class="prose prose-slate dark:prose-invert max-w-none">
                        {!! $translation?->description !!}
                    </div>
                </div>
                <!-- صندوق صور من القالب -->
                <div
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-black/20 p-6 sm:p-8">
                    <h3 class="flex items-center gap-3 text-xl font-bold text-gray-800 dark:text-white mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l-1.586-1.586a2 2 0 00-2.828 0L6 14m6-6l.01.01" />
                        </svg>
                        {{ t('Frontend.Template_images', 'Template images') }}
                    </h3>
                    @if ($gallery->isNotEmpty())
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($gallery as $item)
                                @php
                                    $src = $item['src'];
                                    $url = Str::startsWith($src, ['http://', 'https://', '//']) ? $src : asset($src);
                                    $alt = $item['alt'] ?? $translation?->name . ' screenshot';
                                @endphp
                                <a href="{{ $url }}"
                                    class="group block rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-shadow duration-300">
                                    <img loading="lazy" src="{{ $url }}" alt="{{ $alt }}"
                                        class="w-full h-auto object-cover aspect-video transition-transform duration-500 ease-in-out group-hover:scale-105">
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ t('Frontend.No_photos_added_yet', 'No photos added yet.') }}</p>
                    @endif
                </div>
                <!-- صندوق الأسئلة الشائعة -->
                <div x-data="{ active: 1 }"
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-black/20 p-6 sm:p-8">
                    <h3 class="flex items-center gap-3 text-xl font-bold text-gray-800 dark:text-white mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        أسئلة شائعة
                    </h3>
                    <div class="space-y-4">
                        <!-- السؤال الأول -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <button @click="active = (active === 1 ? null : 1 )"
                                class="w-full flex justify-between items-center text-start">
                                <span class="font-semibold text-gray-800 dark:text-white">هل أحتاج إلى خبرة برمجية
                                    لاستخدام القالب؟</span>
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform"
                                    :class="{ 'rotate-180': active === 1 }" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="active === 1" x-collapse class="pt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-300">إطلاقاً! القالب مصمم ليكون سهل
                                    الاستخدام مع لوحة تحكم ووردبريس. يمكنك تخصيص كل شيء بالسحب والإفلات دون كتابة أي
                                    كود.</p>
                            </div>
                        </div>
                        <!-- السؤال الثاني -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <button @click="active = (active === 2 ? null : 2)"
                                class="w-full flex justify-between items-center text-start">
                                <span class="font-semibold text-gray-800 dark:text-white">هل السعر يشمل الدومين
                                    والاستضافة؟</span>
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform"
                                    :class="{ 'rotate-180': active === 2 }" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="active === 2" x-collapse class="pt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-300">نعم، السعر المعروض هو باقة متكاملة
                                    تشمل القالب، دومين مجاني للسنة الأولى، واستضافة بحجم 5 جيجا، بالإضافة إلى الدعم
                                    الفني.</p>
                            </div>
                        </div>
                        <!-- السؤال الثالث -->
                        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                            <button @click="active = (active === 3 ? null : 3)"
                                class="w-full flex justify-between items-center text-start">
                                <span class="font-semibold text-gray-800 dark:text-white">ماذا لو لم يعجبني القالب بعد
                                    الشراء؟</span>
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 transition-transform"
                                    :class="{ 'rotate-180': active === 3 }" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="active === 3" x-collapse class="pt-2">
                                <p class="text-sm text-gray-600 dark:text-gray-300">لا تقلق، نحن نقدم ضمان استعادة
                                    الأموال لمدة 30 يومًا. إذا لم تكن راضيًا تمامًا عن الخدمة، يمكنك استرداد أموالك
                                    بالكامل.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- صندوق آراء العملاء ونموذج إضافة مراجعة -->
                <div x-data="{ addReview: false }"
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-black/20 p-6 sm:p-8">
                    <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
                        @php
                            // احصاء مبسّط لعرض العدد
                            $reviewsCount = $template->reviews()->approved()->count();
                        @endphp
                        <h3 class="flex items-center gap-3 text-xl font-bold text-gray-800 dark:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            {{ t('Frontend.What_did_our_customers_say?', 'What did our customers say?') }}
                            <span
                                class="text-xs font-normal text-gray-500 dark:text-gray-400 ms-2">({{ $reviewsCount }})</span>
                        </h3>
                        <button @click="addReview = !addReview"
                            class="flex items-center gap-2 text-sm font-bold bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary-300 px-4 py-2 rounded-lg hover:bg-primary/20 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span x-show="!addReview">{{ t('Frontend.Add_your_review', 'Add your review') }}</span>
                            <span x-show="addReview">{{ t('Frontend.Close', 'Close') }}</span>
                        </button>
                    </div>
                    <!-- نموذج إضافة مراجعة (يظهر عند الضغط ) -->
                    <div x-show="addReview" x-collapse class="mb-8" x-data="{ rating: 0, hoverRating: 0 }">
                        <form x-ref="form" method="POST"
                            action="{{ route('frontend.templates.reviews.store', ['template_id' => $template->id]) }}"
                            @submit.prevent="if(rating===0){ alert('يرجى اختيار تقييم من 1 إلى 5'); return; } $refs.form.submit();">
                            @csrf
                            @if (session('success'))
                                <div class="text-green-600 text-sm mb-3">{{ session('success') }}</div>
                            @endif
                            @if ($errors->any())
                                <div class="text-red-600 text-sm mb-3">
                                    <ul class="list-disc ps-5">
                                        @foreach ($errors->all() as $e)
                                            <li>{{ $e }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-bold text-lg text-gray-800 dark:text-white mb-4">
                                    {{ t('Frontend.Share_your_opinion_with_us', 'Share your opinion with us') }}</h4>
                                @guest('client')
                                    @guest
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                            <input type="text" name="author_name"
                                                placeholder="{{ t('Frontend.your_name', 'your name') }}"
                                                class="w-full bg-slate-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 rounded-md focus:ring-primary focus:border-primary transition"
                                                required>
                                            <input type="email" name="author_email"
                                                placeholder="{{ t('Frontend.Your_email_(will_not_be_published)', 'Your email (will not be published)') }}"
                                                class="w-full bg-slate-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 rounded-md focus:ring-primary focus:border-primary transition"
                                                required>
                                        </div>
                                    @endguest
                                @endguest
                                <textarea name="comment" placeholder="{{ t('Frontend.Write_your_review_here...', 'Write your review here...') }}"
                                    rows="4"
                                    class="w-full bg-slate-50 dark:bg-gray-700/50 border-gray-200 dark:border-gray-600 rounded-md focus:ring-primary focus:border-primary transition mb-4"></textarea>
                                <input type="hidden" name="rating" :value="rating">
                                <div class="flex flex-wrap items-center justify-between gap-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="font-semibold text-sm text-gray-700 dark:text-gray-300">{{ t('Frontend.Your_rating:', 'Your rating:') }}</span>
                                        <div class="flex" @mouseleave="hoverRating = 0">
                                            <template x-for="star in 5" :key="star">
                                                <svg @click="rating = star" @mouseover="hoverRating = star"
                                                    class="w-6 h-6 cursor-pointer"
                                                    :class="(hoverRating >= star || rating >= star) ? 'text-yellow-400' :
                                                    'text-gray-300 dark:text-gray-600'"
                                                    fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            </template>
                                        </div>
                                    </div>
                                    <button
                                        class="bg-primary hover:bg-primary/90 text-white font-bold py-2 px-6 rounded-lg transition-all duration-300 transform hover:-translate-y-0.5">{{ t('Frontend.Submit_review:', 'Submit review') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <!-- قائمة المراجعات الحالية -->
                    <div class="space-y-6">
                        @foreach ($template->reviews as $review)
                            @php
                                // الاسم المعرُوض
                                $displayName =
                                    $review->client?->first_name || $review->client?->last_name
                                        ? trim(
                                            ($review->client?->first_name ?? '') .
                                                ' ' .
                                                ($review->client?->last_name ?? ''),
                                        )
                                        : $review->user?->name ?? ($review->author_name ?? 'مستخدم');
                                // الصورة
                                $avatarName =
                                    $review->author_name ??
                                    ($review->client?->first_name ?? ($review->user?->name ?? 'User'));
                                $avatarUrl = $review->client?->avatar
                                    ? asset('storage/' . $review->client->avatar)
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($avatarName);
                                // تنظيف التعليق من علامات اقتباس زائدة
                                $cleanComment = \Illuminate\Support\Str::of($review->comment ?? '')
                                    ->trim()
                                    ->trim('"')
                                    ->trim("'")
                                    ->toString();
                            @endphp
                            <!-- المراجعة الأولى -->
                            <div class="flex gap-4">
                                <img loading="lazy" src="{{ $avatarUrl }}" alt="صورة العميل"
                                    class="w-12 h-12 rounded-full object-cover border-2 border-primary/50">
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <h4 class="font-bold text-gray-800 dark:text-white">{{ $displayName }}</h4>
                                        @if ($review->created_at)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($review->created_at)->diffForHumans() }}
                                            </span>
                                        @endif
                                        <div class="flex text-yellow-400"
                                            aria-label="تقييم {{ (int) $review->rating }} من 5">
                                            <!-- نجوم التقييم -->
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg class="w-4 h-4 {{ $review->rating >= $i ? '' : 'text-gray-300 dark:text-gray-600' }}"
                                                    fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            @endfor
                                        </div>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-300 text-sm">{{ e($cleanComment) }}</p>
                                </div>
                            </div>
                        @endforeach
                        @if ($reviewsCount === 0)
                            <p class="text-sm text-gray-500 dark:text-gray-400">لا توجد مراجعات بعد — كن أول من يقيّم
                                القالب.</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right (Pricing) -->
            <div class="lg:col-span-4 flex flex-col gap-8">
                <!-- صندوق المواصفات والأسعار -->
                <div id="price-section" x-data="{
                    basePrice: {{ number_format($basePrice, 2, '.', '') }},
                    discPrice: {{ $hasDiscount ? number_format($discPrice, 2, '.', '') : 'null' }},
                    hasDiscount: {{ $hasDiscount ? 'true' : 'false' }},
                    endsAt: {{ $endsAt ? '\'' . $endsAt->toIso8601String() . '\'' : 'null' }},
                    leftMs: 0,
                    timer: null,
                    open: false, // 👈新增
                    init() {
                        if (this.endsAt) {
                            this.tick();
                            this.timer = setInterval(() => this.tick(), 1000);
                        }
                    },
                    tick() {
                        this.leftMs = new Date(this.endsAt) - new Date();
                        if (this.leftMs <= 0) {
                            this.leftMs = 0;
                            this.hasDiscount = false;
                            this.discPrice = null;
                            if (this.timer) clearInterval(this.timer);
                        }
                    },
                    finalPrice() { return this.hasDiscount && this.discPrice ? this.discPrice : this.basePrice; },
                    money(v) { return '$' + Number(v).toFixed(2); }
                }"
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-black/20 p-6 sm:p-8 flex flex-col gap-5">

                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $translation?->name }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5">
                            أطلق متجرك الإلكتروني الاحترافي في دقائق
                        </p>
                    </div>
                    @php
                        $avg = round($template->avgRating(), 1);
                    @endphp
                    <div class="flex items-center justify-center gap-3 text-sm text-gray-600 dark:text-gray-300 border-y border-gray-200 dark:border-gray-700 py-3"
                        aria-label="تقييم المنتج">
                        <div class="flex text-yellow-400" role="img"
                            aria-label="تقييم {{ number_format($avg, 1) }} من 5 نجوم">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 {{ $avg >= $i ? '' : 'text-gray-300 dark:text-gray-600' }}"
                                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            @endfor
                        </div>
                        <span class="font-semibold" aria-hidden="true">{{ number_format($avg, 1) }}</span>
                        <span class="sr-only">({{ number_format($avg, 1) }} من 5)</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <p class="text-base font-medium text-gray-600 dark:text-gray-300">
                            {{ t('Frontend.Price', 'Price') }}:</p>
                        <div class="flex items-baseline gap-2">
                            <span class="text-4xl font-bold text-primary dark:text-primary-400"
                                x-text="money(finalPrice())">
                                ${{ number_format($finalPrice, 2) }}
                            </span>
                            <span class="text-xl text-gray-400 dark:text-gray-500 line-through" x-show="hasDiscount"
                                style="display:none;">
                                ${{ number_format($basePrice, 2) }}
                            </span>
                        </div>
                    </div>
                    @if ($endsAt)
                        <div class="bg-red-100/50 dark:bg-red-900/20 text-sm p-3 rounded-lg text-red-700 dark:text-red-300 font-semibold text-center"
                            x-show="hasDiscount && leftMs > 0" style="display:none;" aria-live="polite">
                            🔥 {{ t('Frontend.Offer_ends_in', 'Offer ends in') }}:
                            <span class="font-mono tracking-wider"
                                x-text="(() => { const s = Math.floor(leftMs/1000); const h=String(Math.floor(s/3600)).padStart(2,'0'); const m=String(Math.floor((s%3600)/60)).padStart(2,'0'); const ss=String(s%60).padStart(2,'0'); return `${h}:${m}:${ss}`; })()">
                                00:00:00
                            </span>
                        </div>
                    @endif
                    {{-- دلالات أوضح للمواصفات --}}
                    @php $short = $detailsList->take(3); @endphp
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm pt-2">
                        @forelse($short as $row)
                            @php
                                $name = trim($row['name'] ?? ($row['label'] ?? ''));
                                $value = trim($row['value'] ?? '');
                            @endphp
                            <dt class="font-semibold text-gray-800 dark:text-gray-200">
                                {{ $name !== '' ? $name : '—' }}</dt>
                            <dd class="text-gray-600 dark:text-gray-400 text-end">{{ $value !== '' ? $value : '—' }}
                            </dd>
                        @empty
                            <dt class="font-semibold text-gray-800 dark:text-gray-200">
                                {{ t('Frontend.No_specifications', 'No specifications') }}</dt>
                            <dd class="text-gray-600 dark:text-gray-400 text-end">—</dd>
                        @endforelse
                        @if ($detailsList->count() > 3)
                            <template x-if="open">
                                <div class="contents" id="more-specs">
                                    @foreach ($detailsList->slice(3) as $row)
                                        @php
                                            $name = trim($row['name'] ?? ($row['label'] ?? ''));
                                            $value = trim($row['value'] ?? '');
                                        @endphp
                                        <dt class="font-semibold text-gray-800 dark:text-gray-200">
                                            {{ $name !== '' ? $name : '—' }}</dt>
                                        <dd class="text-gray-600 dark:text-gray-400 text-end">
                                            {{ $value !== '' ? $value : '—' }}</dd>
                                    @endforeach
                                </div>
                            </template>
                        @endif
                    </dl>
                    @if ($detailsList->count() > 3)
                        <button @click="open = !open"
                            class="inline-flex items-center gap-1 text-sm font-semibold text-primary dark:text-primary-400 hover:text-primary/80 transition-colors"
                            :aria-expanded="open.toString()" aria-controls="more-specs" type="button">
                            <svg x-show="!open" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"
                                aria-hidden="true">
                                <path
                                    d="M10 3a1 1 0 01.894.553l6 12A1 1 0 0116 17H4a1 1 0 01-.894-1.447l6-12A1 1 0 0110 3z" />
                            </svg>
                            <svg x-show="open" class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"
                                aria-hidden="true">
                                <path
                                    d="M10 17a1 1 0 01-.894-.553l-6-12A1 1 0 014 3h12a1 1 0 01.894 1.447l-6 12A1 1 0 0110 17z" />
                            </svg>
                            <span x-show="!open">{{ t('Frontend.Show_all_specs', 'Show all specs') }}</span>
                            <span x-show="open">{{ t('Frontend.Hide_specs', 'Hide specs') }}</span>
                        </button>
                    @endif
                    @php $finalPriceCents = (int) round($finalPrice * 100); @endphp
                    <a id="subscribeNow"
                        href="{{ route('checkout.cart', ['template_id' => $template->id, 'review' => 1, 'domain' => request('domain')]) }}"
                        data-template-id="{{ $template->id }}"
                        data-template-name="{{ $translation?->name ?? ($template->name ?? 'Template') }}"
                        data-price-cents="{{ $finalPriceCents }}"
                        class="w-full text-center bg-primary hover:bg-primary/90 text-white py-3.5 rounded-xl font-bold text-base shadow-xl shadow-primary/30 hover:shadow-primary/40 transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                        aria-label="اشترك الآن في {{ $translation?->name }} بسعر {{ number_format($finalPrice, 2) }} دولار">
                        🛒 {{ t('Frontend.Subscribe_now', 'Subscribe now') }}
                    </a>
                    <div class="flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ t('Frontend.Money_back_30_days', '30-day money-back guarantee') }}</span>
                    </div>
                </div>
                <!-- صندوق تفاصيل القالب -->
                <div
                    class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/50 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-black/20 p-6 sm:p-8">
                    <h3 class="flex items-center gap-3 text-xl font-bold text-gray-800 dark:text-white mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ t('Frontend.Template_details', 'Template details') }}
                    </h3>
                    @if ($specs->isNotEmpty())
                        <div class="space-y-4 text-sm">
                            @foreach ($specs as $row)
                                <div class="flex justify-between">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $row['name'] }}
                                    </span>
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">
                                        {{ $row['value'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="space-y-4 text-sm">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                لا يوجد تفاصيل للقالب
                            </p>
                        </div>
                    @endif
                    <div class="pt-2">
                        <span
                            class="font-semibold text-gray-700 dark:text-gray-300 mb-2 block">{{ t('Frontend.Tags', 'Tags') }}</span>
                        @if ($tags->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach ($tags as $tag)
                                    <span
                                        class="bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary-300 text-xs font-bold px-3 py-1 rounded-full">{{ $tag }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('Frontend.No_tags', 'No tags') }}
                            </p>
                        @endif
                    </div>
                </div>
                <!-- صندوق دعوة لاتخاذ إجراء نهائي (CTA V4) -->
                <div style="--color-primary: #240B36; --color-secondary: #AE1028;"
                    class="relative bg-[var(--color-primary)] rounded-2xl shadow-2xl shadow-slate-300/40 dark:shadow-black/40 p-8 text-center overflow-hidden">
                    <!-- عناصر زخرفية في الخلفية -->
                    <div
                        class="absolute top-0 left-0 w-20 h-20 bg-white/5 rounded-full blur-xl -translate-x-1/2 -translate-y-1/2">
                    </div>
                    <div
                        class="absolute bottom-0 right-0 w-28 h-28 bg-[var(--color-secondary)]/10 rounded-full blur-2xl translate-x-1/4 translate-y-1/4">
                    </div>
                    <div class="relative z-10">
                        <!-- أيقونة مميزة -->
                        <div class="mx-auto w-fit bg-white/10 p-3 rounded-xl mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <!-- المحتوى النصي -->
                        <h3 class="text-3xl font-extrabold text-white tracking-tight mb-2">
                            حوّل فكرتك إلى واقع
                        </h3>
                        <p class="text-slate-300/70 max-w-md mx-auto mb-6">
                            نقدم لك حلولاً متكاملة، من القوالب الجاهزة إلى التصاميم الحصرية المصممة خصيصًا لك.
                        </p>
                        <!-- الأزرار -->
                        <div class="flex flex-col gap-3 max-w-xs mx-auto">
                            <a href="#"
                                class="group flex items-center justify-center gap-2.5 text-center bg-[var(--color-secondary )] hover:bg-opacity-90 text-white py-3 px-5 rounded-lg font-bold shadow-lg shadow-[var(--color-secondary)]/20 transition-all duration-300 transform hover:scale-105">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                </svg>
                                <span>طلب تصميم خاص</span>
                            </a>
                            <a href="#price-section"
                                class="w-full text-center bg-white/10 hover:bg-white/20 text-white py-3 px-5 rounded-lg font-semibold transition-colors duration-300">
                                شراء القالب الجاهز
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // إن وُجد domain في الاستعلام، وجّه الاشتراك إلى checkout/cart مع template_id لعرضه ضمن السلة الموحدة
        (function() {
            const qp = new URLSearchParams(window.location.search);
            const d = (qp.get('domain') || '').trim();
            const a = document.getElementById('subscribeNow');
            const tplId = a?.getAttribute('data-template-id');
            if (a && tplId) {
                const base = "{{ route('checkout.cart') }}";
                const url = new URL(base, window.location.origin);
                url.searchParams.set('template_id', tplId);
                url.searchParams.set('review', '1');
                if (d) url.searchParams.set('domain', d);
                a.href = url.pathname + url.search;
            }
        })();
    </script>
</x-template.layouts.index-layouts>
<script src="//unpkg.com/alpinejs" defer></script>
<script>
    (function() {
        const CART_KEY = 'palgoals_cart';

        function readCart() {
            const legacy = localStorage.getItem('palgoals_cart_domains');
            const unified = localStorage.getItem(CART_KEY);
            let items = [];
            try {
                items = unified ? JSON.parse(unified) : [];
            } catch {
                items = [];
            }
            if (legacy && !unified) {
                try {
                    const old = JSON.parse(legacy);
                    if (Array.isArray(old)) {
                        items = items.concat(old.map(it => ({
                            kind: 'domain',
                            domain: String(it.domain || '').toLowerCase(),
                            item_option: it.item_option ?? it.option ?? null,
                            price_cents: Number(it.price_cents) || 0,
                            meta: it.meta ?? null,
                        })));
                        // (اختياري) بعد الدمج الأول احذف المفتاح القديم لتفادي أي لبس لاحقًا:
                        // localStorage.removeItem('palgoals_cart_domains');
                    }
                } catch {}
            }
            return Array.isArray(items) ? items : [];
        }

        function writeCart(items) {
            localStorage.setItem(CART_KEY, JSON.stringify(items || []));
        }

        function addOrIncrementTemplate(items, tplItem) {
            const id = Number(tplItem.template_id) || 0;
            let found = false;
            const out = items.map(it => {
                if (it?.kind === 'template' && Number(it.template_id) === id) {
                    found = true;
                    return {
                        ...it,
                        qty: Math.max(1, Number(it.qty || 1) + Number(tplItem.qty || 1))
                    };
                }
                return it;
            });
            if (!found) out.push({
                ...tplItem,
                qty: Math.max(1, Number(tplItem.qty || 1))
            });
            return out;
        }

        function addTemplateToCartFrom(btn) {
            try {
                const tpl = {
                    kind: 'template',
                    template_id: Number(btn.dataset.templateId) || null,
                    template_name: btn.dataset.templateName || 'Template',
                    qty: 1,
                    price_cents: Number(btn.dataset.priceCents) || 0,
                    meta: null,
                };
                if (!tpl.template_id) return;
                const items = readCart();
                const updated = addOrIncrementTemplate(items, tpl);
                writeCart(updated);
            } catch (err) {
                console.error('Add to cart failed:', err);
            }
        }

        const btn = document.getElementById('subscribeNow');
        if (!btn) return;

        // ✅ حارس لمنع الإضافة المزدوجة أثناء الانتقال
        let addedOnce = false;
        const handleAdd = () => {
            if (addedOnce) return;
            addedOnce = true;
            addTemplateToCartFrom(btn);
            // إعادة الضبط بعد قليل (في حال فتح في تبويب جديد مثلاً)
            setTimeout(() => {
                addedOnce = false;
            }, 800);
        };

        // Click (يسار)
        btn.addEventListener('click', handleAdd);

        // Middle click (زر أوسط)
        btn.addEventListener('auxclick', (e) => {
            if (e.button === 1) handleAdd();
        });

        // Keyboard: عالج فقط Space — واترك Enter يطلق click تلقائيًا
        btn.addEventListener('keydown', (e) => {
            if (e.key === ' ') {
                e.preventDefault(); // منع سكرول الصفحة
                handleAdd();
            }
        });
    })();
</script>
