/**
 * بال قول - الملف الرئيسي للجافاسكريبت
 * يحتوي على جميع الوظائف الأساسية للموقع بدون الاعتماد على مكتبات خارجية
 */

// تهيئة الصفحة عند التحميل
document.addEventListener('DOMContentLoaded', function() {
  // تهيئة شريط الترويج مع المؤقت
  initPromoTimer();
  
  // تهيئة مبدل اللغة
  initLanguageSwitcher();
  
  // تهيئة القائمة المتنقلة للأجهزة المحمولة
  initMobileMenu();
  
  // تهيئة القوائم المنسدلة
  initDropdowns();
  
  // تهيئة العروض المتحركة
  initSliders();
  
  // تهيئة وضع الظلام
  initDarkMode();
  
  // تهيئة التمرير باللمس للقائمة الجانبية
  initTouchSwipe();
});

/**
 * تهيئة شريط الترويج مع المؤقت
 */
function initPromoTimer() {
  const promoBar = document.getElementById('promoBar');
  const closeButton = document.querySelector('#promoBar button[aria-label="إغلاق الشريط"]');
  const timerElement = document.querySelector('#promoBar span.timer-display');
  
  if (!promoBar || !timerElement) return;
  
  let duration = 600; // 10 دقائق بالثواني
  let show = true;
  
  // تحديث المؤقت كل ثانية
  const timerInterval = setInterval(() => {
    if (!show) {
      clearInterval(timerInterval);
      return;
    }
    
    duration--;
    
    // إذا انتهى الوقت، أخفِ الشريط
    if (duration <= 0) {
      clearInterval(timerInterval);
      promoBar.style.display = 'none';
      show = false;
      return;
    }
    
    // حساب الدقائق والثواني المتبقية
    const mins = Math.floor(duration / 60);
    const secs = duration % 60;
    
    // تحديث نص المؤقت
    timerElement.textContent = `${mins}:${secs < 10 ? '0' + secs : secs}`;
  }, 1000);
  
  // إضافة حدث النقر لزر الإغلاق
  if (closeButton) {
    closeButton.addEventListener('click', () => {
      promoBar.style.display = 'none';
      show = false;
      clearInterval(timerInterval);
    });
  }
}

/**
 * تهيئة مبدل اللغة
 */
function initLanguageSwitcher() {
  const languageButton = document.getElementById('languageButton');
  const languageMenu = document.getElementById('languageMenu');
  const languageOptions = document.querySelectorAll('#languageMenu button');
  const currentLanguageLabel = document.getElementById('currentLanguageLabel');
  
  if (!languageButton || !languageMenu || !currentLanguageLabel) return;
  
  // تعيين اللغة الحالية من التخزين المحلي أو الافتراضية
  let currentLang = localStorage.getItem('lang') || 'ar';
  document.documentElement.lang = currentLang;
  document.documentElement.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
  currentLanguageLabel.textContent = currentLang === 'ar' ? 'العربية' : 'English';
  
  // إضافة حدث النقر لزر اللغة
  languageButton.addEventListener('click', () => {
    const isOpen = !languageMenu.classList.contains('hidden');
    
    if (isOpen) {
      languageMenu.classList.add('hidden');
    } else {
      languageMenu.classList.remove('hidden');
    }
  });
  
  // إغلاق القائمة عند النقر خارجها
  document.addEventListener('click', (event) => {
    if (!languageButton.contains(event.target) && !languageMenu.contains(event.target)) {
      languageMenu.classList.add('hidden');
    }
  });
  
  // إضافة أحداث النقر لخيارات اللغة
  languageOptions.forEach(option => {
    option.addEventListener('click', () => {
      const lang = option.dataset.lang;
      const label = lang === 'ar' ? 'العربية' : 'English';
      
      // تحديث نص اللغة الحالية
      currentLanguageLabel.textContent = label;
      
      // تغيير اتجاه الصفحة بناءً على اللغة
      document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
      document.documentElement.lang = lang;
      localStorage.setItem('lang', lang);
      
      // إعادة تهيئة العروض المتحركة بعد تغيير الاتجاه
      setTimeout(() => {
        initSliders();
      }, 100);
      
      // إغلاق القائمة
      languageMenu.classList.add('hidden');
    });
  });
}

