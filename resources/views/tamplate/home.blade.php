<x-tamplate.layouts.index-layouts
    title="بال قول لتكنولوجيا المعلومات - مواقع الكترونية واستضافة عربية"
    description="شركة فلسطينية متخصصة في برمجة وتصميم المواقع الالكترونية تقدم خدمات استضافة مواقع، حجز دومين،مواقع ووردبريس،اعلانات جوجل،تحسين محركات البحث"
    keywords="خدمات حجز دومين , افضل شركة برمجيات , استضافة مواقع , استضافة مشتركة , شركة استضافة مواقع , شركات استضافة مواقع , افضل شركة برمجة, خدمة كتابة محتوى , تحسين محركات البحث , web hosting service , shared hosting , best wordpress hosting , web hosting company, domain registration services , best IT company , information technology company , content writing service , best SEO services"
    ogImage="{{ asset('assets/images/services.jpg') }}"
>

<!-- Hero Section -->
<main>
  <section class="relative bg-gradient-to-tr from-primary to-primary shadow-2xl overflow-hidden -mt-20">
  <!-- Background Image as <img> -->
  <img
    src="{{ asset('assets/tamplate/images/template.webp') }}"
    alt=""
    fetchpriority="high"
    class="absolute inset-0 z-0 opacity-80 w-full h-full object-cover object-center ltr:scale-x-[-1] rtl:scale-x-100 transition-transform duration-500 ease-in-out"
    aria-hidden="true"
    decoding="async"
    loading="eager"
    />
    
    <!-- Content Container -->
    <div class="relative z-10 px-4 sm:px-8 lg:px-24 py-20 sm:py-28 lg:py-32 flex flex-col-reverse md:flex-row items-center justify-between gap-12 min-h-[600px] lg:min-h-[700px]">
      <!-- Text Content -->
      <div class="max-w-xl rtl:text-right ltr:text-left text-center md:text-start">
        <h1 class="text-3xl/20 sm:text-4xl/20  lg:text-5xl/20 font-extrabold text-white leading-tight drop-shadow-lg mb-6">
          أطلق موقعك الخاص خلال 5 دقائق وبأقل تكلفة
        </h1>
        <p class="text-white/90 text-base sm:text-lg font-light mb-8">
          أنشئ موقع احترافي بسرعة على أقوى منصة عربية لتصميم المواقع، مع لوحة تحكم كاملة، ونطاق واستضافة مجانية.
        </p>
        <!-- Buttons -->
        <div class="flex flex-row flex-wrap gap-3 justify-center md:justify-start">
          <a href="#templates"
            aria-label="ابدأ الآن بإنشاء موقعك مجانًا"
            class="bg-secondary hover:bg-primary text-white font-bold px-6 py-3 rounded-lg shadow transition text-sm sm:text-base">
            ابدأ الآن مجانًا
          </a>
          <a href="#templates"
            aria-label="استعرض قوالب منصتنا الاحترافية"
            class="bg-white/10 text-white font-bold px-6 py-3 rounded-lg shadow transition hover:bg-white/20 text-sm sm:text-base border border-white/30">
            استعرض القوالب
          </a>
        </div>
      </div>
    </div>

    <!-- Decorative Circle -->
    <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl z-0"></div>
  </section>
