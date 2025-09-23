document.addEventListener('DOMContentLoaded', function () {
  const selector = '#clients-swiper';
  const el = document.querySelector(selector);
  if (!el) return;

  const options = {
    slidesPerView: 1,
    spaceBetween: 24,
    loop: true,
    autoplay: {
      delay: 2500,
      disableOnInteraction: false,
    },
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
    },
    breakpoints: {
      640: { slidesPerView: 2 },
      1024: { slidesPerView: 3 },
      1280: { slidesPerView: 4 },
    },
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    rtl: document.documentElement.getAttribute('dir') === 'rtl',
    watchOverflow: true,
  };

  const slidesCount = el.querySelectorAll('.swiper-slide').length;
  const maxPerView = Math.max(
    options.slidesPerView || 1,
    ...Object.values(options.breakpoints || {}).map(b => (b && b.slidesPerView) ? b.slidesPerView : 1)
  );
  options.loop = slidesCount > maxPerView;

  new Swiper(selector, options);
});


// Testimonials Swiper
    document.addEventListener('DOMContentLoaded', function () {
        const selector = '.testimonials-swiper';
        const el = document.querySelector(selector);
        if (!el) return;

        const options = {
            slidesPerView: 1,
            spaceBetween: 24,
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.testimonials-swiper .swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                768: { slidesPerView: 2 },
                1024: { slidesPerView: 3 }
            },
            watchOverflow: true,
        };

        const slidesCount = el.querySelectorAll('.swiper-slide').length;
        const maxPerView = Math.max(
            options.slidesPerView || 1,
            ...Object.values(options.breakpoints || {}).map(b => (b && b.slidesPerView) ? b.slidesPerView : 1)
        );
        options.loop = slidesCount > maxPerView;

        new Swiper(selector, options);
    });
// Blog Swiper
    document.addEventListener('DOMContentLoaded', function () {
        const selector = '.blog-swiper';
        const el = document.querySelector(selector);
        if (!el) return;

        const options = {
            slidesPerView: 1,
            spaceBetween: 24,
            loop: true,
            autoplay: {
                delay: 3500,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.blog-swiper .swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                640: { slidesPerView: 2 },
                1024: { slidesPerView: 3 },
                1280: { slidesPerView: 4 }
            },
            watchOverflow: true,
        };

        const slidesCount = el.querySelectorAll('.swiper-slide').length;
        const maxPerView = Math.max(
            options.slidesPerView || 1,
            ...Object.values(options.breakpoints || {}).map(b => (b && b.slidesPerView) ? b.slidesPerView : 1)
        );
        options.loop = slidesCount > maxPerView;

        new Swiper(selector, options);
    });

    // خدمات رقمية متكاملة 
    document.addEventListener('DOMContentLoaded', function () {
        const selector = '.mySwiper';
        const el = document.querySelector(selector);
        if (!el) return;

        const options = {
            slidesPerView: 1,
            spaceBetween: 24,
            loop: true,
            autoplay: {
                delay: 3500,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                640: { slidesPerView: 2 },
                1024: { slidesPerView: 3 },
                1280: { slidesPerView: 4 }
            },
            watchOverflow: true,
        };

        const slidesCount = el.querySelectorAll('.swiper-slide').length;
        const maxPerView = Math.max(
            options.slidesPerView || 1,
            ...Object.values(options.breakpoints || {}).map(b => (b && b.slidesPerView) ? b.slidesPerView : 1)
        );
        options.loop = slidesCount > maxPerView;

        new Swiper(selector, options);
    });