/**
 * تبديل اتجاه الكتابة
 */
function toggleDirection() {
  const currentDir = document.documentElement.dir;
  const newDir = currentDir === 'rtl' ? 'ltr' : 'rtl';
  
  document.documentElement.dir = newDir;
  
  // إعادة تهيئة العروض المتحركة بعد تغيير الاتجاه
  setTimeout(() => {
    initSliders();
  }, 100);
}

/**
 * تهيئة القائمة المتنقلة للأجهزة المحمولة
 */
function initMobileMenu() {
  const menuButton = document.getElementById('mobileMenuButton');
  const mobileMenu = document.getElementById('mobileSidebar');
  const closeButton = document.querySelector('#mobileSidebar button[aria-label="إغلاق"]');
  const overlay = document.getElementById('mobileMenuOverlay');
  
  if (!menuButton || !mobileMenu || !overlay) return;
  
  // إضافة حدث النقر لزر القائمة
  menuButton.addEventListener('click', () => {
    overlay.classList.remove('hidden');
    mobileMenu.classList.remove('hidden');
    
    // إضافة تأثيرات الانتقال
    setTimeout(() => {
      overlay.classList.add('opacity-100');
      overlay.classList.remove('opacity-0');
      
      if (document.documentElement.dir === 'rtl') {
        mobileMenu.classList.remove('translate-x-[-100%]');
      } else {
        mobileMenu.classList.remove('translate-x-full');
      }
      
      mobileMenu.classList.add('translate-x-0');
      mobileMenu.classList.remove('opacity-0');
      mobileMenu.classList.add('opacity-100');
    }, 10);
  });
  
  // وظيفة إغلاق القائمة
  const closeMenu = () => {
    mobileMenu.classList.remove('translate-x-0', 'opacity-100');
    
    if (document.documentElement.dir === 'rtl') {
      mobileMenu.classList.add('translate-x-[-100%]');
    } else {
      mobileMenu.classList.add('translate-x-full');
    }
    
    mobileMenu.classList.add('opacity-0');
    overlay.classList.remove('opacity-100');
    overlay.classList.add('opacity-0');
    
    // إخفاء العناصر بعد انتهاء التأثير
    setTimeout(() => {
      overlay.classList.add('hidden');
      mobileMenu.classList.add('hidden');
    }, 300);
  };
  
  // إضافة حدث النقر لزر الإغلاق
  if (closeButton) {
    closeButton.addEventListener('click', closeMenu);
  }
  
  // إضافة حدث النقر للخلفية
  overlay.addEventListener('click', closeMenu);
  
  // تهيئة القوائم الفرعية في القائمة المتنقلة
  const dropdownButtons = document.querySelectorAll('#mobileSidebar button[data-dropdown]');
  
  dropdownButtons.forEach(button => {
    const dropdownId = button.dataset.dropdown;
    const dropdown = document.getElementById(dropdownId);
    
    if (!dropdown) return;
    
    button.addEventListener('click', () => {
      const isOpen = !dropdown.classList.contains('hidden');
      
      if (isOpen) {
        dropdown.classList.add('hidden');
        button.querySelector('svg').classList.remove('rotate-90');
      } else {
        dropdown.classList.remove('hidden');
        button.querySelector('svg').classList.add('rotate-90');
      }
    });
  });
}

/**
 * تهيئة القوائم المنسدلة
 */