</main>
<!-- Features Section -->
<section class="py-28 px-4 sm:px-8 lg:px-24 bg-background" dir="auto" aria-labelledby="features-heading">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-16">
      <h2 class="text-3xl sm:text-4xl font-extrabold text-primary mb-4 tracking-tight" data-aos="fade-up" data-aos-delay="100">
        {{ t('frontend.features_title') }}
      </h2>
      <p class="text-tertiary text-suptitle/9 sm:text-suptitle/9 max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="100">
        {{ t('frontend.features_suptitle') }}
      </p>
    </div>

    <div class="grid gap-16 lg:grid-cols-5 items-center" data-aos="zoom-in" data-aos-delay="100">
      <!-- Illustration -->
      <div class="lg:col-span-2 flex justify-center">
        <img src="{{ asset('assets/tamplate/images/Fu.svg') }}" alt="مميزات المنصة"
             class="max-w-[300px] sm:max-w-sm lg:max-w-[500px] w-full h-auto object-contain mx-auto animate-fade-in-up hover:scale-105 transition-transform duration-500"
             loading="lazy">
      </div>

      <!-- Features Grid -->
      <dl class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-y-12 gap-x-6 text-center sm:text-start">
        
        <!-- Feature 1 -->
        <div>
          <dt class="flex flex-col items-center sm:items-start gap-4">
            <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-primary/20 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-pretty">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.631 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a4.493 4.493 0 0 0-1.757 4.306 4.493 4.493 0 0 0 4.306-1.758M16.5 9a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
             </svg>
            </div>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">{{ t('frontend.feature_1_title') }}</span>
          </dt>
          <dd class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
            اطلق موقعك خلال دقائق، دون أي تعقيد تقني.
          </dd>
        </div>

        <!-- Feature 2 -->
        <div>
          <dt class="flex flex-col items-center sm:items-start gap-4">
            <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-primary/20 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-primary">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
              </svg>
            </div>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">دعم فني متواصل</span>
          </dt>
          <dd class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
           نرافقك خطوة بخطوة، بفريق جاهز دائمًا لمساعدتك.
          </dd>
        </div>

        <!-- Feature 3 -->
        <div>
          <dt class="flex flex-col items-center sm:items-start gap-4">
            <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-primary/20 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-primary">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" />
              </svg>
            </div>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">لوحة تحكم سهلة</span>
          </dt>
          <dd class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
            تحكم في كل تفاصيل موقعك بسهولة من خلال واجهة مريحة وسريعة.
          </dd>
        </div>

        <!-- Feature 4 -->
        <div>
          <dt class="flex flex-col items-center sm:items-start gap-4">
            <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-primary/20 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-primary">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
              </svg>
          </div>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">أمان وموثوقية</span>
          </dt>
          <dd class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
            نحمي بياناتك بأعلى معايير الأمان ونسخ احتياطي مستمر.
          </dd>
        </div>

        <!-- Feature 5 -->
        <div>
          <dt class="flex flex-col items-center sm:items-start gap-4">
            <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-primary/20 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-primary">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
              </svg>
          </div>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">تصميم متجاوب</span>
          </dt>
          <dd class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
            موقعك يظهر بأفضل شكل على الجوال، التابلت، والكمبيوتر.
          </dd>
        </div>

        <!-- Feature 6 -->
        <div>
          <dt class="flex flex-col items-center sm:items-start gap-4">
            <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-primary/20 shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-primary">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
              </svg>
            </div>
            <span class="text-lg font-semibold text-gray-900 dark:text-white">تحسين محركات البحث</span>
          </dt>
          <dd class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
           هيكلية مدروسة تساعد موقعك في الظهور على Google وجذب الزوار.
          </dd>
        </div>

      </dl>
    </div>
  </div>
</section>
<!-- خدمات رقمية متكاملة -->
@php
    $services = \App\Models\Service::with('translations')->orderBy('order')->get();
@endphp
<section class="py-20 px-4 sm:px-8 lg:px-24 bg-white" dir="auto" aria-label="خدمات رقمية متكاملة">
  <div class="relative">
    <div class="relative z-10 max-w-7xl mx-auto">
      <div class="text-center mb-16">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-primary mb-4 tracking-tight" data-aos="fade-up" data-aos-delay="100">
          خدمات رقمية متكاملة تنطلق بك نحو النجاح
        </h2>
        <p class="text-tertiary text-base sm:text-lg max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
          كل ما تحتاجه لبناء مشروعك الرقمي بنجاح: تصميم احترافي، استضافة سريعة، تسويق فعّال، ودعم فني مستمر – حلول متكاملة من فريق واحد.
        </p>
      </div>

      <!-- Slider -->
      <div class="swiper mySwiper" aria-label="قائمة الخدمات الرقمية" role="region">
        <div class="swiper-wrapper">
          @foreach($services as $service)
            @php
              $translation = $service->translations->firstWhere('locale', app()->getLocale()) 
              ?? $service->translations->first();
            @endphp                    
            <div class="swiper-slide" data-aos="zoom-in" data-aos-delay="100">
              <div class="group bg-white rounded-3xl shadow-xl p-8 flex flex-col items-center text-center border border-primary/10 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 h-full">
                <div class="bg-primary/10 group-hover:bg-primary/20 rounded-full p-4 mb-5 transition">
                  <img src="{{ asset('storage/' . $service->icon) }}" alt="{{ $translation->title }}" class="w-14 h-14" loading="lazy" />
                </div>
                <h3 class="font-bold text-lg text-primary mb-2 group-hover:text-secondary transition">{{ $translation->title }}</h3>
                <p class="text-tertiary text-sm mb-4">{{ $translation->description }}</p>
              </div>
            </div>
          @endforeach
        </div>
        <!-- Pagination -->
        <div class="w-full flex justify-center items-center mt-8">
          <div class="swiper-pagination flex justify-center items-center"></div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- تصاميم عصرية واحترافية -->
