(function () {
    const asRoot = (scope) => scope instanceof Element || scope instanceof Document ? scope : document;

    const slugify = (value) => String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    const countLines = (value) => String(value || '')
        .split(/\r\n|\r|\n/)
        .map((item) => item.trim())
        .filter(Boolean).length;

    window.refreshHostingPricingCategoryDatalists = function (scope) {
        const root = asRoot(scope);
        const repeaters = root.matches?.('[data-pricing-category-repeater]')
            ? [root]
            : Array.from(root.querySelectorAll('[data-pricing-category-repeater]'));

        repeaters.forEach((repeater) => {
            const datalistId = repeater.dataset.categoryDatalistId;
            const datalist = datalistId ? document.getElementById(datalistId) : null;

            if (!(datalist instanceof HTMLDataListElement)) {
                return;
            }

            const options = Array.from(repeater.querySelectorAll('[data-pricing-category-field="key"]'))
                .map((input) => String(input.value || '').trim())
                .filter(Boolean);

            datalist.innerHTML = '';

            Array.from(new Set(options)).forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                datalist.appendChild(option);
            });
        });
    };

    window.initHostingPricingCategoryRepeaters = function (scope) {
        const root = asRoot(scope);
        const repeaters = root.matches?.('[data-pricing-category-repeater]')
            ? [root]
            : Array.from(root.querySelectorAll('[data-pricing-category-repeater]'));

        repeaters.forEach((repeater) => {
            if (repeater.dataset.pricingCategoryRepeaterBound === '1') {
                return;
            }

            const list = repeater.querySelector('[data-pricing-category-items]');
            const template = repeater.querySelector('template[data-pricing-category-template]');
            const emptyState = repeater.querySelector('[data-pricing-category-empty]');
            const addButtons = Array.from(repeater.querySelectorAll('[data-add-pricing-category]'));
            const itemLabel = repeater.dataset.categoryItemLabel || 'Category';
            const itemHint = repeater.dataset.categoryItemHint || 'Click to edit this pricing tab';

            if (!list || !template) {
                repeater.dataset.pricingCategoryRepeaterBound = '1';
                return;
            }

            const setExpanded = (item, expanded, collapseOthers = false) => {
                if (!(item instanceof HTMLElement)) {
                    return;
                }

                if (collapseOthers) {
                    Array.from(list.querySelectorAll('[data-pricing-category-item]')).forEach((entry) => {
                        if (entry !== item) {
                            setExpanded(entry, false, false);
                        }
                    });
                }

                const body = item.querySelector('[data-pricing-category-body]');
                const toggle = item.querySelector('[data-pricing-category-toggle]');
                const icon = item.querySelector('[data-pricing-category-toggle-icon]');

                body?.classList.toggle('hidden', !expanded);
                toggle?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                icon?.classList.toggle('rotate-180', expanded);
            };

            const refreshMeta = (item, index = 0) => {
                const labelInput = item.querySelector('[data-pricing-category-field="label"]');
                const keyInput = item.querySelector('[data-pricing-category-field="key"]');
                const title = item.querySelector('[data-pricing-category-title]');
                const summary = item.querySelector('[data-pricing-category-summary]');
                const label = String(labelInput?.value || '').trim();
                const key = String(keyInput?.value || '').trim();

                if (title) {
                    title.textContent = label || `${itemLabel} ${Number(index) + 1}`;
                }

                if (summary) {
                    summary.textContent = key ? `Key: ${key}` : itemHint;
                }
            };

            const reindexItems = () => {
                const items = Array.from(list.querySelectorAll('[data-pricing-category-item]'));

                items.forEach((item, index) => {
                    item.querySelectorAll('[data-name-template]').forEach((field) => {
                        const templateName = field.dataset.nameTemplate || '';
                        if (templateName) {
                            field.name = templateName.replace(/__INDEX__/g, String(index));
                        }
                    });

                    refreshMeta(item, index);
                });

                emptyState?.classList.toggle('hidden', items.length > 0);

                if (items.length > 0 && !items.some((item) => !item.querySelector('[data-pricing-category-body]')?.classList.contains('hidden'))) {
                    setExpanded(items[0], true, false);
                }

                window.refreshHostingPricingCategoryDatalists?.(repeater.closest('[data-editor-tab-panel]') || repeater);
            };

            const bindItem = (item) => {
                if (!(item instanceof HTMLElement) || item.dataset.pricingCategoryItemBound === '1') {
                    return;
                }

                const labelInput = item.querySelector('[data-pricing-category-field="label"]');
                const keyInput = item.querySelector('[data-pricing-category-field="key"]');
                const removeButton = item.querySelector('[data-remove-pricing-category]');
                const duplicateButton = item.querySelector('[data-duplicate-pricing-category]');
                const toggleButton = item.querySelector('[data-pricing-category-toggle]');

                labelInput?.addEventListener('input', function () {
                    if (keyInput && !String(keyInput.value || '').trim()) {
                        keyInput.value = slugify(labelInput.value);
                    }

                    refreshMeta(item);
                    window.refreshHostingPricingCategoryDatalists?.(repeater.closest('[data-editor-tab-panel]') || repeater);
                });

                keyInput?.addEventListener('input', function () {
                    keyInput.value = slugify(keyInput.value);
                    refreshMeta(item);
                    window.refreshHostingPricingCategoryDatalists?.(repeater.closest('[data-editor-tab-panel]') || repeater);
                });

                removeButton?.addEventListener('click', function () {
                    item.remove();
                    reindexItems();
                });

                duplicateButton?.addEventListener('click', function () {
                    const createdItem = createItem({
                        label: labelInput?.value || '',
                        key: keyInput?.value || '',
                    });

                    setExpanded(createdItem, true, true);
                });

                toggleButton?.addEventListener('click', function () {
                    const shouldExpand = item.querySelector('[data-pricing-category-body]')?.classList.contains('hidden') ?? true;
                    setExpanded(item, shouldExpand, shouldExpand);
                });

                refreshMeta(item);
                item.dataset.pricingCategoryItemBound = '1';
            };

            const createItem = (seed = {}) => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template.innerHTML.trim();

                const item = wrapper.firstElementChild;
                if (!(item instanceof HTMLElement)) {
                    return null;
                }

                list.appendChild(item);
                bindItem(item);

                const labelInput = item.querySelector('[data-pricing-category-field="label"]');
                const keyInput = item.querySelector('[data-pricing-category-field="key"]');

                if (labelInput && typeof seed.label === 'string') {
                    labelInput.value = seed.label;
                }

                if (keyInput && typeof seed.key === 'string') {
                    keyInput.value = slugify(seed.key);
                }

                reindexItems();
                setExpanded(item, true, true);

                return item;
            };

            addButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const item = createItem();
                    const labelInput = item?.querySelector('[data-pricing-category-field="label"]');

                    if (labelInput instanceof HTMLElement) {
                        window.setTimeout(() => labelInput.focus(), 30);
                    }
                });
            });

            Array.from(list.querySelectorAll('[data-pricing-category-item]')).forEach(bindItem);

            if (typeof Sortable !== 'undefined' && list.dataset.pricingCategorySortableBound !== '1') {
                Sortable.create(list, {
                    animation: 160,
                    handle: '[data-pricing-category-drag-handle]',
                    ghostClass: 'sections-sortable-ghost',
                    chosenClass: 'sections-sortable-chosen',
                    dragClass: 'sections-sortable-drag',
                    onEnd: reindexItems,
                });

                list.dataset.pricingCategorySortableBound = '1';
            }

            reindexItems();
            repeater.dataset.pricingCategoryRepeaterBound = '1';
        });
    };

    window.initHostingPricingPlanRepeaters = function (scope) {
        const root = asRoot(scope);
        const repeaters = root.matches?.('[data-pricing-plan-repeater]')
            ? [root]
            : Array.from(root.querySelectorAll('[data-pricing-plan-repeater]'));

        repeaters.forEach((repeater) => {
            if (repeater.dataset.pricingPlanRepeaterBound === '1') {
                return;
            }

            const list = repeater.querySelector('[data-pricing-plan-items]');
            const template = repeater.querySelector('template[data-pricing-plan-template]');
            const emptyState = repeater.querySelector('[data-pricing-plan-empty]');
            const addButtons = Array.from(repeater.querySelectorAll('[data-add-pricing-plan]'));
            const itemLabel = repeater.dataset.planItemLabel || 'Plan';
            const itemHint = repeater.dataset.planItemHint || 'Click to edit this plan card';

            if (!list || !template) {
                repeater.dataset.pricingPlanRepeaterBound = '1';
                return;
            }

            const setExpanded = (item, expanded, collapseOthers = false) => {
                if (!(item instanceof HTMLElement)) {
                    return;
                }

                if (collapseOthers) {
                    Array.from(list.querySelectorAll('[data-pricing-plan-item]')).forEach((entry) => {
                        if (entry !== item) {
                            setExpanded(entry, false, false);
                        }
                    });
                }

                const body = item.querySelector('[data-pricing-plan-body]');
                const toggle = item.querySelector('[data-pricing-plan-toggle]');
                const icon = item.querySelector('[data-pricing-plan-toggle-icon]');

                body?.classList.toggle('hidden', !expanded);
                toggle?.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                icon?.classList.toggle('rotate-180', expanded);
            };

            const refreshMeta = (item, index = 0) => {
                const titleInput = item.querySelector('[data-pricing-plan-field="title"]');
                const categoryInput = item.querySelector('[data-pricing-plan-field="category"]');
                const featuresInput = item.querySelector('[data-pricing-plan-field="features"]');
                const buttonLabelInput = item.querySelector('[data-pricing-plan-field="button_label"]');
                const newTabInput = item.querySelector('[data-pricing-plan-field="button_new_tab"]');
                const title = item.querySelector('[data-pricing-plan-title]');
                const summary = item.querySelector('[data-pricing-plan-summary]');

                const planTitle = String(titleInput?.value || '').trim();
                const category = String(categoryInput?.value || '').trim();
                const buttonLabel = String(buttonLabelInput?.value || '').trim();
                const featuresCount = countLines(featuresInput?.value || '');
                const opensNewTab = Boolean(newTabInput?.checked);
                const parts = [];

                if (category) {
                    parts.push(`Tab: ${category}`);
                }

                if (featuresCount) {
                    parts.push(`${featuresCount} feature${featuresCount === 1 ? '' : 's'}`);
                }

                if (buttonLabel) {
                    parts.push(`CTA: ${buttonLabel}`);
                }

                if (opensNewTab) {
                    parts.push('Opens in a new tab');
                }

                if (title) {
                    title.textContent = planTitle || `${itemLabel} ${Number(index) + 1}`;
                }

                if (summary) {
                    summary.textContent = parts.length ? parts.join(' - ') : itemHint;
                }
            };

            const reindexItems = () => {
                const items = Array.from(list.querySelectorAll('[data-pricing-plan-item]'));

                items.forEach((item, index) => {
                    item.querySelectorAll('[data-name-template]').forEach((field) => {
                        const templateName = field.dataset.nameTemplate || '';
                        if (templateName) {
                            field.name = templateName.replace(/__INDEX__/g, String(index));
                        }
                    });

                    refreshMeta(item, index);
                });

                emptyState?.classList.toggle('hidden', items.length > 0);

                if (items.length > 0 && !items.some((item) => !item.querySelector('[data-pricing-plan-body]')?.classList.contains('hidden'))) {
                    setExpanded(items[0], true, false);
                }
            };

            const bindItem = (item) => {
                if (!(item instanceof HTMLElement) || item.dataset.pricingPlanItemBound === '1') {
                    return;
                }

                const titleInput = item.querySelector('[data-pricing-plan-field="title"]');
                const categoryInput = item.querySelector('[data-pricing-plan-field="category"]');
                const featuresInput = item.querySelector('[data-pricing-plan-field="features"]');
                const buttonLabelInput = item.querySelector('[data-pricing-plan-field="button_label"]');
                const buttonUrlInput = item.querySelector('[data-pricing-plan-field="button_url"]');
                const newTabInput = item.querySelector('[data-pricing-plan-field="button_new_tab"]');
                const removeButton = item.querySelector('[data-remove-pricing-plan]');
                const duplicateButton = item.querySelector('[data-duplicate-pricing-plan]');
                const toggleButton = item.querySelector('[data-pricing-plan-toggle]');

                [titleInput, categoryInput, featuresInput, buttonLabelInput, buttonUrlInput].forEach((field) => {
                    field?.addEventListener('input', function () {
                        refreshMeta(item);
                    });
                });

                newTabInput?.addEventListener('change', function () {
                    refreshMeta(item);
                });

                removeButton?.addEventListener('click', function () {
                    item.remove();
                    reindexItems();
                });

                duplicateButton?.addEventListener('click', function () {
                    const createdItem = createItem({
                        title: titleInput?.value || '',
                        category: categoryInput?.value || '',
                        features: featuresInput?.value || '',
                        buttonLabel: buttonLabelInput?.value || '',
                        buttonUrl: buttonUrlInput?.value || '',
                        buttonNewTab: Boolean(newTabInput?.checked),
                    });

                    setExpanded(createdItem, true, true);
                });

                toggleButton?.addEventListener('click', function () {
                    const shouldExpand = item.querySelector('[data-pricing-plan-body]')?.classList.contains('hidden') ?? true;
                    setExpanded(item, shouldExpand, shouldExpand);
                });

                refreshMeta(item);
                item.dataset.pricingPlanItemBound = '1';
            };

            const createItem = (seed = {}) => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = template.innerHTML.trim();

                const item = wrapper.firstElementChild;
                if (!(item instanceof HTMLElement)) {
                    return null;
                }

                list.appendChild(item);
                bindItem(item);

                const titleInput = item.querySelector('[data-pricing-plan-field="title"]');
                const categoryInput = item.querySelector('[data-pricing-plan-field="category"]');
                const featuresInput = item.querySelector('[data-pricing-plan-field="features"]');
                const buttonLabelInput = item.querySelector('[data-pricing-plan-field="button_label"]');
                const buttonUrlInput = item.querySelector('[data-pricing-plan-field="button_url"]');
                const newTabInput = item.querySelector('[data-pricing-plan-field="button_new_tab"]');

                if (titleInput && typeof seed.title === 'string') {
                    titleInput.value = seed.title;
                }
                if (categoryInput && typeof seed.category === 'string') {
                    categoryInput.value = seed.category;
                }
                if (featuresInput && typeof seed.features === 'string') {
                    featuresInput.value = seed.features;
                }
                if (buttonLabelInput && typeof seed.buttonLabel === 'string') {
                    buttonLabelInput.value = seed.buttonLabel;
                }
                if (buttonUrlInput && typeof seed.buttonUrl === 'string') {
                    buttonUrlInput.value = seed.buttonUrl;
                }
                if (newTabInput) {
                    newTabInput.checked = Boolean(seed.buttonNewTab);
                }

                reindexItems();
                setExpanded(item, true, true);

                return item;
            };

            addButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const item = createItem();
                    const titleInput = item?.querySelector('[data-pricing-plan-field="title"]');

                    if (titleInput instanceof HTMLElement) {
                        window.setTimeout(() => titleInput.focus(), 30);
                    }
                });
            });

            Array.from(list.querySelectorAll('[data-pricing-plan-item]')).forEach(bindItem);

            if (typeof Sortable !== 'undefined' && list.dataset.pricingPlanSortableBound !== '1') {
                Sortable.create(list, {
                    animation: 160,
                    handle: '[data-pricing-plan-drag-handle]',
                    ghostClass: 'sections-sortable-ghost',
                    chosenClass: 'sections-sortable-chosen',
                    dragClass: 'sections-sortable-drag',
                    onEnd: reindexItems,
                });

                list.dataset.pricingPlanSortableBound = '1';
            }

            reindexItems();
            repeater.dataset.pricingPlanRepeaterBound = '1';
        });
    };
})();