function initDropdowns() {
  const dropdowns = document.querySelectorAll('[data-dropdown-button]');
  
  dropdowns.forEach(button => {
    const dropdownId = button.dataset.dropdownButton;
    const dropdown = document.getElementById(dropdownId);
    
    if (!dropdown) return;
    
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      const isOpen = !dropdown.classList.contains('hidden');
      
      // إغلاق جميع القوائم المنسدلة الأخرى
      document.querySelectorAll('[data-dropdown-menu]').forEach(menu => {
        if (menu.id !== dropdownId) {
          menu.classList.add('hidden');
        }
      });
      
      if (isOpen) {
        dropdown.classList.add('hidden');
      } else {
        dropdown.classList.remove('hidden');
      }
    });
  });
  
  // إغلاق القوائم المنسدلة عند النقر خارجها
  document.addEventListener('click', () => {
    document.querySelectorAll('[data-dropdown-menu]').forEach(menu => {
      menu.classList.add('hidden');
    });
  });
}

/**
 * تهيئة العروض المتحركة (بديل لـ Swiper.js)
 */
function initSliders() {
  initServiceSlider();
  initClientsSlider();
  initTestimonialsSlider();
}

/**
 * تهيئة عرض الخدمات المتحرك
 */
function initServiceSlider() {
  const slider = document.querySelector('[data-slider="services"]');
  if (!slider) return;
  
  const container = slider.querySelector('[data-slider-container="services"]');
  const slides = slider.querySelectorAll('[data-slide]');
  const pagination = slider.querySelector('[data-slider-pagination="services"]');
  const nextButton = slider.querySelector('[data-slider-next="services"]');
  const prevButton = slider.querySelector('[data-slider-prev="services"]');
  
  if (!container || slides.length === 0) return;
  
  let currentSlide = 0;
  let slideWidth = slides[0].offsetWidth;
  let slidesPerView = 1;
  let autoplayInterval;
  
  // تحديد عدد الشرائح المعروضة بناءً على عرض الشاشة
  const updateSlidesPerView = () => {
    if (window.innerWidth >= 1280) {
      slidesPerView = 4; // للشاشات الكبيرة جداً
    } else if (window.innerWidth >= 1024) {
      slidesPerView = 3; // للشاشات الكبيرة
    } else if (window.innerWidth >= 640) {
      slidesPerView = 2; // للشاشات المتوسطة
    } else {
      slidesPerView = 1; // للهواتف
    }
    
    slideWidth = container.offsetWidth / slidesPerView;
    
    // تحديث عرض الشرائح
    slides.forEach(slide => {
      slide.style.minWidth = `${slideWidth}px`;
    });
    
    // تحديث موضع العرض
    goToSlide(currentSlide);
  };
  
  // الانتقال إلى شريحة محددة
  const goToSlide = (index) => {
    if (index < 0) {
      index = slides.length - slidesPerView;
    } else if (index > slides.length - slidesPerView) {
      index = 0;
    }
    
    currentSlide = index;
    const translateValue = slideWidth * index;
    container.style.transform = `translateX(${document.documentElement.dir === 'rtl' ? translateValue : -translateValue}px)`;
    
    // تحديث حالة نقاط التنقل
    updatePagination();
  };
  
  // تحديث نقاط التنقل
  const updatePagination = () => {
    if (!pagination) return;
    
    // إزالة جميع النقاط الحالية
    pagination.innerHTML = '';
    
    // إنشاء نقاط التنقل الجديدة
    for (let i = 0; i <= slides.length - slidesPerView; i++) {
      const dot = document.createElement('button');
      dot.setAttribute('type', 'button');
      dot.setAttribute('aria-label', `الانتقال إلى الشريحة ${i + 1}`);
      dot.classList.add('w-3', 'h-3', 'rounded-full', 'mx-1', 'transition-colors');
      
      if (i === currentSlide) {
        dot.classList.add('bg-primary');
      } else {
        dot.classList.add('bg-gray-300');
      }
      
      dot.addEventListener('click', () => goToSlide(i));
      pagination.appendChild(dot);
    }
  };
  
  // إضافة أحداث النقر لأزرار التنقل
  if (prevButton) {
    prevButton.addEventListener('click', () => {
      goToSlide(currentSlide - 1);
      resetAutoplay();
    });
  }
  
  if (nextButton) {
    nextButton.addEventListener('click', () => {
      goToSlide(currentSlide + 1);
      resetAutoplay();
    });
  }
  
  // تمكين التمرير باللمس للأجهزة المحمولة
  let touchStartX = 0;
  let touchEndX = 0;
  
  container.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
    stopAutoplay();
  }, { passive: true });
  
  container.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    
    // التحقق من اتجاه التمرير
    if (touchStartX - touchEndX > 50) {
      // تمرير لليسار
      goToSlide(currentSlide + 1);
    } else if (touchEndX - touchStartX > 50) {
      // تمرير لليمين
      goToSlide(currentSlide - 1);
    }
    
    startAutoplay();
  }, { passive: true });
  
  // وظائف التشغيل التلقائي
  const startAutoplay = () => {
    autoplayInterval = setInterval(() => {
      goToSlide(currentSlide + 1);
    }, 3500);
  };
  
  const stopAutoplay = () => {
    clearInterval(autoplayInterval);
  };
  
  const resetAutoplay = () => {
    stopAutoplay();
    startAutoplay();
  };
  
  // التهيئة الأولية
  updateSlidesPerView();
  startAutoplay();
  
  // تحديث العرض عند تغيير حجم النافذة
  window.addEventListener('resize', () => {
    updateSlidesPerView();
    resetAutoplay();
  });
  
  // إيقاف التشغيل التلقائي عند تحويم المؤشر
  container.addEventListener('mouseenter', stopAutoplay);
  container.addEventListener('mouseleave', startAutoplay);
}

