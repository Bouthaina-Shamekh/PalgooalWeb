/*!
 * Invoices Index UX script (robust)
 * Path: public/assets/dashboard/js/invoices-index.js
 */
(function (win, doc) {
  'use strict';

  const ready = (cb) => {
    if (doc.readyState === 'loading') doc.addEventListener('DOMContentLoaded', cb, { once: true });
    else cb();
  };

  const T = {
    chooseAction: 'Select an action first.',
    selectInvoices: 'No invoices selected.',
    selectedCount: (n) => `Selected ${n} invoice${n === 1 ? '' : 's'}.`,
    bulkConfirm: (n) => `Apply this action to ${n} invoice${n === 1 ? '' : 's'}?`,
    loading: 'Loading…',
    deleteSuccess: 'Invoice deleted.',
  };

  const showToast = (message, variant = 'info') => {
    try {
      if (!message) return;
      let root = doc.getElementById('dashboard-toast-root');
      if (!root) {
        root = doc.createElement('div');
        root.id = 'dashboard-toast-root';
        root.className = 'fixed top-4 right-4 z-[2000] flex flex-col gap-2';
        doc.body.appendChild(root);
      }
      const variants = {
        info: 'border-blue-500 bg-white text-blue-600',
        success: 'border-green-500 bg-white text-green-600',
        error: 'border-red-500 bg-white text-red-600',
      };
      const el = doc.createElement('div');
      el.className = `shadow-lg border-l-4 rounded-lg px-4 py-3 text-sm transition-opacity duration-300 ${variants[variant] || variants.info}`;
      el.textContent = message;
      root.appendChild(el);
      setTimeout(() => {
        el.classList.add('opacity-0');
        setTimeout(() => el.remove(), 300);
      }, 4000);
    } catch (_) {}
  };

  const toggleLoading = (btn, state) => {
    if (!btn) return;
    if (state) {
      if (!btn.dataset.originalHtml) btn.dataset.originalHtml = btn.innerHTML;
      btn.setAttribute('disabled', 'disabled'); // إزالة أي تفاعل
      btn.innerHTML = `<span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-current animate-ping"></span><span>${T.loading}</span></span>`;
    } else {
      if (btn.dataset.originalHtml) {
        btn.innerHTML = btn.dataset.originalHtml;
        delete btn.dataset.originalHtml;
      }
      btn.removeAttribute('disabled');
    }
  };

  // أدوات مساعدة للاختيار
  const qsa = (sel, root = doc) => Array.from(root.querySelectorAll(sel));
  const qs  = (sel, root = doc) => root.querySelector(sel);

  ready(() => {
    try {
      console.debug('[invoices-index] init');

      // ===== Dropdowns (لو موجودة) =====
      const dropdowns = qsa('[data-pc-dropdown]');
      if (dropdowns.length) {
        doc.addEventListener('click', (e) => {
          dropdowns.forEach((menu) => {
            const toggle = menu.previousElementSibling;
            if (!menu.contains(e.target) && !(toggle && toggle.contains(e.target))) {
              menu.classList.add('hidden');
              menu.setAttribute('aria-hidden', 'true');
            }
          });
        });
        doc.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') dropdowns.forEach((m) => m.classList.add('hidden'));
        });
        qsa('[data-pc-toggle="dropdown"]').forEach((btn) => {
          btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const menu = btn.parentElement.querySelector('[data-pc-dropdown]');
            if (!menu) return;
            const willOpen = menu.classList.contains('hidden');
            dropdowns.forEach((other) => {
              other.classList.add('hidden');
              other.setAttribute('aria-hidden', 'true');
            });
            menu.classList.toggle('hidden', !willOpen);
            menu.setAttribute('aria-hidden', willOpen ? 'false' : 'true');
          });
        });
      }

      // ===== إغلاق التنبيهات =====
      qsa('[data-alert-dismiss]').forEach((btn) => {
        btn.addEventListener('click', () => btn.closest('.rounded-lg')?.remove());
      });

      // ===== المتغيرات الخاصة بالإجراءات الجماعية =====
      const bulkForm    = qs('#bulk_form_invoices');
      const helper      = qs('#bulk_selection_helper');
      const actionField = qs('#bulk_action_invoices');
      const selectAll   = qs('#select_all_invoices');
      const bulkButtons = qsa('[data-bulk-action]'); // مهم: من الوثيقة كاملة

      const getCheckboxes = () => qsa('.invoice_checkbox');
      const getChecked    = () => getCheckboxes().filter((cb) => cb.checked);

      const enableButtons = (enable) => {
        // غيّر الـ property والـ attribute لضمان سلوك الـ CSS :disabled
        bulkButtons.forEach((btn) => {
          if (enable) btn.removeAttribute('disabled');
          else btn.setAttribute('disabled', 'disabled');
        });
      };

      const updateSelectAllState = () => {
        if (!selectAll) return;
        const boxes   = getCheckboxes();
        const total   = boxes.length;
        const checked = getChecked().length;
        selectAll.checked = total > 0 && checked === total;
        selectAll.indeterminate = checked > 0 && checked < total;
      };

      const updateSelectionState = () => {
        const count = getChecked().length;
        if (helper) {
          helper.textContent = count ? T.selectedCount(count) : 'Select invoices to enable the actions.';
        }
        enableButtons(count > 0);
        if (actionField && count === 0) actionField.value = '';
        updateSelectAllState();
        return count;
      };

      // تهيئة أولية
      updateSelectionState();

      // تفويض change للـ checkboxes (حتى لو أضيفت لاحقًا)
      doc.addEventListener('change', (e) => {
        if (e.target && e.target.classList && e.target.classList.contains('invoice_checkbox')) {
          updateSelectionState();
        }
      });

      // دعم Shift+Click
      let lastIndex = -1;
      doc.addEventListener('click', (e) => {
        const boxes = getCheckboxes();
        if (e.target && e.target.classList && e.target.classList.contains('invoice_checkbox')) {
          const idx = boxes.indexOf(e.target);
          if (e.shiftKey && lastIndex >= 0 && lastIndex !== idx) {
            const [start, end] = [Math.min(lastIndex, idx), Math.max(lastIndex, idx)];
            const state = e.target.checked;
            for (let i = start; i <= end; i++) boxes[i].checked = state;
          }
          lastIndex = idx;
          updateSelectionState();
        }
      });

      // تحديد/إلغاء تحديد الكل
      if (selectAll) {
        selectAll.addEventListener('change', () => {
          const boxes = getCheckboxes();
          boxes.forEach((cb) => (cb.checked = selectAll.checked));
          updateSelectionState();
        });
      }

      // تنفيذ الإجراءات الجماعية
      if (bulkForm && bulkButtons.length) {
        bulkButtons.forEach((btn) => {
          btn.addEventListener('click', (e) => {
            e.preventDefault();

            const action = btn.dataset.bulkAction || '';
            if (!action) return showToast(T.chooseAction, 'info');

            const count = updateSelectionState();
            if (!count) return showToast(T.selectInvoices, 'info');

            const tpl = btn.dataset.confirm || '';
            const msg = tpl ? tpl.replace(':count', count) : T.bulkConfirm(count);
            if (!win.confirm(msg)) return;

            if (actionField) actionField.value = action;

            // حقن ids المختارة داخل الفورم (لضمان إرسالها)
            qsa('input[name="ids[]"].__dynamic', bulkForm).forEach((n) => n.remove());
            getChecked().forEach((cb) => {
              const hidden = doc.createElement('input');
              hidden.type = 'hidden';
              hidden.name = 'ids[]';
              hidden.value = cb.value;
              hidden.classList.add('__dynamic');
              bulkForm.appendChild(hidden);
            });

            // منع النقر المكرر
            enableButtons(false);
            toggleLoading(btn, true);

            bulkForm.submit();
          });
        });
      }

      // حذف Ajax لسطر الفاتورة + fallback
      qsa('form.ajax-action').forEach((form) => {
        form.addEventListener('submit', (e) => {
          e.preventDefault();
          const confirmMsg = form.getAttribute('data-confirm');
          if (confirmMsg && !win.confirm(confirmMsg)) return;

          const submitBtn = form.querySelector('button[type="submit"],input[type="submit"]');
          toggleLoading(submitBtn, true);

          const data = new FormData(form); // يحتوي _method=DELETE
          fetch(form.action, {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': doc.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: data,
          })
            .then((res) => {
              if (res.ok) {
                form.closest('tr')?.remove();
                showToast(T.deleteSuccess, 'success');
                updateSelectionState();
              } else {
                toggleLoading(submitBtn, false);
                form.submit(); // fallback كامل
              }
            })
            .catch(() => {
              toggleLoading(submitBtn, false);
              form.submit(); // fallback
            });
        });
      });

      console.debug('[invoices-index] ready');
    } catch (err) {
      // لا تسمح لخطأ واحد أن يعطّل الصفحة
      try { console.error('[invoices-index] failed:', err); } catch (_) {}
    }
  });
})(window, document);
