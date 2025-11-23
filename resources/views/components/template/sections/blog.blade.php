@php
    use Illuminate\Support\Facades\Cache;
    use App\Services\Feeds\BlogFeedService;

    $blogs = Cache::remember('latest_blog_posts_with_images', now()->addMinutes(60), function () {
        return (new BlogFeedService())->getLatest(5);
    });
    // $blogs = (new BlogFeedService())->getLatest(5);
@endphp

<!-- Blog Section with Dark Mode -->
<section id="latest-blogs" class="py-20 px-4 sm:px-8 lg:px-24 bg-white dark:bg-gray-950 transition-colors duration-300" aria-labelledby="blog-heading">
  <div class="text-center mb-12">
    <h2 id="blog-heading" class="text-title-h2 font-extrabold text-primary dark:text-white mb-4 animate-fade-in-up" data-aos="fade-up" data-aos-delay="200">
      {{ $data['title'] ?? 'عنوان غير متوفر' }}
    </h2>
    <p class="text-tertiary dark:text-gray-300 text-suptitle font-light animate-fade-in-up delay-100" data-aos="fade-up" data-aos-delay="200">
      {{ $data['subtitle'] ?? '' }}
    </p>
  </div>

  <div class="swiper blog-swiper">
    <div class="swiper-wrapper" data-aos="zoom-in" data-aos-delay="200">
      @foreach ($blogs as $blog)
        @php
            $imageUrl = $blog['image'] ?? asset('assets/tamplate/images/wordpress.webp');
        @endphp
    <div class="swiper-slide flex justify-center h-full">
        <article class="blog-card group bg-white dark:bg-gray-900 dark:shadow-none dark:border-gray-700" itemscope itemtype="https://schema.org/BlogPosting">
            <div class="relative">
                <img src="{{ $imageUrl }}" loading="lazy" alt="صورة غلاف مقال: {{ $blog['title'] }}" class="blog-img">
                <span class="blog-tag bg-primary/90 dark:bg-primary">{{ $blog['categories'][0] ?? 'مدونة' }}</span>
            </div>
            <div class="blog-content">
                <h3 class="blog-title text-primary dark:text-white" itemprop="headline">
                    <a href="{{ $blog['url'] }}" target="_blank" itemprop="url" class="hover:text-secondary dark:hover:text-secondary/80 transition-colors">
                        {{ $blog['title'] }}
                    </a>
                </h3>
                <p class="text-suptitle text-tertiary dark:text-gray-300 font-light mb-4" itemprop="description">
                    {{ Str::limit($blog['description'], 100) }}
                </p>
                <div class="blog-meta">
                    <span class="text-xs text-primary dark:text-white font-bold" itemprop="name">{{ $blog['author'] }}</span>
                    <time datetime="{{ $blog['date'] }}" class="text-xs text-tertiary dark:text-gray-400" itemprop="datePublished">
                        | {{ \Carbon\Carbon::parse($blog['date'])->translatedFormat('d F Y') }}
                    </time>
                </div>
            </div>
        </article>
    </div>
@endforeach


      <!-- Blog Post 1 -->
      {{-- <div class="swiper-slide flex justify-center h-full">
        <article class="blog-card group bg-white dark:bg-gray-900 dark:shadow-none dark:border-gray-700" itemscope itemtype="https://schema.org/BlogPosting">
          <div class="relative">
            <img src="./assets/images/وردبرس.webp" loading="lazy"
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
            <img src="./assets/images/وردبرس.webp" loading="lazy"
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
            <img src="./assets/images/وردبرس.webp" loading="lazy"
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
            <img src="./assets/images/وردبرس.webp" loading="lazy"
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
            <img src="./assets/images/وردبرس.webp" loading="lazy"
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
      </div> --}}
    </div>

    <!-- Slider Pagination -->
    <div class="w-full flex justify-center items-center mt-8">
      <div class="swiper-pagination flex justify-center items-center mt-6"></div>
    </div>
  </div>

  <!-- زر استعراض المزيد -->
  <footer class="flex justify-center items-center gap-4 mt-10">
    <a href="{{ $data['button_url-1'] ?? '#' }}" aria-label="عرض جميع مقالات المدونة"
      class="relative inline-flex items-center justify-center px-10 py-3 overflow-hidden font-extrabold text-lg text-white rounded-lg shadow-lg bg-primary transition-all duration-300 hover:bg-primary/30 focus:outline-none focus:ring-2 focus:ring-secondary focus:ring-offset-2">
      <span class="absolute inset-0 w-full h-full bg-white opacity-0 group-hover:opacity-10 transition-opacity duration-300 dark:bg-white/10"></span>
      <span class="relative z-10 flex items-center gap-2 rtl:flex-row">
        <svg class="w-5 h-5 text-white group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
        {{ $data['button_text-1'] ?? '' }}
      </span>
    </a>
  </footer>
</section>
