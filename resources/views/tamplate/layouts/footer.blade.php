@php
    $generalSettings = \App\Models\GeneralSetting::first();
    $site_title = $generalSettings->site_title;
    $site_discretion = $generalSettings->site_discretion;
@endphp
<!-- Contact Section -->
<section class="py-20 px-4 sm:px-8 lg:px-24 bg-background -mb-25 -z-30 relative" aria-labelledby="support-heading"
         itemscope itemtype="https://schema.org/ContactPoint" itemprop="contactType" content="customer support">
  <div class="max-w-3xl mx-auto text-center">
    <h2 id="support-heading" class="text-title-h2 font-extrabold text-primary mb-4">
      نحن هنا لمساعدتك 24/7
    </h2>
    <p class="text-tertiary text-suptitle font-light mb-8">
      تواصل معنا بسهولة على مدار الساعة. فريق <strong itemprop="name">Palgoals</strong> للدعم الفني متواجد دائمًا للإجابة على استفساراتك وتقديم المساعدة عبر الهاتف أو البريد الإلكتروني أو نموذج الاتصال.
    </p>
    <div class="flex flex-col sm:flex-row justify-center gap-x-6 gap-y-4">
      <a href="tel:+970599123456" itemprop="telephone"
         class="px-8 py-3 rounded-lg font-bold flex items-center justify-center gap-2 bg-primary text-white transition-colors duration-200 hover:bg-secondary sm:shadow">
        <svg class="w-5 h-5" aria-hidden="true" ...></svg>
        اتصل بنا
      </a>
      <a href="mailto:info@palgoals.com" itemprop="email"
         class="px-8 py-3 rounded-lg font-bold flex items-center justify-center gap-2 bg-secondary text-white transition-colors duration-200 hover:bg-primary sm:shadow">
        <svg class="w-5 h-5" aria-hidden="true" ...></svg>
        راسلنا عبر البريد
      </a>
      <a href="#contact" aria-label="نموذج الاتصال بنا"
         class="px-8 py-3 rounded-lg font-bold flex items-center justify-center gap-2 bg-white text-primary border border-primary/20 transition-colors duration-200 hover:bg-primary hover:text-white sm:shadow">
        <svg class="w-5 h-5" aria-hidden="true" ...></svg>
        تواصل معنا
      </a>
    </div>
  </div>
