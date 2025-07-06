<main>
  <section class="relative bg-gradient-to-tr from-primary to-primary shadow-2xl overflow-hidden -mt-20">
    <img
      src="{{ asset('assets/tamplate/images/template.webp') }}"
      alt=""
      fetchpriority="high"
      class="absolute inset-0 z-0 opacity-80 w-full h-full object-cover object-center ltr:scale-x-[-1] rtl:scale-x-100 transition-transform duration-500 ease-in-out"
      aria-hidden="true"
      decoding="async"
      loading="eager"
    />

    <div class="relative z-10 px-4 sm:px-8 lg:px-24 py-20 sm:py-28 lg:py-32 flex flex-col-reverse md:flex-row items-center justify-between gap-12 min-h-[600px] lg:min-h-[700px]">
      <div class="max-w-xl rtl:text-right ltr:text-left text-center md:text-start">
        <h1 class="text-3xl/20 sm:text-4xl/20 lg:text-5xl/20 font-extrabold text-white leading-tight drop-shadow-lg mb-6">
          {{ $data['title'] ?? 'عنوان غير متوفر' }}
        </h1>
        <p class="text-white/90 text-base sm:text-lg font-light mb-8">
          {{ $data['subtitle'] ?? '' }}
        </p>
        <div class="flex flex-row flex-wrap gap-3 justify-center md:justify-start">
          <a href="{{ $data['button_url-1'] }}"
             aria-label="{{ $data['button_text-1'] }}"
             class="bg-secondary hover:bg-primary text-white font-bold px-6 py-3 rounded-lg shadow transition text-sm sm:text-base">
            {{ $data['button_text-1'] }}
          </a>
          <a href="{{ $data['button_url-2'] ?? '#' }}"
             class="bg-white/10 text-white font-bold px-6 py-3 rounded-lg shadow transition hover:bg-white/20 text-sm sm:text-base border border-white/30">
            {{ $data['button_text-2'] ?? 'استعرض القوالب' }}
          </a>
        </div>
      </div>
    </div>

    <div class="absolute -bottom-20 -left-20 w-96 h-96 bg-white/10 rounded-full blur-3xl z-0"></div>
  </section>
</main>