/**
 * تهيئة عرض العملاء المتحرك
 */
function initClientsSlider() {
  const slider = document.querySelector('#clients-swiper');
  if (!slider) return;
  
  const container = slider.querySelector('.swiper-wrapper');
  const slides = slider.querySelectorAll('.swiper-slide');
  const pagination = slider.querySelector('.swiper-pagination');
  
  if (!container || slides.length === 0) return;
  
  let currentSlide = 0;
  let slideWidth = slides[0].offsetWidth;
  let slidesPerView = 1;
  let autoplayInterval;
  
  // تحديد عدد الشرائح المعروضة بناءً على عرض الشاشة
  const updateSlidesPerView = () => {
    if (window.innerWidth >= 1280) {
      slidesPerView = 4; // للشاشات الكبيرة جداً
    } else if (window.innerWidth >= 1024) {
      slidesPerView = 3; // للشاشات الكبيرة
    } else if (window.innerWidth >= 640) {
      slidesPerView = 2; // للشاشات المتوسطة
    } else {
      slidesPerView = 1; // للهواتف
    }
    
    slideWidth = container.offsetWidth / slidesPerView;
    
    // تحديث عرض الشرائح
    slides.forEach(slide => {
      slide.style.minWidth = `${slideWidth}px`;
    });
    
    // تحديث موضع العرض
    goToSlide(currentSlide);
  };
  
  // الانتقال إلى شريحة محددة
  const goToSlide = (index) => {
    if (index < 0) {
      index = slides.length - slidesPerView;
    } else if (index > slides.length - slidesPerView) {
      index = 0;
    }
    
    currentSlide = index;
    const translateValue = slideWidth * index;
    container.style.transform = `translateX(${document.documentElement.dir === 'rtl' ? translateValue : -translateValue}px)`;
    
    // تحديث حالة نقاط التنقل
    updatePagination();
  };
  
  // تحديث نقاط التنقل
  const updatePagination = () => {
    if (!pagination) return;
    
    // إزالة جميع النقاط الحالية
    pagination.innerHTML = '';
    
    // إنشاء نقاط التنقل الجديدة
    for (let i = 0; i <= slides.length - slidesPerView; i++) {
      const dot = document.createElement('button');
      dot.setAttribute('type', 'button');
      dot.setAttribute('aria-label', `الانتقال إلى الشريحة ${i + 1}`);
      dot.classList.add('w-3', 'h-3', 'rounded-full', 'mx-1', 'transition-colors');
      
      if (i === currentSlide) {
        dot.classList.add('bg-primary');
      } else {
        dot.classList.add('bg-gray-300');
      }
      
      dot.addEventListener('click', () => goToSlide(i));
      pagination.appendChild(dot);
    }
  };
  
  // تمكين التمرير باللمس للأجهزة المحمولة
  let touchStartX = 0;
  let touchEndX = 0;
  
  container.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
    stopAutoplay();
  }, { passive: true });
  
  container.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    
    // التحقق من اتجاه التمرير
    if (touchStartX - touchEndX > 50) {
      // تمرير لليسار
      goToSlide(currentSlide + 1);
    } else if (touchEndX - touchStartX > 50) {
      // تمرير لليمين
      goToSlide(currentSlide - 1);
    }
    
    startAutoplay();
  }, { passive: true });
  
  // وظائف التشغيل التلقائي
  const startAutoplay = () => {
    autoplayInterval = setInterval(() => {
      goToSlide(currentSlide + 1);
    }, 2500);
  };
  
  const stopAutoplay = () => {
    clearInterval(autoplayInterval);
  };
  
  // التهيئة الأولية
  updateSlidesPerView();
  startAutoplay();
  
  // تحديث العرض عند تغيير حجم النافذة
  window.addEventListener('resize', updateSlidesPerView);
  
  // إيقاف التشغيل التلقائي عند تحويم المؤشر
  container.addEventListener('mouseenter', stopAutoplay);
  container.addEventListener('mouseleave', startAutoplay);
}

