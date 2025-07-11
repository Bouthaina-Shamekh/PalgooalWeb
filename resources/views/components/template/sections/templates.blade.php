<!-- تصاميم عصرية واحترافية -->
<section class="bg-background dark:bg-gray-900 text-primary dark:text-white py-16 px-4 sm:px-8 lg:px-16 rtl:text-right ltr:text-left">
  <header class="text-center mb-10">
    <h2 class="text-title-h2 font-extrabold mb-2 animate-fade-in-up" data-aos="fade-up" data-aos-delay="200">
      {{ $data['title'] ?? 'عنوان غير متوفر' }}
    </h2>
    <p class="text-tertiary text-base dark:text-gray-300 sm:text-lg max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
      {{ $data['subtitle'] ?? '' }}
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
        <img itemprop="image" src="./assets/images/2-1-1.webp" type="image/webp" alt="قالب متجر ملابس نسائية عصري" class="w-full h-40 object-cover transition-transform duration-300 group-hover:scale-105 group-hover:brightness-95" loading="lazy" decoding="async">
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