@php
    use Illuminate\Support\Str;
@endphp

@php
    $testimonials = collect($data['testimonials'] ?? []);
@endphp
<!-- Testimonials Section -->
<section id="testimonials" class="bg-background py-20 px-4 sm:px-8 lg:px-24">
  <div class="max-w-4xl mx-auto text-center mb-12">
    <h2 class="text-title-h2 font-extrabold text-primary mb-4" data-aos="fade-up" data-aos-delay="200">
      {{ $data['title'] ?? 'Testimonials' }}
    </h2>
    <p class="text-tertiary text-suptitle mx-auto animate-fade-in-up delay-100" data-aos="fade-up" data-aos-delay="200">
      {{ $data['subtitle'] ?? '' }}
    </p>
  </div>

  @php
      $testimonials = collect($data['testimonials'] ?? []);
  @endphp

  @if ($testimonials->isNotEmpty())
    <div class="swiper testimonials-swiper">
      <div class="swiper-wrapper" data-aos="zoom-in" data-aos-delay="200">
        @foreach ($testimonials as $testimonial)
          @php
                    $translation = $testimonial->translations
                        ->firstWhere('locale', app()->getLocale())
                        ?? $testimonial->translations->first();

                    // الصورة من علاقة media (image_id → Media)
                    $imageUrl = $testimonial->image?->url ?? asset('assets/images/user1.webp');

                    $stars = max(0, min(5, (int) ($testimonial->star ?? 0)));
                @endphp

          <div class="swiper-slide flex justify-center">
            <figure class="testimonial-card" style="will-change: transform, opacity;">
              <svg class="w-8 h-8 text-secondary mb-4" fill="currentColor" viewBox="0 0 24 24">
                <path d="M7.17 15c.41-2.36 1.83-4.09 4.33-5.17V7.5A4.5 4.5 0 0 0 7 12v3H3v3h7v-3H7.17zm9 0c.41-2.36 1.83-4.09 4.33-5.17V7.5A4.5 4.5 0 0 0 16 12v3h-4v3h7v-3h-2.83z"/>
              </svg>
              <blockquote class="text-suptitle text-[#240B36] font-light mb-6">
                {{ $translation?->feedback ?? '' }}
              </blockquote>
              <figcaption class="flex items-center gap-3 mt-auto">
                <img src="{{ $imageUrl }}" loading="lazy" alt="{{ $translation?->name ?? 'Customer' }} avatar" class="w-12 h-12 rounded-full border-2 border-primary" />
                <div class="text-right">
                  <p class="font-bold text-primary text-sm">{{ $translation?->name ?? '' }}</p>
                  <p class="text-xs text-[#5E4A72]">{{ $translation?->major ?? '' }}</p>
                </div>
              </figcaption>
              <div class="flex gap-1 mt-2" aria-label="{{ $stars }} out of 5 stars">
                @for ($i = 0; $i < $stars; $i++)
                  <span class="text-yellow-400" aria-hidden="true">&starf;</span>
                @endfor
                @for ($i = $stars; $i < 5; $i++)
                  <span class="text-gray-300" aria-hidden="true">&star;</span>
                @endfor
              </div>
            </figure>
          </div>
        @endforeach
      </div>

      <!-- Slider Pagination -->
      <div class="w-full flex justify-center items-center mt-8">
        <div class="swiper-pagination flex justify-center items-center mt-6"></div>
      </div>
    </div>
  @else
    <div class="mt-8 text-center text-tertiary" data-aos="fade-up" data-aos-delay="200">
      {{ __('No testimonials available yet.') }}
    </div>
  @endif
</section>