/**
 * تهيئة عرض الشهادات المتحرك
 */
function initTestimonialsSlider() {
  const slider = document.querySelector('.testimonials-swiper');
  if (!slider) return;
  
  const container = slider.querySelector('.swiper-wrapper');
  const slides = slider.querySelectorAll('.swiper-slide');
  const pagination = slider.querySelector('.swiper-pagination');
  
  if (!container || slides.length === 0) return;
  
  let currentSlide = 0;
  let slideWidth = slides[0].offsetWidth;
  let slidesPerView = 1;
  let autoplayInterval;
  
  // تحديد عدد الشرائح المعروضة بناءً على عرض الشاشة
  const updateSlidesPerView = () => {
    if (window.innerWidth >= 1024) {
      slidesPerView = 3; // للشاشات الكبيرة
    } else if (window.innerWidth >= 768) {
      slidesPerView = 2; // للشاشات المتوسطة
    } else {
      slidesPerView = 1; // للهواتف
    }
    
    slideWidth = container.offsetWidth / slidesPerView;
    
    // تحديث عرض الشرائح
    slides.forEach(slide => {
      slide.style.minWidth = `${slideWidth}px`;
    });
    
    // تحديث موضع العرض
    goToSlide(currentSlide);
  };
  
  // الانتقال إلى شريحة محددة
  const goToSlide = (index) => {
    if (index < 0) {
      index = slides.length - slidesPerView;
    } else if (index > slides.length - slidesPerView) {
      index = 0;
    }
    
    currentSlide = index;
    const translateValue = slideWidth * index;
    container.style.transform = `translateX(${document.documentElement.dir === 'rtl' ? translateValue : -translateValue}px)`;
    
    // تحديث حالة نقاط التنقل
    updatePagination();
  };
  
  // تحديث نقاط التنقل
  const updatePagination = () => {
    if (!pagination) return;
    
    // إزالة جميع النقاط الحالية
    pagination.innerHTML = '';
    
    // إنشاء نقاط التنقل الجديدة
    for (let i = 0; i <= slides.length - slidesPerView; i++) {
      const dot = document.createElement('button');
      dot.setAttribute('type', 'button');
      dot.setAttribute('aria-label', `الانتقال إلى الشريحة ${i + 1}`);
      dot.classList.add('w-3', 'h-3', 'rounded-full', 'mx-1', 'transition-colors');
      
      if (i === currentSlide) {
        dot.classList.add('bg-primary');
      } else {
        dot.classList.add('bg-gray-300');
      }
      
      dot.addEventListener('click', () => goToSlide(i));
      pagination.appendChild(dot);
    }
  };
  
  // تمكين التمرير باللمس للأجهزة المحمولة
  let touchStartX = 0;
  let touchEndX = 0;
  
  container.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
    stopAutoplay();
  }, { passive: true });
  
  container.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    
    // التحقق من اتجاه التمرير
    if (touchStartX - touchEndX > 50) {
      // تمرير لليسار
      goToSlide(currentSlide + 1);
    } else if (touchEndX - touchStartX > 50) {
      // تمرير لليمين
      goToSlide(currentSlide - 1);
    }
    
    startAutoplay();
  }, { passive: true });
  
  // وظائف التشغيل التلقائي
  const startAutoplay = () => {
    autoplayInterval = setInterval(() => {
      goToSlide(currentSlide + 1);
    }, 4000);
  };
  
  const stopAutoplay = () => {
    clearInterval(autoplayInterval);
  };
  
  // التهيئة الأولية
  updateSlidesPerView();
  startAutoplay();
  
  // تحديث العرض عند تغيير حجم النافذة
  window.addEventListener('resize', updateSlidesPerView);
  
  // إيقاف التشغيل التلقائي عند تحويم المؤشر
  container.addEventListener('mouseenter', stopAutoplay);
  container.addEventListener('mouseleave', startAutoplay);
}

