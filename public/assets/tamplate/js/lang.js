  function languageSwitcher() {
    return {
      currentLang: localStorage.getItem('lang') || 'ar',
      menuOpen: false,
      get currentLabel() {
        return this.currentLang === 'ar' ? 'العربية' : 'English';
      },
      toggleMenu() {
        this.menuOpen = !this.menuOpen;
      },
      switchLanguage(lang) {
        this.currentLang = lang;
        localStorage.setItem('lang', lang);
        document.documentElement.lang = lang;
        document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';

        // أعد تهيئة Swiper بعد تغيير الاتجاه
        setTimeout(() => {
          initTestimonialsSwiper();
        }, 100);
      },
      toggleDirection() {
        const currentDir = document.documentElement.dir;
        document.documentElement.dir = currentDir === 'rtl' ? 'ltr' : 'rtl';
        setTimeout(() => {
          initTestimonialsSwiper();
        }, 100);
      }
    }
  }

