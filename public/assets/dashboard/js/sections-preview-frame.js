(function () {
    const initSectionsPreviewFrame = function () {
        const previewRoot = document.querySelector('[data-sections-preview-root]');
        if (!(previewRoot instanceof HTMLElement)) {
            return;
        }

        const previewBlocks = Array.from(document.querySelectorAll('[data-preview-section-id]'));
        const initialHighlight = Number(previewRoot.dataset.highlightSectionId || 0);
        const currentOrigin = window.location.origin;

        const getTopOverlayOffset = function () {
            return Array.from(document.body.querySelectorAll('*')).reduce(function (maxOffset, element) {
                const style = window.getComputedStyle(element);
                if (!['sticky', 'fixed'].includes(style.position)) {
                    return maxOffset;
                }

                const rect = element.getBoundingClientRect();
                if (rect.height <= 0 || rect.width <= 0) {
                    return maxOffset;
                }

                if (rect.top > 4 || rect.bottom <= 0) {
                    return maxOffset;
                }

                return Math.max(maxOffset, rect.bottom);
            }, 0);
        };

        const updatePreviewOffset = function () {
            const offset = Math.ceil(getTopOverlayOffset());
            document.documentElement.style.setProperty('--sections-preview-top-offset', offset + 'px');

            return offset;
        };

        const scrollSectionIntoView = function (element) {
            if (!element) {
                return;
            }

            const offset = updatePreviewOffset() + 16;
            const targetTop = window.scrollY + element.getBoundingClientRect().top - offset;

            window.scrollTo({
                top: Math.max(targetTop, 0),
                behavior: 'smooth',
            });
        };

        const setHighlightedSection = function (sectionId, shouldScroll) {
            if (!sectionId) {
                return;
            }

            previewBlocks.forEach(function (block) {
                const isActive = Number(block.dataset.previewSectionId || 0) === sectionId;
                block.classList.toggle('is-highlighted', isActive);
            });

            const activeBlock = document.querySelector('[data-preview-section-id="' + sectionId + '"]');
            if (activeBlock && shouldScroll !== false) {
                scrollSectionIntoView(activeBlock);
            }
        };

        previewBlocks.forEach(function (block) {
            block.addEventListener('click', function (event) {
                const clickedLink = event.target.closest('a');

                if (clickedLink && clickedLink.getAttribute('target') === '_blank') {
                    return;
                }

                if (event.target.closest('a, button, input, textarea, select, form')) {
                    event.preventDefault();
                }

                const sectionId = Number(block.dataset.previewSectionId || 0);
                if (!sectionId) {
                    return;
                }

                setHighlightedSection(sectionId, false);

                if (window.parent && window.parent !== window) {
                    window.parent.postMessage({
                        type: 'sections-preview:selected',
                        sectionId: sectionId,
                    }, currentOrigin);
                }
            });
        });

        document.addEventListener('click', function (event) {
            const clickedLink = event.target.closest('a');

            if (clickedLink && clickedLink.getAttribute('target') === '_blank') {
                return;
            }

            if (event.target.closest('a, button, [role="button"], form')) {
                event.preventDefault();
            }
        }, true);

        window.addEventListener('message', function (event) {
            if (event.origin !== currentOrigin) {
                return;
            }

            const payload = event.data || {};
            if (payload.type === 'sections-preview:highlight') {
                setHighlightedSection(Number(payload.sectionId || 0), true);
            }
        });

        window.addEventListener('resize', updatePreviewOffset);

        updatePreviewOffset();

        if (initialHighlight) {
            window.setTimeout(function () {
                setHighlightedSection(initialHighlight, true);
            }, 120);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSectionsPreviewFrame);
        return;
    }

    initSectionsPreviewFrame();
})();