<section class="bg-background dark:bg-gray-900 text-primary dark:text-white py-16 px-4 sm:px-8 lg:px-16 rtl:text-right ltr:text-left">
  <header class="text-center mb-10">
    <h2 class="text-title-h2 font-extrabold mb-2 animate-fade-in-up" data-aos="fade-up" data-aos-delay="200">
      قوالب ووردبريس احترافية مع استضافة ودومين
    </h2>
    <p class="text-tertiary text-base dark:text-gray-300 sm:text-lg max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
      احصل على موقع متكامل خلال دقائق باختيار قالب احترافي يشمل الاستضافة والدومين والدعم الفني.
    </p>
  </header>

  <main class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 px-0 sm:px-12" data-aos="zoom-in" data-aos-delay="200">
    <article style="will-change: transform" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden relative group transition-transform duration-300 hover:-translate-y-2 hover:shadow-2xl animate-fade-in-up border border-primary/10" itemscope itemtype="https://schema.org/Product"  lang="ar">
      <meta itemprop="name" content="قالب للأزياء والملابس">
      <meta itemprop="description" content="قالب متجر مصمم بما يتناسب بيع الملابس">
      <meta itemprop="sku" content="fashion-template-001">
      <meta itemprop="category" content="قوالب مواقع">
      <meta itemprop="brand" content="Palgoals">
      <meta itemprop="priceCurrency" content="USD" />
      <meta itemprop="price" content="56" />
      <meta itemprop="availability" content="https://schema.org/InStock" />
      <div class="relative">
        <img itemprop="image" src="{{ asset('assets/tamplate/images/2-1-1.webp') }}" type="image/webp" alt="قالب متجر ملابس نسائية عصري" class="w-full h-40 object-cover transition-transform duration-300 group-hover:scale-105 group-hover:brightness-95" loading="lazy" decoding="async">
        <div class="bg-gradient-to-tr from-secondary to-primary text-white flex items-end justify-center w-24 h-10 absolute -top-2 rtl:-left-10 ltr:-right-10 ltr:rotate-[40deg] rtl:rotate-[320deg] animate-bounce shadow-lg font-bold text-base tracking-wide">
          جديد
        </div>
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition duration-300"></div>
        <div class="absolute top-2 right-2 rtl:right-auto rtl:left-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
          <button class="bg-white/80 dark:bg-white/20 hover:bg-primary text-primary hover:text-white rounded-full p-2 shadow-md transition" title="معاينة القالب" aria-label="معاينة القالب" role="button">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-9 9-9 9s-9-4-9-9a9 9 0 0118 0z"/>
            </svg>
          </button>
          <button class="bg-white/80 dark:bg-white/20 hover:bg-secondary text-secondary hover:text-white rounded-full p-2 shadow-md transition" title="شراء القالب" aria-label="شراء القالب" role="button">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.35 2.7A1 1 0 007 17h10a1 1 0 00.95-.68L19 13M7 13V6a1 1 0 011-1h5a1 1 0 011 1v7"/>
            </svg>
          </button>
        </div>
      </div>
      <div class="p-5 rtl:text-right ltr:text-left flex flex-col items-start">
        <h3 itemprop="name" class="text-suptitle font-bold mb-1 text-primary/90 dark:text-white group-hover:text-secondary transition-colors leading-snug text-start rtl:text-right ltr:text-left">قالب للأزياء والملابس</h3>
        <p itemprop="description" class="text-suptitle font-light mb-2 text-primary/70 dark:text-gray-300 rtl:text-right ltr:text-left leading-relaxed">قالب متجر مصمم بما يتناسب بيع الملابس</p>
        <div class="flex justify-between items-center text-sm font-bold rtl:flex-row-reverse ltr:flex-row mt-3 rtl:text-right ltr:text-left w-full">
          <div class="flex items-center gap-1" aria-label="التقييم 4 من 5 نجوم">
            <span class="text-yellow-400 text-base" role="img" aria-label="4 من 5 نجوم">★★★★☆</span>
          </div>
          <div class="flex items-center gap-2 rtl:flex-row-reverse ltr:flex-row">
            <span class="line-through text-suptitle text-primary/40 dark:text-gray-400">$56</span>
            <span itemprop="price" class="text-title-h3 text-secondary dark:text-yellow-400">$56</span>
          </div>
        </div>
      </div>
    </article>
  </main>

  <footer class="flex justify-center items-center gap-4 mt-10">
    <a href="/templates" class="relative inline-flex items-center justify-center px-10 py-3 overflow-hidden font-extrabold text-lg text-white rounded-lg shadow-lg bg-primary transition-all duration-300 hover:bg-primary/30 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2">
      <span class="absolute inset-0 w-full h-full bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></span>
      <span class="relative z-10 flex items-center gap-2 rtl:flex-row ltr:flex-row">
        <svg class="w-5 h-5 text-white group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        اكتشف القوالب الاحترافية
      </span>
    </a>
  </footer>
