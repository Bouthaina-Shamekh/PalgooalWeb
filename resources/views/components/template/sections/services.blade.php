
<!-- خدمات رقمية متكاملة -->
<section class="py-20 px-4 sm:px-8 lg:px-24 bg-white" dir="rtl" aria-label="خدمات رقمية متكاملة">
  <div class="relative">
    <div class="relative z-10 max-w-7xl mx-auto">
      <div class="text-center mb-16">
        <h2 class="text-3xl sm:text-4xl font-extrabold text-primary mb-4 tracking-tight" data-aos="fade-up" data-aos-delay="100">
          {{ $data['title'] ?? 'عنوان غير متوفر' }}
        </h2>
        <p class="text-tertiary text-base sm:text-lg max-w-2xl mx-auto" data-aos="fade-up" data-aos-delay="200">
          {{ $data['subtitle'] ?? '' }}
        </p>
      </div>

      <!-- Slider -->
      <div class="swiper mySwiper" aria-label="{{ $data['title'] ?? 'عنوان غير متوفر' }}" role="region">
        <div class="swiper-wrapper">
          @foreach($data['services'] as $service)
            @php
              $translation = $service->translations->firstWhere('locale', app()->getLocale()) ?? $service->translations->first();
            @endphp
            <div class="swiper-slide" data-aos="zoom-in" data-aos-delay="100">
              <a href="{{ $service->url ?? '#' }}" class="group bg-white rounded-3xl shadow-xl p-8 flex flex-col items-center text-center border border-primary/10 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 h-full">
                
                <div class="bg-primary/10 group-hover:bg-primary/20 rounded-full p-4 mb-5 transition">
                  <img src="{{ asset('storage/' . $service->icon) }}" alt="{{ $translation->title }}" class="w-14 h-14" loading="lazy" />
                </div>
                <h3 class="font-bold text-lg text-primary mb-2 group-hover:text-secondary transition">{{ $translation->title }}</h3>
                <p class="text-tertiary text-sm mb-4">{{ $translation->description }}</p>
              </a>
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