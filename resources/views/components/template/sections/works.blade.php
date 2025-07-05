<!-- أحدث أعمالنا -->
<section class="dark:bg-gray-900 text-primary dark:text-white py-16 px-4 sm:px-8 lg:px-24 rtl:text-right ltr:text-left" aria-labelledby="why-us-heading">
  <header class="text-center mb-12">
    <h2 id="why-us-heading" class="text-title-h2 font-extrabold animate-fade-in-up" data-aos="fade-up" data-aos-delay="200">
      {{ $data['title'] ?? 'عنوان غير متوفر' }}
    </h2>
    <p class="text-tertiary mt-4 text-suptitle max-w-max mx-auto animate-fade-in-up delay-100" data-aos="fade-up" data-aos-delay="200">
      {{ $data['subtitle'] ?? '' }}
    </p>
  </header>

  <main class="relative group" aria-label="نماذج من أعمالنا" role="region" data-aos="zoom-in" data-aos-delay="200">
    <div class="swiper" id="clients-swiper">
      <div class="swiper-wrapper">
        <div class="swiper-slide flex flex-col items-center text-center animate-fade-in-up hover:scale-105 transition-transform duration-300">
          <div class="w-full max-w-xs overflow-hidden rounded-lg">
            <img src="./assets/images/Group 34130.jpg" role="img" alt="موقع شركة مقاولات" class="rounded-lg hover:brightness-90 transition duration-300" loading="lazy" decoding="async" />
          </div>
          <h3 class="font-extrabold text-suptitle mt-4 animate-fade-in-up delay-150 text-center text-primary dark:text-white">موقع شركة مقاولات</h3>
        </div>
         <div class="swiper-slide flex flex-col items-center text-center animate-fade-in-up hover:scale-105 transition-transform duration-300">
          <div class="w-full max-w-xs overflow-hidden rounded-lg">
            <img src="./assets/images/Group 34130.jpg" role="img" alt="موقع شركة مقاولات" class="rounded-lg hover:brightness-90 transition duration-300" loading="lazy" decoding="async" />
          </div>
          <h3 class="font-extrabold text-suptitle mt-4 animate-fade-in-up delay-150 text-center text-primary dark:text-white">موقع شركة مقاولات</h3>
        </div>
        <div class="swiper-slide flex flex-col items-center text-center animate-fade-in-up hover:scale-105 transition-transform duration-300">
          <div class="w-full max-w-xs overflow-hidden rounded-lg">
            <img src="./assets/images/Group 34130.jpg" role="img" alt="موقع شركة مقاولات" class="rounded-lg hover:brightness-90 transition duration-300" loading="lazy" decoding="async" />
          </div>
          <h3 class="font-extrabold text-suptitle mt-4 animate-fade-in-up delay-150 text-center text-primary dark:text-white">موقع شركة مقاولات</h3>
        </div>
        <div class="swiper-slide flex flex-col items-center text-center animate-fade-in-up hover:scale-105 transition-transform duration-300">
          <div class="w-full max-w-xs overflow-hidden rounded-lg">
            <img src="./assets/images/Group 34130.jpg" role="img" alt="موقع شركة مقاولات" class="rounded-lg hover:brightness-90 transition duration-300" loading="lazy" decoding="async" />
          </div>
          <h3 class="font-extrabold text-suptitle mt-4 animate-fade-in-up delay-150 text-center text-primary dark:text-white">موقع شركة مقاولات</h3>
        </div>
        <div class="swiper-slide flex flex-col items-center text-center animate-fade-in-up hover:scale-105 transition-transform duration-300">
          <div class="w-full max-w-xs overflow-hidden rounded-lg">
            <img src="./assets/images/Group 34130.jpg" role="img" alt="موقع شركة مقاولات" class="rounded-lg hover:brightness-90 transition duration-300" loading="lazy" decoding="async" />
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