</section>
<!-- أحدث أعمالنا -->
<section class="dark:bg-gray-900 text-primary dark:text-white py-16 px-4 sm:px-8 lg:px-24 rtl:text-right ltr:text-left" aria-labelledby="why-us-heading">
  <header class="text-center mb-12">
    <h2 id="why-us-heading" class="text-title-h2 font-extrabold animate-fade-in-up" data-aos="fade-up" data-aos-delay="200">
      أحدث أعمالنا
    </h2>
    <p class="text-tertiary mt-4 text-suptitle max-w-max mx-auto animate-fade-in-up delay-100" data-aos="fade-up" data-aos-delay="200">
      نعتز بثقة أكثر من 1000 عميل أطلقوا مواقعهم الإلكترونية معنا، وها هي نماذج حقيقية من مشاريع نفذناها باحترافية.
    </p>
  </header>

  <main class="relative group" aria-label="نماذج من أعمالنا" role="region" data-aos="zoom-in" data-aos-delay="200">
    <div class="swiper" id="clients-swiper">
      <div class="swiper-wrapper">
        <div class="swiper-slide flex flex-col items-center text-center animate-fade-in-up hover:scale-105 transition-transform duration-300">
          <div class="w-full max-w-xs overflow-hidden rounded-lg">
            <img src="{{ asset('assets/tamplate/images/Group 34130.jpg') }}" role="img" alt="موقع شركة مقاولات" class="rounded-lg hover:brightness-90 transition duration-300" loading="lazy" decoding="async" />
          </div>
          <h3 class="font-extrabold text-suptitle mt-4 animate-fade-in-up delay-150 text-center text-primary dark:text-white">موقع شركة مقاولات</h3>
        </div>
         <div class="swiper-slide flex flex-col items-center text-center animate-fade-in-up hover:scale-105 transition-transform duration-300">
          <div class="w-full max-w-xs overflow-hidden rounded-lg">
            <img src="{{ asset('assets/tamplate/images/Group 34130.jpg') }}" role="img" alt="موقع شركة مقاولات" class="rounded-lg hover:brightness-90 transition duration-300" loading="lazy" decoding="async" />
          </div>
          <h3 class="font-extrabold text-suptitle mt-4 animate-fade-in-up delay-150 text-center text-primary dark:text-white">موقع شركة مقاولات</h3>
        </div>
        <div class="swiper-slide flex flex-col items-center text-center animate-fade-in-up hover:scale-105 transition-transform duration-300">
          <div class="w-full max-w-xs overflow-hidden rounded-lg">
            <img src="{{ asset('assets/tamplate/images/Group 34130.jpg') }}" role="img" alt="موقع شركة مقاولات" class="rounded-lg hover:brightness-90 transition duration-300" loading="lazy" decoding="async" />
          </div>
          <h3 class="font-extrabold text-suptitle mt-4 animate-fade-in-up delay-150 text-center text-primary dark:text-white">موقع شركة مقاولات</h3>
        </div>
        <div class="swiper-slide flex flex-col items-center text-center animate-fade-in-up hover:scale-105 transition-transform duration-300">
          <div class="w-full max-w-xs overflow-hidden rounded-lg">
            <img src="{{ asset('assets/tamplate/images/Group 34130.jpg') }}" role="img" alt="موقع شركة مقاولات" class="rounded-lg hover:brightness-90 transition duration-300" loading="lazy" decoding="async" />
          </div>
          <h3 class="font-extrabold text-suptitle mt-4 animate-fade-in-up delay-150 text-center text-primary dark:text-white">موقع شركة مقاولات</h3>
        </div>
        <div class="swiper-slide flex flex-col items-center text-center animate-fade-in-up hover:scale-105 transition-transform duration-300">
          <div class="w-full max-w-xs overflow-hidden rounded-lg">
            <img src="{{ asset('assets/tamplate/images/Group 34130.jpg') }}" role="img" alt="موقع شركة مقاولات" class="rounded-lg hover:brightness-90 transition duration-300" loading="lazy" decoding="async" />
          </div>
          <h3 class="font-extrabold text-suptitle mt-4 animate-fade-in-up delay-150 text-center text-primary dark:text-white">موقع شركة مقاولات</h3>
        </div>
      </div>
      
      <!-- Pagination -->
        <div class="w-full flex justify-center items-center mt-8">
          <div class="swiper-pagination flex justify-center items-center mt-6"></div>
        </div>
    </div>

    <!-- زر استعراض المزيد -->
    <footer class="flex justify-center items-center gap-4 mt-10">
      <a href="#" class="relative inline-flex items-center justify-center px-10 py-3 overflow-hidden font-extrabold text-lg text-white rounded-lg shadow-lg bg-primary transition-all duration-300 hover:bg-primary/30 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2">
        <span class="absolute inset-0 w-full h-full bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300"></span>
        <span class="relative z-10 flex items-center gap-2 rtl:flex-row ltr:flex-row">
          <svg class="w-5 h-5 text-white group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
          </svg>
          مشاهدة المزيد
        </span>
      </a>
    </footer>
  </main>
