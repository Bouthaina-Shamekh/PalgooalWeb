<!-- Alpine.js — must load before deferred scripts that define Alpine components -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<!-- Local scripts — deferred so they never block rendering -->
<script src="{{ asset('assets/tamplate/js/header.js') }}" defer></script>
<script src="{{ asset('assets/tamplate/js/custoum-script.js') }}" defer></script>
<script src="{{ asset('assets/tamplate/js/slider.js') }}" defer></script>
<!-- lang.js was blocking — now deferred -->
<script src="{{ asset('assets/tamplate/js/lang.js') }}" defer></script>

<!-- Swiper JS — deferred (used only in slider sections) -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>

<!-- AOS JS — deferred; CSS is now in <head> via head.blade.php -->
<script src="https://cdn.jsdelivr.net/npm/aos@next/dist/aos.js" defer></script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    if (typeof AOS !== "undefined") {
      AOS.init({
        duration: 800,
        once: true,
        easing: "ease-out",
      });
    }
  });
</script>

@stack('scripts')

</body>
</html>
