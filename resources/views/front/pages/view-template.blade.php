<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ current_dir() }}" class="h-full overflow-hidden">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة: {{ $translation->name }}</title>

    <!-- Tailwind CSS via CDN / compiled asset -->
    <link rel="stylesheet" href="{{ asset('assets/tamplate/css/app.css') }}">

    <!-- Alpine.js for UI interactivity -->
    <script src="//unpkg.com/alpinejs" defer></script>

    <!-- Custom CSS for minor adjustments -->
    @php
        // نفس منطق السعر الموجود في template-show.blade
        $basePrice = (float) ($template->price ?? 0);
        $discRaw = $template->discount_price;
        $discPrice = is_null($discRaw) ? null : (float) $discRaw;
        $hasDiscount = !is_null($discPrice) && $discPrice > 0 && $discPrice < $basePrice;
        $finalPrice = $hasDiscount ? $discPrice : $basePrice;
        $finalPriceCents = (int) round($finalPrice * 100);
    @endphp

    <style>
        body {
            margin: 0;
        }

        iframe {
            border: none;
        }

        .device-switcher button.active {
            background-color: white;
            color: #111827;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1),
                0 1px 2px -1px rgb(0 0 0 / 0.1);
        }

        .dark .device-switcher button.active {
            background-color: #1f2937;
            /* gray-800 */
            color: #f9fafb;
            /* gray-50 */
        }
    </style>
</head>

<body class="h-full bg-gray-200 dark:bg-gray-700 grid grid-rows-[auto_1fr] font-Cairo" x-data="{ device: 'desktop' }">

    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50 h-16 flex items-center flex-shrink-0">
        <div class="w-full px-4 flex justify-between items-center">

            <!-- Right Side: Logo & Template Name -->
            <div class="flex items-center gap-4">
                <a href="{{ route('template.show', $translation->slug) }}" title="العودة لصفحة المنتج">
                    <img src="{{ asset('assets/tamplate/images/logo.svg') }}" alt="شركة بال قول " class="h-8 w-auto">
                </a>
                <div class="hidden md:block border-r border-gray-200 dark:border-gray-600 h-8 mx-2"></div>
                <h1 class="hidden md:block text-lg font-bold text-gray-800 dark:text-white">
                    {{ $translation->name }}
                </h1>
            </div>

            <!-- Center: Device Switcher -->
            <div class="device-switcher flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-900/50 rounded-full">
                <button @click="device = 'desktop'" :class="{ 'active': device === 'desktop' }"
                    class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/80 transition-all"
                    title="عرض سطح المكتب">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                        </path>
                    </svg>
                </button>
                <button @click="device = 'tablet'" :class="{ 'active': device === 'tablet' }"
                    class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/80 transition-all"
                    title="عرض التابلت">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </button>
                <button @click="device = 'mobile'" :class="{ 'active': device === 'mobile' }"
                    class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/80 transition-all"
                    title="عرض الجوال">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </button>
            </div>

            <!-- Left Side: Purchase & Close Buttons -->
            <div class="flex items-center gap-4">
                {{-- زر الشراء موحّد مع نظام السلة --}}
                <a id="buyNow"
                    href="{{ route('checkout.cart', ['template_id' => $template->id, 'review' => 1, 'domain' => request('domain')]) }}"
                    data-template-id="{{ $template->id }}" data-template-name="{{ $translation->name }}"
                    data-price-cents="{{ $finalPriceCents }}" class="btn-primary">
                    🛒 اشترِ الآن
                </a>

                <a href="{{ route('template.show', $translation->slug) }}" title="إغلاق المعاينة"
                    class="text-3xl text-gray-500 hover:text-red-500 transition">
                    &times;
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="min-h-0 w-full flex items-center justify-center transition-all duration-500 ease-in-out"
        :class="device === 'desktop' ? 'p-0' : 'p-4 md:p-8'">

        <div class="bg-white rounded-xl shadow-2xl transition-all duration-500 ease-in-out"
            :class="{
                'w-full h-full rounded-none shadow-none': device === 'desktop',
                'w-[768px] h-full max-w-full': device === 'tablet',
                'w-[375px] h-full max-w-full': device === 'mobile'
            }">

            @if ($embedAllowed)
                <iframe src="{{ $previewUrl }}" class="w-full h-full"
                    :class="{ 'rounded-xl': device !== 'desktop' }" title="معاينة حية: {{ $translation->name }}">
                </iframe>
            @else
                <div class="text-center px-6">
                    <div class="max-w-xl mx-auto bg-white/70 dark:bg-gray-800/70 backdrop-blur rounded-2xl p-6 border">
                        <h2 class="text-xl font-bold mb-2">لا يمكن عرض المعاينة داخل الصفحة</h2>
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            هذا الموقع لا يسمح بالتضمين داخل iframe. افتح المعاينة في تبويب جديد.
                        </p>
                        <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90">
                            فتح المعاينة
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </main>

    {{-- سكربت بسيط لإضافة القالب للسلة (نفس فكرة subscribeNow في template-show) --}}
    <script>
        (function() {
            const CART_KEY = 'palgoals_cart';

            function readCart() {
                const unified = localStorage.getItem(CART_KEY);
                let items = [];
                try {
                    items = unified ? JSON.parse(unified) : [];
                } catch {
                    items = [];
                }
                return Array.isArray(items) ? items : [];
            }

            function writeCart(items) {
                localStorage.setItem(CART_KEY, JSON.stringify(items || []));
            }

            function addOrIncrementTemplate(items, tplItem) {
                const id = Number(tplItem.template_id) || 0;
                let found = false;

                const out = items.map(it => {
                    if (it?.kind === 'template' && Number(it.template_id) === id) {
                        found = true;
                        return {
                            ...it,
                            qty: Math.max(1, Number(it.qty || 1) + Number(tplItem.qty || 1)),
                        };
                    }
                    return it;
                });

                if (!found) {
                    out.push({
                        ...tplItem,
                        qty: Math.max(1, Number(tplItem.qty || 1)),
                    });
                }

                return out;
            }

            function addTemplateToCartFrom(btn) {
                try {
                    const tpl = {
                        kind: 'template',
                        template_id: Number(btn.dataset.templateId) || null,
                        template_name: btn.dataset.templateName || 'Template',
                        qty: 1,
                        price_cents: Number(btn.dataset.priceCents) || 0,
                        meta: null,
                    };

                    if (!tpl.template_id) return;

                    const items = readCart();
                    const updated = addOrIncrementTemplate(items, tpl);
                    writeCart(updated);
                } catch (err) {
                    console.error('Add to cart failed:', err);
                }
            }

            const btn = document.getElementById('buyNow');
            if (!btn) return;

            let addedOnce = false;
            const handleAdd = () => {
                if (addedOnce) return;
                addedOnce = true;
                addTemplateToCartFrom(btn);
                setTimeout(() => {
                    addedOnce = false;
                }, 800);
            };

            // left click
            btn.addEventListener('click', handleAdd);

            // middle click
            btn.addEventListener('auxclick', (e) => {
                if (e.button === 1) handleAdd();
            });

            // Space key (اترك Enter يطلق click تلقائياً)
            btn.addEventListener('keydown', (e) => {
                if (e.key === ' ') {
                    e.preventDefault();
                    handleAdd();
                }
            });
        })();
    </script>

</body>

</html>