</section>
<!-- Testimonials Section -->
<section id="testimonials" class="bg-background py-20 px-4 sm:px-8 lg:px-24">
  <div class="max-w-4xl mx-auto text-center mb-12">
    <h2 class="text-title-h2 font-extrabold text-primary mb-4" data-aos="fade-up" data-aos-delay="200">
      ماذا يقول عملاؤنا عن Palgoals؟
    </h2>
    <p class="text-tertiary text-suptitle mx-auto animate-fade-in-up delay-100" data-aos="fade-up" data-aos-delay="200">
      إليك آراء حقيقية من عملائنا حول خدمات تصميم المواقع واستضافة Palgoals، حيث صنعنا الفرق في أعمالهم الرقمية.
    </p>
  </div>

  <div class="swiper testimonials-swiper">
    <div class="swiper-wrapper" data-aos="zoom-in" data-aos-delay="200">

      <!-- Testimonial Card -->
      <div class="swiper-slide flex justify-center">
        <figure class="testimonial-card" style="will-change: transform, opacity;">
          <svg class="w-8 h-8 text-secondary mb-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M7.17 15c.41-2.36 1.83-4.09 4.33-5.17V7.5A4.5 4.5 0 0 0 7 12v3H3v3h7v-3H7.17zm9 0c.41-2.36 1.83-4.09 4.33-5.17V7.5A4.5 4.5 0 0 0 16 12v3h-4v3h7v-3h-2.83z"/>
          </svg>
          <blockquote class="text-suptitle text-[#240B36] font-light mb-6">
            خدمتهم اختصرت علينا الوقت والجهد. من تصميم الموقع حتى إطلاق المتجر، كل شيء كان سلس وسريع.
          </blockquote>
          <figcaption class="flex items-center gap-3 mt-auto">
            <img src="./assets/images/user1.webp" loading="lazy" alt="نادين خليل - صاحبة متجر ملابس" class="w-12 h-12 rounded-full border-2 border-primary" />
            <div class="text-right">
              <p class="font-bold text-primary text-sm">نادين خليل</p>
              <p class="text-xs text-[#5E4A72]">صاحبة متجر ملابس</p>
            </div>
          </figcaption>
          <div class="flex gap-1 mt-2 text-yellow-400" aria-label="5 من 5 نجوم">★★★★★</div>
        </figure>
      </div>

            <!-- Testimonial Card -->
      <div class="swiper-slide flex justify-center">
        <figure class="testimonial-card" style="will-change: transform, opacity;">
          <svg class="w-8 h-8 text-secondary mb-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M7.17 15c.41-2.36 1.83-4.09 4.33-5.17V7.5A4.5 4.5 0 0 0 7 12v3H3v3h7v-3H7.17zm9 0c.41-2.36 1.83-4.09 4.33-5.17V7.5A4.5 4.5 0 0 0 16 12v3h-4v3h7v-3h-2.83z"/>
          </svg>
          <blockquote class="text-suptitle text-[#240B36] font-light mb-6">
            خدمتهم اختصرت علينا الوقت والجهد. من تصميم الموقع حتى إطلاق المتجر، كل شيء كان سلس وسريع.
          </blockquote>
          <figcaption class="flex items-center gap-3 mt-auto">
            <img src="./assets/images/user1.webp" loading="lazy" alt="نادين خليل - صاحبة متجر ملابس" class="w-12 h-12 rounded-full border-2 border-primary" />
            <div class="text-right">
              <p class="font-bold text-primary text-sm">نادين خليل</p>
              <p class="text-xs text-[#5E4A72]">صاحبة متجر ملابس</p>
            </div>
          </figcaption>
          <div class="flex gap-1 mt-2 text-yellow-400" aria-label="5 من 5 نجوم">★★★★★</div>
        </figure>
      </div>      <!-- Testimonial Card -->
      <div class="swiper-slide flex justify-center">
        <figure class="testimonial-card" style="will-change: transform, opacity;">
          <svg class="w-8 h-8 text-secondary mb-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M7.17 15c.41-2.36 1.83-4.09 4.33-5.17V7.5A4.5 4.5 0 0 0 7 12v3H3v3h7v-3H7.17zm9 0c.41-2.36 1.83-4.09 4.33-5.17V7.5A4.5 4.5 0 0 0 16 12v3h-4v3h7v-3h-2.83z"/>
          </svg>
          <blockquote class="text-suptitle text-[#240B36] font-light mb-6">
            خدمتهم اختصرت علينا الوقت والجهد. من تصميم الموقع حتى إطلاق المتجر، كل شيء كان سلس وسريع.
          </blockquote>
          <figcaption class="flex items-center gap-3 mt-auto">
            <img src="./assets/images/user1.webp" loading="lazy" alt="نادين خليل - صاحبة متجر ملابس" class="w-12 h-12 rounded-full border-2 border-primary" />
            <div class="text-right">
              <p class="font-bold text-primary text-sm">نادين خليل</p>
              <p class="text-xs text-[#5E4A72]">صاحبة متجر ملابس</p>
            </div>
          </figcaption>
          <div class="flex gap-1 mt-2 text-yellow-400" aria-label="5 من 5 نجوم">★★★★★</div>
        </figure>
      </div>      <!-- Testimonial Card -->
      <div class="swiper-slide flex justify-center">
        <figure class="testimonial-card" style="will-change: transform, opacity;">
          <svg class="w-8 h-8 text-secondary mb-4" fill="currentColor" viewBox="0 0 24 24">
            <path d="M7.17 15c.41-2.36 1.83-4.09 4.33-5.17V7.5A4.5 4.5 0 0 0 7 12v3H3v3h7v-3H7.17zm9 0c.41-2.36 1.83-4.09 4.33-5.17V7.5A4.5 4.5 0 0 0 16 12v3h-4v3h7v-3h-2.83z"/>
          </svg>
          <blockquote class="text-suptitle text-[#240B36] font-light mb-6">
            خدمتهم اختصرت علينا الوقت والجهد. من تصميم الموقع حتى إطلاق المتجر، كل شيء كان سلس وسريع.
          </blockquote>
          <figcaption class="flex items-center gap-3 mt-auto">
            <img src="./assets/images/user1.webp" loading="lazy" alt="نادين خليل - صاحبة متجر ملابس" class="w-12 h-12 rounded-full border-2 border-primary" />
            <div class="text-right">
              <p class="font-bold text-primary text-sm">نادين خليل</p>
              <p class="text-xs text-[#5E4A72]">صاحبة متجر ملابس</p>
            </div>
          </figcaption>
          <div class="flex gap-1 mt-2 text-yellow-400" aria-label="5 من 5 نجوم">★★★★★</div>
        </figure>
      </div>

    </div>

    <!-- Slider Pagination -->
    <div class="w-full flex justify-center items-center mt-8">
      <div class="swiper-pagination flex justify-center items-center mt-6"></div>
    </div>
  </div>