</section>
<!-- Footer Section -->
<footer class="bg-gradient-to-tr from-primary/90 to-primary/95 text-white pt-12 pb-6 px-4 sm:px-8 lg:px-24 mt-16 rounded-t-3xl shadow-2xl"
    itemscope itemtype="https://schema.org/Organization">
    <meta itemprop="name" content="Palgoals" />
    <meta itemprop="url" content="https://palgoals.com" />
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between gap-10">
        <!-- Logo & About -->
        <div class="flex-1 mb-8 md:mb-0 text-center md:text-right">
            <a href="/" class="flex items-center justify-center md:justify-start gap-3 mb-4 group">
                <img src="{{ asset('assets/tamplate/images/logo.svg') }}" alt="Palgoals Logo" class="h-12 w-auto transition-transform group-hover:scale-105" itemprop="logo" loading="lazy" />
                <span class="font-extrabold text-xl tracking-tight group-hover:text-[#AE1028] transition hidden sm:inline">
                    {{ t('General.'.$site_title, 'palgoals') }}
                </span>
            </a>
            <p class="text-white/80 text-sm max-w-xs mx-auto md:mx-0 leading-relaxed">
                {{ t('General.'.$site_discretion, 'discretion') }}
            </p>
            <div class="flex gap-3 mt-5" aria-label="روابط التواصل الاجتماعي">
                <!-- Facebook -->
                <a href="https://www.facebook.com/palgoals" target="_blank" rel="noopener" aria-label="Facebook" class="hover:text-[#AE1028] transition">
                    <svg class="w-8 h-8 bg-white rounded-full hover:bg-secondary/30" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 92 92" fill="none">
                        <rect x="0.138672" width="91.5618" height="91.5618" rx="15" fill="#EDF4FF"/>
                        <path d="M56.4927 48.6403L57.7973 40.3588H49.7611V34.9759C49.7611 32.7114 50.883 30.4987 54.4706 30.4987H58.1756V23.4465C56.018 23.1028 53.8378 22.9168 51.6527 22.8901C45.0385 22.8901 40.7204 26.8626 40.7204 34.0442V40.3588H33.3887V48.6403H40.7204V68.671H49.7611V48.6403H56.4927Z" fill="#337FFF"/>
                    </svg>
                </a>
                <!-- Twitter X -->
                <a href="https://x.com/palgoals" target="_blank" rel="noopener" aria-label="Twitter X" class="hover:text-[#AE1028] transition">
                    <svg class="w-8 h-8 bg-white rounded-full hover:bg-secondary/30"  xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 93 92" fill="none">
                        <rect x="0.138672" width="91.5618" height="91.5618" rx="15" fill="#F7F7F7"/>
                        <path d="M50.7568 42.1716L69.3704 21H64.9596L48.7974 39.383L35.8887 21H21L40.5205 48.7983L21 71H25.4111L42.4788 51.5869L56.1113 71H71L50.7557 42.1716H50.7568ZM44.7152 49.0433L42.7374 46.2752L27.0005 24.2492H33.7756L46.4755 42.0249L48.4533 44.7929L64.9617 67.8986H58.1865L44.7152 49.0443V49.0433Z" fill="black"/>
                    </svg>
                </a>
                <!-- LinkedIn -->
                <a href="https://www.linkedin.com/company/palgoals" target="_blank" rel="noopener" aria-label="LinkedIn" class="hover:text-[#AE1028] transition">
                    <svg class="w-8 h-8 bg-white rounded-full hover:bg-secondary/30" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 92 93" fill="none">
                        <rect x="0.138672" width="91.5618" height="91.5618" rx="15" fill="#EEFAFF"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M24.6975 21.5618C22.6561 21.5618 21 23.1674 21 25.1456V68.0091C21 69.9875 22.6563 71.5918 24.6975 71.5918H67.3325C69.3747 71.5918 71.03 69.9873 71.03 68.0086V25.1456C71.03 23.1674 69.3747 21.5618 67.3325 21.5618H24.6975ZM36.2032 40.9068V63.4304H28.7167V40.9068H36.2032ZM36.6967 33.9411C36.6967 36.1025 35.0717 37.8321 32.4615 37.8321L32.4609 37.8319H32.4124C29.8998 37.8319 28.2754 36.1023 28.2754 33.9409C28.2754 31.7304 29.9489 30.0491 32.5111 30.0491C35.0717 30.0491 36.6478 31.7304 36.6967 33.9411ZM47.833 63.4304H40.3471L40.3469 63.4312C40.3469 63.4312 40.4452 43.0205 40.3475 40.9075H47.8336V44.0957C48.8288 42.5613 50.6098 40.3787 54.5808 40.3787C59.5062 40.3787 63.1991 43.598 63.1991 50.516V63.4304H55.7133V51.3822C55.7133 48.354 54.6293 46.2887 51.921 46.2887C49.8524 46.2887 48.6206 47.6815 48.0796 49.0271C47.8819 49.5072 47.833 50.1813 47.833 50.8535V63.4304Z" fill="#006699"/>
                    </svg>
                </a>
                <!-- Instagram -->
                <a href="https://www.instagram.com/palgoals" target="_blank" rel="noopener" aria-label="Instagram" class="hover:text-[#AE1028] transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 bg-white rounded-full hover:bg-secondary/30" width="28" height="28" viewBox="0 0 51 51" fill="none">
                        <path d="M17.4456 25.7808C17.4456 21.1786 21.1776 17.4468 25.7826 17.4468C30.3875 17.4468 34.1216 21.1786 34.1216 25.7808C34.1216 30.383 30.3875 34.1148 25.7826 34.1148C21.1776 34.1148 17.4456 30.383 17.4456 25.7808ZM12.9377 25.7808C12.9377 32.8708 18.6883 38.618 25.7826 38.618C32.8768 38.618 38.6275 32.8708 38.6275 25.7808C38.6275 18.6908 32.8768 12.9436 25.7826 12.9436C18.6883 12.9436 12.9377 18.6908 12.9377 25.7808ZM36.1342 12.4346C36.1339 13.0279 36.3098 13.608 36.6394 14.1015C36.9691 14.595 37.4377 14.9797 37.9861 15.2069C38.5346 15.4342 39.1381 15.4939 39.7204 15.3784C40.3028 15.2628 40.8378 14.9773 41.2577 14.5579C41.6777 14.1385 41.9638 13.6041 42.0799 13.0222C42.1959 12.4403 42.1367 11.8371 41.9097 11.2888C41.6828 10.7406 41.2982 10.2719 40.8047 9.94202C40.3112 9.61218 39.7309 9.436 39.1372 9.43576H39.136C38.3402 9.43613 37.5771 9.75216 37.0142 10.3144C36.4514 10.8767 36.1349 11.6392 36.1342 12.4346ZM15.6765 46.1302C13.2377 46.0192 11.9121 45.6132 11.0311 45.2702C9.86323 44.8158 9.02993 44.2746 8.15381 43.4002C7.27768 42.5258 6.73536 41.6938 6.28269 40.5266C5.93928 39.6466 5.53304 38.3214 5.42217 35.884C5.3009 33.2488 5.27668 32.4572 5.27668 25.781C5.27668 19.1048 5.3029 18.3154 5.42217 15.678C5.53324 13.2406 5.94248 11.918 6.28269 11.0354C6.73736 9.86816 7.27888 9.03536 8.15381 8.15976C9.02873 7.28416 9.86123 6.74216 11.0311 6.28976C11.9117 5.94656 13.2377 5.54056 15.6765 5.42976C18.3133 5.30856 19.1054 5.28436 25.7826 5.28436C32.4598 5.28436 33.2527 5.31056 35.8916 5.42976C38.3305 5.54076 39.6539 5.94976 40.537 6.28976C41.7049 6.74216 42.5382 7.28536 43.4144 8.15976C44.2905 9.03416 44.8308 9.86816 45.2855 11.0354C45.6289 11.9154 46.0351 13.2406 46.146 15.678C46.2673 18.3154 46.2915 19.1048 46.2915 25.781C46.2915 32.4572 46.2673 33.2466 46.146 35.884C46.0349 38.3214 45.6267 39.6462 45.2855 40.5266C44.8308 41.6938 44.2893 42.5266 43.4144 43.4002C42.5394 44.2738 41.7049 44.8158 40.537 45.2702C39.6565 45.6134 38.3305 46.0194 35.8916 46.1302C33.2549 46.2514 32.4628 46.2756 25.7826 46.2756C19.1024 46.2756 18.3125 46.2514 15.6765 46.1302ZM15.4694 0.932162C12.8064 1.05336 10.9867 1.47536 9.39755 2.09336C7.75177 2.73156 6.35853 3.58776 4.9663 4.97696C3.57406 6.36616 2.71955 7.76076 2.08097 9.40556C1.46259 10.9948 1.04034 12.8124 0.919069 15.4738C0.795795 18.1394 0.767578 18.9916 0.767578 25.7808C0.767578 32.57 0.795795 33.4222 0.919069 36.0878C1.04034 38.7494 1.46259 40.5668 2.08097 42.156C2.71955 43.7998 3.57426 45.196 4.9663 46.5846C6.35833 47.9732 7.75177 48.8282 9.39755 49.4682C10.9897 50.0862 12.8064 50.5082 15.4694 50.6294C18.138 50.7506 18.9893 50.7808 25.7826 50.7808C32.5759 50.7808 33.4286 50.7526 36.0958 50.6294C38.759 50.5082 40.5774 50.0862 42.1676 49.4682C43.8124 48.8282 45.2066 47.9738 46.5989 46.5846C47.9911 45.1954 48.8438 43.7998 49.4842 42.156C50.1026 40.5668 50.5268 38.7492 50.6461 36.0878C50.7674 33.4202 50.7956 32.57 50.7956 25.7808C50.7956 18.9916 50.7674 18.1394 50.6461 15.4738C50.5248 12.8122 50.1026 10.9938 49.4842 9.40556C48.8438 7.76176 47.9889 6.36836 46.5989 4.97696C45.2088 3.58556 43.8124 2.73156 42.1696 2.09336C40.5775 1.47536 38.7588 1.05136 36.0978 0.932162C33.4306 0.810962 32.5779 0.780762 25.7846 0.780762C18.9913 0.780762 18.138 0.808962 15.4694 0.932162Z" fill="url(#paint0_radial_7092_54379)"/>
                        <path d="M17.4456 25.7808C17.4456 21.1786 21.1776 17.4468 25.7826 17.4468C30.3875 17.4468 34.1216 21.1786 34.1216 25.7808C34.1216 30.383 30.3875 34.1148 25.7826 34.1148C21.1776 34.1148 17.4456 30.383 17.4456 25.7808ZM12.9377 25.7808C12.9377 32.8708 18.6883 38.618 25.7826 38.618C32.8768 38.618 38.6275 32.8708 38.6275 25.7808C38.6275 18.6908 32.8768 12.9436 25.7826 12.9436C18.6883 12.9436 12.9377 18.6908 12.9377 25.7808ZM36.1342 12.4346C36.1339 13.0279 36.3098 13.608 36.6394 14.1015C36.9691 14.595 37.4377 14.9797 37.9861 15.2069C38.5346 15.4342 39.1381 15.4939 39.7204 15.3784C40.3028 15.2628 40.8378 14.9773 41.2577 14.5579C41.6777 14.1385 41.9638 13.6041 42.0799 13.0222C42.1959 12.4403 42.1367 11.8371 41.9097 11.2888C41.6828 10.7406 41.2982 10.2719 40.8047 9.94202C40.3112 9.61218 39.7309 9.436 39.1372 9.43576H39.136C38.3402 9.43613 37.5771 9.75216 37.0142 10.3144C36.4514 10.8767 36.1349 11.6392 36.1342 12.4346ZM15.6765 46.1302C13.2377 46.0192 11.9121 45.6132 11.0311 45.2702C9.86323 44.8158 9.02993 44.2746 8.15381 43.4002C7.27768 42.5258 6.73536 41.6938 6.28269 40.5266C5.93928 39.6466 5.53304 38.3214 5.42217 35.884C5.3009 33.2488 5.27668 32.4572 5.27668 25.781C5.27668 19.1048 5.3029 18.3154 5.42217 15.678C5.53324 13.2406 5.94248 11.918 6.28269 11.0354C6.73736 9.86816 7.27888 9.03536 8.15381 8.15976C9.02873 7.28416 9.86123 6.74216 11.0311 6.28976C11.9117 5.94656 13.2377 5.54056 15.6765 5.42976C18.3133 5.30856 19.1054 5.28436 25.7826 5.28436C32.4598 5.28436 33.2527 5.31056 35.8916 5.42976C38.3305 5.54076 39.6539 5.94976 40.537 6.28976C41.7049 6.74216 42.5382 7.28536 43.4144 8.15976C44.2905 9.03416 44.8308 9.86816 45.2855 11.0354C45.6289 11.9154 46.0351 13.2406 46.146 15.678C46.2673 18.3154 46.2915 19.1048 46.2915 25.781C46.2915 32.4572 46.2673 33.2466 46.146 35.884C46.0349 38.3214 45.6267 39.6462 45.2855 40.5266C44.8308 41.6938 44.2893 42.5266 43.4144 43.4002C42.5394 44.2738 41.7049 44.8158 40.537 45.2702C39.6565 45.6134 38.3305 46.0194 35.8916 46.1302C33.2549 46.2514 32.4628 46.2756 25.7826 46.2756C19.1024 46.2756 18.3125 46.2514 15.6765 46.1302ZM15.4694 0.932162C12.8064 1.05336 10.9867 1.47536 9.39755 2.09336C7.75177 2.73156 6.35853 3.58776 4.9663 4.97696C3.57406 6.36616 2.71955 7.76076 2.08097 9.40556C1.46259 10.9948 1.04034 12.8124 0.919069 15.4738C0.795795 18.1394 0.767578 18.9916 0.767578 25.7808C0.767578 32.57 0.795795 33.4222 0.919069 36.0878C1.04034 38.7494 1.46259 40.5668 2.08097 42.156C2.71955 43.7998 3.57426 45.196 4.9663 46.5846C6.35833 47.9732 7.75177 48.8282 9.39755 49.4682C10.9897 50.0862 12.8064 50.5082 15.4694 50.6294C18.138 50.7506 18.9893 50.7808 25.7826 50.7808C32.5759 50.7808 33.4286 50.7526 36.0958 50.6294C38.759 50.5082 40.5774 50.0862 42.1676 49.4682C43.8124 48.8282 45.2066 47.9738 46.5989 46.5846C47.9911 45.1954 48.8438 43.7998 49.4842 42.156C50.1026 40.5668 50.5268 38.7492 50.6461 36.0878C50.7674 33.4202 50.7956 32.57 50.7956 25.7808C50.7956 18.9916 50.7674 18.1394 50.6461 15.4738C50.5248 12.8122 50.1026 10.9938 49.4842 9.40556C48.8438 7.76176 47.9889 6.36836 46.5989 4.97696C45.2088 3.58556 43.8124 2.73156 42.1696 2.09336C40.5775 1.47536 38.7588 1.05136 36.0978 0.932162C33.4306 0.810962 32.5779 0.780762 25.7846 0.780762C18.9913 0.780762 18.138 0.808962 15.4694 0.932162Z" fill="url(#paint1_radial_7092_54379)"/>
                        <defs>
                            <radialGradient id="paint0_radial_7092_54379" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(7.41436 51.017) scale(65.31 65.2708)">
                                <stop offset="0.09" stop-color="#FA8F21"/>
                                <stop offset="0.78" stop-color="#D82D7E"/>
                            </radialGradient>
                            <radialGradient id="paint1_radial_7092_54379" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(31.1086 53.257) scale(51.4733 51.4424)">
                                <stop offset="0.64" stop-color="#8C3AAA" stop-opacity="0"/>
                                <stop offset="1" stop-color="#8C3AAA"/>
                            </radialGradient>
                        </defs>
                    </svg>
                </a>
                <!-- whatsapp -->
                <a href="https://api.whatsapp.com/send?phone=970598663901" target="_blank" rel="noopener" aria-label="whatsapp" class="hover:text-[#AE1028] transition">
                    <svg class="w-8 h-8 bg-white rounded-full hover:bg-secondary/30" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 93 92" fill="none">
                        <rect x="1.13867" width="91.5618" height="91.5618" rx="15" fill="#ECFFF5"/>
                        <path d="M23.5762 66.8405L26.8608 54.6381C24.2118 49.8847 23.3702 44.3378 24.4904 39.0154C25.6106 33.693 28.6176 28.952 32.9594 25.6624C37.3012 22.3729 42.6867 20.7554 48.1276 21.1068C53.5685 21.4582 58.6999 23.755 62.5802 27.5756C66.4604 31.3962 68.8292 36.4844 69.2519 41.9065C69.6746 47.3286 68.1228 52.7208 64.8813 57.0938C61.6399 61.4668 56.9261 64.5271 51.605 65.7133C46.284 66.8994 40.7125 66.1318 35.9131 63.5513L23.5762 66.8405ZM36.508 58.985L37.2709 59.4365C40.7473 61.4918 44.8076 62.3423 48.8191 61.8555C52.8306 61.3687 56.5681 59.5719 59.4489 56.7452C62.3298 53.9185 64.1923 50.2206 64.7463 46.2279C65.3002 42.2351 64.5143 38.1717 62.5113 34.6709C60.5082 31.1701 57.4003 28.4285 53.6721 26.8734C49.9438 25.3184 45.8045 25.0372 41.8993 26.0736C37.994 27.11 34.5422 29.4059 32.0817 32.6035C29.6212 35.801 28.2903 39.7206 28.2963 43.7514C28.293 47.0937 29.2197 50.3712 30.9732 53.2192L31.4516 54.0061L29.6153 60.8167L36.508 58.985Z" fill="#00D95F"/>
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M55.0259 46.8847C54.5787 46.5249 54.0549 46.2716 53.4947 46.1442C52.9344 46.0168 52.3524 46.0186 51.793 46.1495C50.9524 46.4977 50.4093 47.8134 49.8661 48.4713C49.7516 48.629 49.5833 48.7396 49.3928 48.7823C49.2024 48.8251 49.0028 48.797 48.8316 48.7034C45.7543 47.5012 43.1748 45.2965 41.5122 42.4475C41.3704 42.2697 41.3033 42.044 41.325 41.8178C41.3467 41.5916 41.4555 41.3827 41.6286 41.235C42.2344 40.6368 42.6791 39.8959 42.9218 39.0809C42.9756 38.1818 42.7691 37.2863 42.3269 36.5011C41.985 35.4002 41.3344 34.42 40.4518 33.6762C39.9966 33.472 39.4919 33.4036 38.9985 33.4791C38.5052 33.5546 38.0443 33.7709 37.6715 34.1019C37.0242 34.6589 36.5104 35.3537 36.168 36.135C35.8256 36.9163 35.6632 37.7643 35.6929 38.6165C35.6949 39.0951 35.7557 39.5716 35.8739 40.0354C36.1742 41.1497 36.636 42.2144 37.2447 43.1956C37.6839 43.9473 38.163 44.6749 38.6801 45.3755C40.3607 47.6767 42.4732 49.6305 44.9003 51.1284C46.1183 51.8897 47.42 52.5086 48.7799 52.973C50.1924 53.6117 51.752 53.8568 53.2931 53.6824C54.1711 53.5499 55.003 53.2041 55.7156 52.6755C56.4281 52.1469 56.9995 51.4518 57.3795 50.6512C57.6028 50.1675 57.6705 49.6269 57.5735 49.1033C57.3407 48.0327 55.9053 47.4007 55.0259 46.8847Z" fill="#00D95F"/>
                    </svg>
                </a>
            </div>
        </div>
        <!-- Links -->
        <div class="flex-1 grid grid-cols-2 gap-8 text-center md:text-right">
            <div>
                <h4 class="font-extrabold text-lg mb-4 text-white">روابط سريعة</h4>
                <ul class="space-y-2 text-white/90 text-sm">
                    <li><a href="#" class="hover:text-[#AE1028] transition">الرئيسية</a></li>
                    <li><a href="#" class="hover:text-[#AE1028] transition">استضافة</a></li>
                    <li><a href="#" class="hover:text-[#AE1028] transition">دومين</a></li>
                    <li><a href="#" class="hover:text-[#AE1028] transition">قوالب</a></li>
                    <li><a href="#" class="hover:text-[#AE1028] transition">أعمالنا</a></li>
                    <li><a href="#" class="hover:text-[#AE1028] transition">مدونة</a></li>
                    <li><a href="#" class="hover:text-[#AE1028] transition">تواصل</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-extrabold text-lg mb-4 text-white">خدماتنا</h4>
                <ul class="space-y-2 text-white/90 text-sm">
                    <li><a href="#" class="hover:text-[#AE1028] transition">تصميم مواقع</a></li>
                    <li><a href="#" class="hover:text-[#AE1028] transition">استضافة وردبريس</a></li>
                    <li><a href="#" class="hover:text-[#AE1028] transition">استضافة مشتركة</a></li>
                    <li><a href="#" class="hover:text-[#AE1028] transition">تحسين محركات البحث</a></li>
                </ul>
            </div>
        </div>
        <!-- Contact Info -->
        <div class="flex-1 text-center md:text-right">
            <h4 class="font-extrabold text-lg mb-4 text-white">تواصل معنا</h4>
            <ul class="space-y-3 text-white/90 text-sm">
                <li class="flex items-center justify-center md:justify-start gap-2">
                    <!-- Phone Icon -->
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h1.2a1 1 0 01.9.6l1.2 2.4a1 1 0 01-.2 1.1L7.5 9a16 16 0 006.5 6.5l1.5-1.5a1 1 0 011.1-.2l2.4 1.2a1 1 0 01.6.9V19a2 2 0 01-2 2h-1C9.4 21 3 14.6 3 7V6a2 2 0 012-1z" />
                    </svg>
                    <a href="tel:+970599123456" class="hover:text-[#AE1028] transition font-semibold" itemprop="telephone">+970 599 123 456</a>
                </li>
                <li class="flex items-center justify-center md:justify-start gap-2">
                    <!-- Mail Icon -->
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16c1.1 0 2 .9 2 2v12a2 2 0 01-2 2H4c-1.1 0-2-.9-2-2V6a2 2 0 012-2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22 6l-10 7L2 6" />
                    </svg>
                    <a href="mailto:info@palgoals.com" class="hover:text-[#AE1028] transition font-semibold" itemprop="email">info@palgoals.com</a>
                </li>
                <li class="flex items-center justify-center md:justify-start gap-2">
                    <!-- Map Pin Icon -->
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 11a4 4 0 100-8 4 4 0 000 8z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14c-4.4 0-8 1.8-8 4v2h16v-2c0-2.2-3.6-4-8-4z" />
                    </svg>
                    <span itemprop="address">فلسطين - غزة</span>
                </li>
            </ul>
        </div>
    </div>
    <!-- وسائل الدفع -->
    <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 border-t border-white/10 pt-6" itemprop="paymentAccepted">
        <span class="text-white/80 text-sm font-semibold">نقبل وسائل الدفع:</span>
        <div class="flex items-center gap-4">
            <img src="{{ asset('assets/tamplate/images/visa.svg') }}" alt="بطاقة Visa" class="h-6 w-auto sm:h-8 opacity-90 hover:opacity-100 transition-opacity duration-200" loading="lazy" itemprop="image">
            <img src="{{ asset('assets/tamplate/images/mastercard.svg') }}" alt="بطاقة MasterCard" class="h-6 w-auto sm:h-8 opacity-90 hover:opacity-100 transition-opacity duration-200" loading="lazy" itemprop="image">
        </div>
    </div>
    <div class="border-t border-white/20 mt-10 pt-6 text-center text-xs text-white/70">
        جميع الحقوق محفوظة &copy; <span id="footer-year"></span> بال قول | Palgoals
    </div>
</footer>
