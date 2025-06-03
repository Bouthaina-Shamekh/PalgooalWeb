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
<!-- Header -->
<header class="bg-white dark:bg-[#1c1c1c] shadow-md sticky top-0 z-50">
    <div class="flex items-center justify-between py-3 px-4 md:px-8 lg:px-24 h-20">
        <!-- Logo -->
        <a href="/" class="flex items-center gap-2 group">
            <img src="{{ asset('assets/tamplate/images/logo.svg') }}" alt="Palgoals Logo" loading="eager" fetchpriority="high"
                class="h-10 w-auto transition-transform group-hover:scale-105 will-change-transform" />
        </a>
        <!-- Desktop Navigation -->
        <nav role="navigation" aria-label="القائمة الرئيسية">
            <ul class="hidden md:flex items-center gap-7 font-semibold text-primary dark:text-white text-base">
                <li><a href="#" class="hover:text-secondary dark:hover:text-yellow-400 transition">الرئيسية</a></li>
                <!-- Hosting Dropdown -->
                <li class="relative group">
                    <div class="flex items-center gap-1 cursor-pointer hover:text-secondary dark:hover:text-yellow-400 transition"
                        role="button" tabindex="0" aria-expanded="false">
                        <span>الاستضافة</span>
                        <svg class="w-4 h-4 mt-0.5 transform transition-transform group-hover:rotate-180 will-change-transform" fill="none"
                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <ul class="absolute top-full end-0 mt-2 w-48 bg-white dark:bg-[#2c2c2c] border border-gray-200 dark:border-gray-700
                        rounded-lg shadow-md z-50 text-sm font-normal flex-col opacity-0 invisible scale-95
                        group-hover:opacity-100 group-hover:visible group-hover:scale-100 transition-all duration-200">
                        <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/20">الاستضافة المشتركة</a></li>
                        <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/20">استضافة ووردبريس</a></li>
                    </ul>
                </li>
                <li><a href="/dist/domain/domains.html" class="hover:text-secondary dark:hover:text-yellow-400 transition">دومين</a></li>
                <!-- Blog Dropdown -->
                <li class="relative group">
                    <div class="flex items-center gap-1 cursor-pointer hover:text-secondary dark:hover:text-yellow-400 transition"
                        role="button" tabindex="0" aria-expanded="false">
                        <span>المدونة</span>
                        <svg class="w-4 h-4 mt-0.5 transform transition-transform group-hover:rotate-180 will-change-transform" fill="none"
                            stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                    <ul class="absolute top-full end-0 mt-2 w-48 bg-white dark:bg-[#2c2c2c] border border-gray-200 dark:border-gray-700
                        rounded-lg shadow-md z-50 text-sm font-normal flex-col opacity-0 invisible scale-95
                        group-hover:opacity-100 group-hover:visible group-hover:scale-100 transition-all duration-200">
                        <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/20">الاستضافة</a></li>
                        <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/20">التسويق الالكتروني</a></li>
                        <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/20">صميم المواقع</a></li>
                        <li><a href="#" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/20">مقالات متنوعة</a></li>
                    </ul>
                </li>
                <li><a href="#" class="hover:text-secondary dark:hover:text-yellow-400 transition">أعمالنا</a></li>
                <li><a href="#" class="hover:text-secondary dark:hover:text-yellow-400 transition">تواصل</a></li>
            </ul>
        </nav>
        <!-- Header Actions -->
        <div class="flex items-center gap-2 sm:gap-4">
            <!-- Language Switch -->
            <div class="relative group" id="lang-container">
                <button id="lang-switch"
                    class="flex items-center gap-1 text-primary dark:text-white font-semibold hover:text-secondary dark:hover:text-yellow-400 text-sm"
                    aria-haspopup="true" aria-controls="lang-menu">
                    🌐 <span id="current-label">AR</span>
                </button>
                <div id="lang-menu"
                    class="absolute left-0 mt-2 w-28 bg-white dark:bg-[#2c2c2c] border border-gray-200 dark:border-gray-700 rounded-md shadow-md z-40 opacity-0 invisible group-hover:opacity-100 group-hover:visible md:transition-all md:duration-200">
                    <button class="block w-full text-right px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-white/20" data-lang="AR">العربية</button>
                    <button class="block w-full text-right px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-white/20" data-lang="EN">English</button>
                </div>
            </div>
            <!-- User Menu -->
            <div class="relative hidden md:block">
                <button id="user-menu-toggle"
                    class="inline-flex items-center gap-2 px-4 py-1.5 rounded-lg border border-primary text-primary dark:text-white dark:border-white text-sm font-semibold hover:bg-primary/10 dark:hover:bg-white/20 transition-all duration-200"
                    aria-label="القائمة الشخصية">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A11.963 11.963 0 0112 15c2.21 0 4.266.642 5.879 1.742M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>تسجيل / دخول</span>
                </button>
                <div id="user-menu" class="absolute end-0 mt-2 w-48 bg-white dark:bg-[#1c1c1c] border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-50 text-sm font-normal hidden">
                    <a href="/login" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/20 text-primary dark:text-white">تسجيل الدخول</a>
                    <a href="/register" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/20 text-primary dark:text-white">إنشاء حساب</a>
                </div>
            </div>
            <!-- Mobile Toggle -->
            <button id="sidebar-toggle" class="md:hidden p-2 rounded text-primary dark:text-white hover:bg-primary/10 dark:hover:bg-white/20"
                aria-label="فتح القائمة">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>
    </div>
    <!-- Overlay خلفي للشريط الجانبي -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-20 z-40 hidden opacity-0 md:hidden transition-opacity duration-300"></div>
    <!-- Sidebar Mobile Menu -->
    <div id="mobileSidebar" class="fixed inset-y-0 end-0 w-72 bg-white dark:bg-[#1c1c1c] shadow-xl z-50 text-primary dark:text-white font-semibold text-base flex-col md:hidden transform translate-x-full transition-transform duration-300 ease-in-out invisible opacity-0">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <span class="text-lg font-bold">القائمة</span>
            <button id="sidebar-close" class="text-xl hover:text-secondary dark:hover:text-yellow-400" aria-label="إغلاق القائمة">&times;</button>
        </div>
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <a href="#" class="block text-lg hover:text-secondary dark:hover:text-yellow-400">الرئيسية</a>
            <!-- Hosting Dropdown -->
            <div class="relative">
                <button type="button" aria-expanded="false"
                    class="w-full flex items-center justify-between text-lg text-right hover:text-secondary dark:hover:text-yellow-400 transition"
                    onclick="toggleMobileDropdown(this)">
                    الاستضافة
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="hidden mt-2 space-y-1 text-sm transition-all duration-300 ease-in-out">
                    <a href="#" class="block py-1 hover:text-secondary dark:hover:text-yellow-400">الاستضافة المشتركة</a>
                    <a href="#" class="block py-1 hover:text-secondary dark:hover:text-yellow-400">استضافة ووردبريس</a>
                </div>
            </div>
            <a href="#" class="block text-lg hover:text-secondary dark:hover:text-yellow-400">دومين</a>
            <!-- Blog Dropdown -->
            <div class="relative">
                <button type="button" aria-expanded="false"
                    class="w-full flex items-center justify-between text-lg text-right hover:text-secondary dark:hover:text-yellow-400 transition"
                    onclick="toggleMobileDropdown(this)">
                    المدونة
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div class="hidden mt-2 space-y-1 text-sm transition-all duration-300 ease-in-out">
                    <a href="#" class="block py-1 hover:text-secondary dark:hover:text-yellow-400">استضافة المواقع</a>
                    <a href="#" class="block py-1 hover:text-secondary dark:hover:text-yellow-400">التسويق الإلكتروني</a>
                    <a href="#" class="block py-1 hover:text-secondary dark:hover:text-yellow-400">تصميم المواقع</a>
                    <a href="#" class="block py-1 hover:text-secondary dark:hover:text-yellow-400">مقالات متنوعة</a>
                </div>
            </div>
            <a href="#" class="block text-lg hover:text-secondary dark:hover:text-yellow-400">أعمالنا</a>
            <a href="#" class="block text-lg hover:text-secondary dark:hover:text-yellow-400">تواصل</a>
            <!-- User Menu مباشر للجوال -->
            <div class="mt-4 border-t pt-4 border-gray-200 dark:border-gray-700">
                <a href="/login" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/10 text-primary dark:text-white">تسجيل الدخول</a>
                <a href="/register" class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-white/10 text-primary dark:text-white">إنشاء حساب</a>
            </div>
        </div>
    </div>
</header>