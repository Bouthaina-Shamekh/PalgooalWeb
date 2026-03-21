(() => {
    const root = document.querySelector('[data-template-price-box]');
    const minusBtn = document.getElementById('period-minus');
    const plusBtn = document.getElementById('period-plus');
    const dropdownBtn = document.getElementById('period-dropdown-btn');
    const dropdownMenu = document.getElementById('period-dropdown-menu');
    const label = document.getElementById('period-label');
    const priceValue = document.getElementById('template-price-value');
    const originalPriceValue = document.getElementById('template-original-price');
    const buyNowBtn = document.getElementById('buy-now-btn');

    if (!root || !minusBtn || !plusBtn || !dropdownBtn || !dropdownMenu || !label || !priceValue || !buyNowBtn) {
        return;
    }

    const finalPrice = Number(root.dataset.finalPrice || 0);
    const basePrice = Number(root.dataset.basePrice || finalPrice);
    let years = 1;

    function formatMoney(value) {
        return '$' + Number(value || 0).toFixed(2);
    }

    function updatePeriodButtonsState() {
        const atFirst = years === 1;
        const atLast = years === 3;

        minusBtn
            .classList.toggle('stroke-purple-brand', !atFirst);
        minusBtn
            .classList.toggle('text-purple-brand', !atFirst);
        minusBtn
            .classList.toggle('hover:bg-gray-50', !atFirst);
        minusBtn
            .classList.toggle('stroke-gray-400', atFirst);
        minusBtn
            .classList.toggle('text-gray-400', atFirst);
        minusBtn
            .classList.toggle('opacity-60', atFirst);
        minusBtn
            .classList.toggle('pointer-events-none', atFirst);
        minusBtn.disabled = atFirst;

        plusBtn
            .classList.toggle('stroke-purple-brand', !atLast);
        plusBtn
            .classList.toggle('text-purple-brand', !atLast);
        plusBtn
            .classList.toggle('hover:bg-gray-50', !atLast);
        plusBtn
            .classList.toggle('stroke-gray-400', atLast);
        plusBtn
            .classList.toggle('text-gray-400', atLast);
        plusBtn
            .classList.toggle('opacity-60', atLast);
        plusBtn
            .classList.toggle('pointer-events-none', atLast);
        plusBtn.disabled = atLast;
    }

    function updateCheckoutUrl() {
        const url = new URL(buyNowBtn.dataset.baseUrl, window.location.origin);
        url.searchParams.set('template_id', buyNowBtn.dataset.templateId);
        url.searchParams.set('review', '1');
        url.searchParams.set('years', String(years));

        if (buyNowBtn.dataset.domain) {
            url.searchParams.set('domain', buyNowBtn.dataset.domain);
        }

        buyNowBtn.href = url.pathname + url.search;
        buyNowBtn.dataset.priceCents = String(Math.round(finalPrice * years * 100));
    }

    function renderPeriodState() {
        label.textContent = years === 1 ? '1 Year' : `${years} Years`;
        priceValue.textContent = formatMoney(finalPrice * years);

        if (originalPriceValue) {
            originalPriceValue.textContent = formatMoney(basePrice * years);
        }

        updateCheckoutUrl();
        updatePeriodButtonsState();
    }

    function setYears(nextYears) {
        years = Math.max(1, Math.min(3, nextYears));
        renderPeriodState();
    }

    minusBtn.addEventListener('click', () => {
        setYears(years - 1);
    });

    plusBtn.addEventListener('click', () => {
        setYears(years + 1);
    });

    dropdownBtn.addEventListener('click', () => {
        dropdownMenu.classList.toggle('hidden');
    });

    dropdownMenu.querySelectorAll('.period-option').forEach((option) => {
        option.addEventListener('click', () => {
            setYears(Number(option.dataset.period || 1));
            dropdownMenu.classList.add('hidden');
        });
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('#period-dropdown-btn') && !event.target.closest('#period-dropdown-menu')) {
            dropdownMenu.classList.add('hidden');
        }
    });

    renderPeriodState();
})();