</section>
<!-- Blog Section with Dark Mode -->
<section id="latest-blogs" class="py-20 px-4 sm:px-8 lg:px-24 bg-white dark:bg-gray-950 transition-colors duration-300" aria-labelledby="blog-heading">
  <div class="text-center mb-12">
    <h2 id="blog-heading" class="text-title-h2 font-extrabold text-primary dark:text-white mb-4 animate-fade-in-up" data-aos="fade-up" data-aos-delay="200">
      أحدث من المدونة
    </h2>
    <p class="text-tertiary dark:text-gray-300 text-suptitle font-light animate-fade-in-up delay-100" data-aos="fade-up" data-aos-delay="200">
      أدوات ونصائح تساعدك على تطوير أعمالك الرقمية والبقاء على اطلاع بأحدث الاتجاهات
    </p>
  </div>

  <div class="swiper blog-swiper">
    <div class="swiper-wrapper" data-aos="zoom-in" data-aos-delay="200">

      <!-- Blog Post 1 -->
      <div class="swiper-slide flex justify-center h-full">
        <article class="blog-card group bg-white dark:bg-gray-900 dark:shadow-none dark:border-gray-700" itemscope itemtype="https://schema.org/BlogPosting">
          <div class="relative">
            <img src="{{ asset('assets/tamplate/images/wordpress.webp') }}" loading="lazy"
              alt="صورة غلاف مقال: كيف تبدأ متجرك الإلكتروني بنجاح في 2024؟"
              class="blog-img">
            <span class="blog-tag bg-primary/90 dark:bg-primary">تسويق</span>
          </div>
          <div class="blog-content">
            <h3 class="blog-title text-primary dark:text-white" id="post-title-1" itemprop="headline">
              <a href="/blog/start-your-store-2024" itemprop="url" class="hover:text-secondary dark:hover:text-secondary/80 transition-colors">
                كيف تبدأ متجرك الإلكتروني بنجاح في 2024؟
              </a>
            </h3>
            <p class="text-suptitle text-tertiary dark:text-gray-300 font-light mb-4" itemprop="description">
              خطوات عملية ونصائح ذهبية لإطلاق متجرك الإلكتروني وتحقيق أولى مبيعاتك بسرعة.
            </p>
            <div class="blog-meta">
              <img src="./assets/images/user2.jpg" loading="lazy" alt="عامر موسى - كاتب المقال"
                class="w-8 h-8 rounded-full border-2 border-primary dark:border-secondary">
              <span class="text-xs text-primary dark:text-white font-bold" itemprop="name">عامر موسى</span>
              <time datetime="2024-06-12" class="text-xs text-tertiary dark:text-gray-400" itemprop="datePublished">| 12 يونيو 2024</time>
            </div>
          </div>
        </article>
      </div>
      <!-- Blog Post 2 -->
      <div class="swiper-slide flex justify-center h-full">
        <article class="blog-card group bg-white dark:bg-gray-900 dark:shadow-none dark:border-gray-700" itemscope itemtype="https://schema.org/BlogPosting">
          <div class="relative">
            <img src="{{ asset('assets/tamplate/images/wordpress.webp') }}" loading="lazy"
              alt="صورة غلاف مقال: كيف تبدأ متجرك الإلكتروني بنجاح في 2024؟"
              class="blog-img">
            <span class="blog-tag bg-primary/90 dark:bg-primary">تسويق</span>
          </div>
          <div class="blog-content">
            <h3 class="blog-title text-primary dark:text-white" id="post-title-2" itemprop="headline">
              <a href="/blog/start-your-store-2024" itemprop="url" class="hover:text-secondary dark:hover:text-secondary/80 transition-colors">
                كيف تبدأ متجرك الإلكتروني بنجاح في 2024؟
              </a>
            </h3>
            <p class="text-suptitle text-tertiary dark:text-gray-300 font-light mb-4" itemprop="description">
              خطوات عملية ونصائح ذهبية لإطلاق متجرك الإلكتروني وتحقيق أولى مبيعاتك بسرعة.
            </p>
            <div class="blog-meta">
              <img src="./assets/images/user2.jpg" loading="lazy" alt="عامر موسى - كاتب المقال"
                class="w-8 h-8 rounded-full border-2 border-primary dark:border-secondary">
              <span class="text-xs text-primary dark:text-white font-bold" itemprop="name">عامر موسى</span>
              <time datetime="2024-06-12" class="text-xs text-tertiary dark:text-gray-400" itemprop="datePublished">| 12 يونيو 2024</time>
            </div>
          </div>
        </article>
      </div>
      <!-- Blog Post 3 -->
      <div class="swiper-slide flex justify-center h-full">
        <article class="blog-card group bg-white dark:bg-gray-900 dark:shadow-none dark:border-gray-700" itemscope itemtype="https://schema.org/BlogPosting">
          <div class="relative">
            <img src="{{ asset('assets/tamplate/images/wordpress.webp') }}" loading="lazy"
              alt="صورة غلاف مقال: كيف تبدأ متجرك الإلكتروني بنجاح في 2024؟"
              class="blog-img">
            <span class="blog-tag bg-primary/90 dark:bg-primary">تسويق</span>
          </div>
          <div class="blog-content">
            <h3 class="blog-title text-primary dark:text-white" id="post-title-3" itemprop="headline">
              <a href="/blog/start-your-store-2024" itemprop="url" class="hover:text-secondary dark:hover:text-secondary/80 transition-colors">
                كيف تبدأ متجرك الإلكتروني بنجاح في 2024؟
              </a>
            </h3>
            <p class="text-suptitle text-tertiary dark:text-gray-300 font-light mb-4" itemprop="description">
              خطوات عملية ونصائح ذهبية لإطلاق متجرك الإلكتروني وتحقيق أولى مبيعاتك بسرعة.
            </p>
            <div class="blog-meta">
              <img src="./assets/images/user2.jpg" loading="lazy" alt="عامر موسى - كاتب المقال"
                class="w-8 h-8 rounded-full border-2 border-primary dark:border-secondary">
              <span class="text-xs text-primary dark:text-white font-bold" itemprop="name">عامر موسى</span>
              <time datetime="2024-06-12" class="text-xs text-tertiary dark:text-gray-400" itemprop="datePublished">| 12 يونيو 2024</time>
            </div>
          </div>
        </article>
      </div>
      <!-- Blog Post 4 -->
      <div class="swiper-slide flex justify-center h-full">
        <article class="blog-card group bg-white dark:bg-gray-900 dark:shadow-none dark:border-gray-700" itemscope itemtype="https://schema.org/BlogPosting">
          <div class="relative">
            <img src="{{ asset('assets/tamplate/images/wordpress.webp') }}" loading="lazy"
              alt="صورة غلاف مقال: كيف تبدأ متجرك الإلكتروني بنجاح في 2024؟"
              class="blog-img">
            <span class="blog-tag bg-primary/90 dark:bg-primary">تسويق</span>
          </div>
          <div class="blog-content">
            <h3 class="blog-title text-primary dark:text-white" id="post-title-4" itemprop="headline">
              <a href="/blog/start-your-store-2024" itemprop="url" class="hover:text-secondary dark:hover:text-secondary/80 transition-colors">
                كيف تبدأ متجرك الإلكتروني بنجاح في 2024؟
              </a>
            </h3>
            <p class="text-suptitle text-tertiary dark:text-gray-300 font-light mb-4" itemprop="description">
              خطوات عملية ونصائح ذهبية لإطلاق متجرك الإلكتروني وتحقيق أولى مبيعاتك بسرعة.
            </p>
            <div class="blog-meta">
              <img src="./assets/images/user2.jpg" loading="lazy" alt="عامر موسى - كاتب المقال"
                class="w-8 h-8 rounded-full border-2 border-primary dark:border-secondary">
              <span class="text-xs text-primary dark:text-white font-bold" itemprop="name">عامر موسى</span>
              <time datetime="2024-06-12" class="text-xs text-tertiary dark:text-gray-400" itemprop="datePublished">| 12 يونيو 2024</time>
            </div>
          </div>
        </article>
      </div>
      <!-- Blog Post 5 -->
      <div class="swiper-slide flex justify-center h-full">
        <article class="blog-card group bg-white dark:bg-gray-900 dark:shadow-none dark:border-gray-700" itemscope itemtype="https://schema.org/BlogPosting">
          <div class="relative">
            <img src="{{ asset('assets/tamplate/images/wordpress.webp') }}" loading="lazy"
              alt="صورة غلاف مقال: كيف تبدأ متجرك الإلكتروني بنجاح في 2024؟"
              class="blog-img">
            <span class="blog-tag bg-primary/90 dark:bg-primary">تسويق</span>
          </div>
          <div class="blog-content">
            <h3 class="blog-title text-primary dark:text-white" id="post-title-5" itemprop="headline">
              <a href="/blog/start-your-store-2024" itemprop="url" class="hover:text-secondary dark:hover:text-secondary/80 transition-colors">
                كيف تبدأ متجرك الإلكتروني بنجاح في 2024؟
              </a>
            </h3>
            <p class="text-suptitle text-tertiary dark:text-gray-300 font-light mb-4" itemprop="description">
              خطوات عملية ونصائح ذهبية لإطلاق متجرك الإلكتروني وتحقيق أولى مبيعاتك بسرعة.
            </p>
            <div class="blog-meta">
              <img src="./assets/images/user2.jpg" loading="lazy" alt="عامر موسى - كاتب المقال"
                class="w-8 h-8 rounded-full border-2 border-primary dark:border-secondary">
              <span class="text-xs text-primary dark:text-white font-bold" itemprop="name">عامر موسى</span>
              <time datetime="2024-06-12" class="text-xs text-tertiary dark:text-gray-400" itemprop="datePublished">| 12 يونيو 2024</time>
            </div>
          </div>
        </article>
      </div>
    </div>

    <!-- Slider Pagination -->
    <div class="w-full flex justify-center items-center mt-8">
      <div class="swiper-pagination flex justify-center items-center mt-6"></div>
    </div>
  </div>

  <!-- زر استعراض المزيد -->
  <footer class="flex justify-center items-center gap-4 mt-10">
    <a href="/blog" aria-label="عرض جميع مقالات المدونة"
      class="relative inline-flex items-center justify-center px-10 py-3 overflow-hidden font-extrabold text-lg text-white rounded-lg shadow-lg bg-primary transition-all duration-300 hover:bg-primary/30 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2">
      <span class="absolute inset-0 w-full h-full bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300 dark:bg-white/10"></span>
      <span class="relative z-10 flex items-center gap-2 rtl:flex-row">
        <svg class="w-5 h-5 text-white group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
        مشاهدة المزيد
      </span>
    </a>
  </footer>
</section>
</x-tamplate.layouts.index-layouts>