/**
 * تهيئة وضع الظلام
 */
function initDarkMode() {
  const darkModeToggle = document.getElementById('darkModeToggle');
  
  if (!darkModeToggle) return;
  
  // التحقق من تفضيل المستخدم المحفوظ
  const isDarkMode = localStorage.getItem('darkMode') === 'true';
  
  // تطبيق وضع الظلام إذا كان مفعلاً
  if (isDarkMode) {
    document.documentElement.classList.add('dark');
    darkModeToggle.checked = true;
  } else {
    document.documentElement.classList.remove('dark');
    darkModeToggle.checked = false;
  }
  
  // إضافة حدث تغيير لزر التبديل
  darkModeToggle.addEventListener('change', () => {
    if (darkModeToggle.checked) {
      document.documentElement.classList.add('dark');
      localStorage.setItem('darkMode', 'true');
    } else {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('darkMode', 'false');
    }
  });
}

/**
 * تهيئة التمرير باللمس للقائمة الجانبية
 */
function initTouchSwipe() {
  let touchStartX = 0;
  let touchEndX = 0;
  const threshold = 80;
  const isRTL = document.documentElement.dir === 'rtl';
  const edgeZone = 20;
  const mobileMenuButton = document.getElementById('mobileMenuButton');

  document.body.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].clientX;
  }, { passive: true });

  document.body.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].clientX;
    const swipeDistance = touchEndX - touchStartX;

    if (isRTL) {
      if (touchStartX >= window.innerWidth - edgeZone && swipeDistance < -threshold) {
        // فتح القائمة الجانبية عند التمرير من اليسار إلى اليمين في وضع RTL
        if (mobileMenuButton) mobileMenuButton.click();
      }
    } else {
      if (touchStartX <= edgeZone && swipeDistance > threshold) {
        // فتح القائمة الجانبية عند التمرير من اليمين إلى اليسار في وضع LTR
        if (mobileMenuButton) mobileMenuButton.click();
      }
    }
  }, { passive: true });
  
  // إظهار رسالة توضيحية عند فتح القائمة الجانبية لأول مرة
  const sidebarToggleToast = document.getElementById('swipeToast');
  if (sidebarToggleToast) {
    const mobileMenu = document.getElementById('mobileSidebar');
    
    if (mobileMenu) {
      const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
          if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
            if (!mobileMenu.classList.contains('hidden') && !sessionStorage.getItem('swipeTipShown')) {
              sidebarToggleToast.classList.remove('hidden');
              sidebarToggleToast.classList.add('opacity-100');
              sessionStorage.setItem('swipeTipShown', 'true');

              setTimeout(() => {
                sidebarToggleToast.classList.add('opacity-0');
                setTimeout(() => sidebarToggleToast.classList.add('hidden'), 300);
              }, 4000);
            }
          }
        });
      });

      observer.observe(mobileMenu, { attributes: true });
    }
  }
}
