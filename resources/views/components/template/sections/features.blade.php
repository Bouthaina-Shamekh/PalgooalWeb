<section class="py-28 px-4 sm:px-8 lg:px-24 bg-background" dir="auto" aria-labelledby="features-heading">
  <div class="max-w-7xl mx-auto">
    <div class="text-center mb-16">
      <h2 class="text-3xl sm:text-4xl font-extrabold text-primary mb-4 tracking-tight">
        {{ $data['title'] ?? 'عنوان غير متوفر' }}
      </h2>
      <p class="text-tertiary text-suptitle/9 sm:text-suptitle/9 max-w-2xl mx-auto">
        {{ $data['subtitle'] ?? '' }}
      </p>
    </div>

    <dl class="grid gap-16 lg:grid-cols-5 items-center">
      <!-- Illustration (اختياري) -->
      <div class="lg:col-span-2 flex justify-center">
        <img src="{{ asset('assets/images/Fu.svg') }}" alt="مميزات المنصة"
             class="max-w-[300px] sm:max-w-sm lg:max-w-[500px] w-full h-auto object-contain mx-auto animate-fade-in-up hover:scale-105 transition-transform duration-500"
             loading="lazy">
      </div>

      <!-- الميزات -->
      <div class="lg:col-span-3 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-y-12 gap-x-6 text-center sm:text-start">
        @foreach ($data['features'] ?? [] as $feature)
          <div>
            <dt class="flex flex-col items-center sm:items-start gap-4">
              <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-primary/20 shrink-0">
                {!! $feature['icon'] ?? '' !!}
              </div>
              <span class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ $feature['title'] ?? 'عنوان' }}
              </span>
            </dt>
            <dd class="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
              {{ $feature['description'] ?? 'وصف مختصر' }}
            </dd>
          </div>
        @endforeach
      </div>
    </dl>
  </div>
</section>
