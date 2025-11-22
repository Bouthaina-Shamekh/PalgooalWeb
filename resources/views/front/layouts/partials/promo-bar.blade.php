<!-- Promo Bar -->
<div id="promo-bar"
     role="banner"
     aria-label="عرض ترويجي مؤقت"
     class="sticky top-0 z-40 px-4 sm:px-20 py-4 shadow-md text-sm sm:text-base text-white bg-secondary dark:bg-primary dark:text-gray-100 transition-opacity duration-300">

  <div class="container mx-auto flex flex-col md:flex-row justify-between items-center gap-2 text-center md:text-start">
    <!-- Promo Text -->
    <div class="flex flex-wrap items-center gap-2 justify-center md:justify-start text-start">
      <span class="font-bold">🎉 عرض محدود:</span>
      <span>احصل على موقعك الآن خلال 5 دقائق وبأقل تكلفة</span>
      <a href="/templates" class="underline underline-offset-4 hover:text-primary dark:hover:text-primary transition font-bold">
        تصفح القوالب
      </a>
    </div>
    <!-- Timer -->
    <div class="flex items-center gap-2 font-semibold">
      ⏳ ينتهي خلال:
      <span id="promo-time" class="bg-white text-secondary dark:bg-gray-200 dark:text-primary px-2 py-0.5 rounded text-sm"></span>
    </div>
  </div>

  <!-- Close Button -->
  <button id="close-promo-bar"
          class="absolute top-2 right-4 text-white dark:text-gray-200 hover:text-primary dark:hover:text-yellow-400 transition text-xl font-bold leading-none"
          aria-label="إغلاق الشريط">
    &times;
  </button>
</div>