(() => {
    const CART_KEY = 'palgoals_cart';
    const btn = document.getElementById('buy-now-btn');

    if (!btn) {
        return;
    }

    function readCart() {
        const legacy = localStorage.getItem('palgoals_cart_domains');
        const unified = localStorage.getItem(CART_KEY);
        let items = [];

        try {
            items = unified ? JSON.parse(unified) : [];
        } catch (error) {
            items = [];
        }

        if (legacy && !unified) {
            try {
                const oldItems = JSON.parse(legacy);

                if (Array.isArray(oldItems)) {
                    items = items.concat(oldItems.map((item) => ({
                        kind: 'domain',
                        domain: String(item.domain || '').toLowerCase(),
                        item_option: item.item_option ?? item.option ?? null,
                        price_cents: Number(item.price_cents) || 0,
                        meta: item.meta ?? null,
                    })));
                }
            } catch (error) {
            }
        }

        return Array.isArray(items) ? items : [];
    }

    function writeCart(items) {
        localStorage.setItem(CART_KEY, JSON.stringify(items || []));
    }

    function addOrIncrementTemplate(items, nextItem) {
        const id = Number(nextItem.template_id) || 0;
        let found = false;

        const updated = items.map((item) => {
            if (item?.kind === 'template' && Number(item.template_id) === id) {
                found = true;

                return {
                    ...item,
                    qty: Math.max(1, Number(item.qty || 1) + Number(nextItem.qty || 1)),
                };
            }

            return item;
        });

        if (!found) {
            updated.push({
                ...nextItem,
                qty: Math.max(1, Number(nextItem.qty || 1)),
            });
        }

        return updated;
    }

    function addTemplateToCart() {
        const nextItem = {
            kind: 'template',
            template_id: Number(btn.dataset.templateId) || null,
            template_name: btn.dataset.templateName || 'Template',
            qty: 1,
            price_cents: Number(btn.dataset.priceCents) || 0,
            meta: null,
        };

        if (!nextItem.template_id) {
            return;
        }

        const items = readCart();
        writeCart(addOrIncrementTemplate(items, nextItem));
    }

    let addedOnce = false;

    function handleAdd() {
        if (addedOnce) {
            return;
        }

        addedOnce = true;
        addTemplateToCart();

        window.setTimeout(() => {
            addedOnce = false;
        }, 800);
    }

    btn.addEventListener('click', handleAdd);
    btn.addEventListener('auxclick', (event) => {
        if (event.button === 1) {
            handleAdd();
        }
    });
    btn.addEventListener('keydown', (event) => {
        if (event.key === ' ') {
            event.preventDefault();
            handleAdd();
        }
    });
})();

(() => {
    const buyNowBtn = document.getElementById('buy-now-btn');

    if (!buyNowBtn) {
        return;
    }

    let scaleTimeout = null;

    function pulseBuyButton() {
        buyNowBtn.classList.add('scale-95');

        if (scaleTimeout) {
            window.clearTimeout(scaleTimeout);
        }

        scaleTimeout = window.setTimeout(() => {
            buyNowBtn.classList.remove('scale-95');
            scaleTimeout = null;
        }, 150);
    }

    buyNowBtn.addEventListener('pointerdown', pulseBuyButton);
    buyNowBtn.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            pulseBuyButton();
        }
    });
})();

(() => {
    if (!window.gsap || !window.ScrollTrigger) {
        return;
    }

    window.gsap.registerPlugin(window.ScrollTrigger);

    window.gsap.utils.toArray('.animate-from-left').forEach((element) => {
        window.gsap.from(element, {
            x: -60,
            opacity: 0.85,
            duration: 0.7,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: element,
                start: 'top 88%',
            },
        });
    });

    window.gsap.utils.toArray('.animate-from-right').forEach((element) => {
        window.gsap.from(element, {
            x: 60,
            opacity: 0.85,
            duration: 0.7,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: element,
                start: 'top 88%',
            },
        });
    });
})();

(() => {
    const sidebar = document.getElementById('subscription-sidebar');
    const panel = sidebar?.querySelector('[data-template-price-box]');
    const footer = document.querySelector('footer');

    if (!sidebar || !panel) {
        return;
    }

    let ticking = false;

    function getTopOffset() {
        return window.innerWidth >= 1024 ? 96 : 24;
    }

    function setRelativeState() {
        panel.style.position = 'relative';
        panel.style.top = '0';
        panel.style.left = '0';
        panel.style.width = '100%';
    }

    function setFixedState(topOffset) {
        const sidebarRect = sidebar.getBoundingClientRect();

        panel.style.position = 'fixed';
        panel.style.top = `${topOffset}px`;
        panel.style.left = `${sidebarRect.left}px`;
        panel.style.width = `${sidebarRect.width}px`;
    }

    function setAbsoluteState(topValue) {
        panel.style.position = 'absolute';
        panel.style.top = `${Math.max(0, topValue)}px`;
        panel.style.left = '0';
        panel.style.width = '100%';
    }

    function syncFloatingSidebar() {
        const topOffset = getTopOffset();
        const sidebarRect = sidebar.getBoundingClientRect();
        const sidebarTop = window.scrollY + sidebarRect.top;
        const panelHeight = panel.offsetHeight;
        const footerTop = footer
            ? window.scrollY + footer.getBoundingClientRect().top
            : document.documentElement.scrollHeight;
        const start = sidebarTop - topOffset;
        const end = Math.max(start, footerTop - panelHeight - topOffset - 24);

        sidebar.style.position = 'relative';
        sidebar.style.top = 'auto';
        sidebar.style.minHeight = `${panelHeight}px`;

        if (window.scrollY <= start) {
            setRelativeState();
            return;
        }

        if (window.scrollY < end) {
            setFixedState(topOffset);
            return;
        }

        setAbsoluteState(end - sidebarTop);
    }

    function requestSync() {
        if (ticking) {
            return;
        }

        ticking = true;

        window.requestAnimationFrame(() => {
            ticking = false;
            syncFloatingSidebar();
        });
    }

    window.addEventListener('scroll', requestSync, { passive: true });
    window.addEventListener('resize', requestSync);
    window.addEventListener('load', requestSync);

    if (document.fonts?.ready) {
        document.fonts.ready.then(requestSync).catch(() => {});
    }

    requestSync();
})();
