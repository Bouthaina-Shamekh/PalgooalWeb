// تفعيل القوائم المنسدلة بلوحة المفاتيح والماوس
  document.querySelectorAll('[role="button"]').forEach(el => {
    el.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        el.click();
      }
    });

    el.addEventListener('click', () => {
      const menu = el.nextElementSibling;
      const isOpen = el.getAttribute('aria-expanded') === 'true';
      el.setAttribute('aria-expanded', String(!isOpen));
      menu.classList.toggle('hidden');
      menu.classList.toggle('opacity-0');
      menu.classList.toggle('invisible');
      menu.classList.toggle('scale-95');
    });
  });

  // تفعيل قائمة المستخدم
  document.querySelectorAll('#user-menu-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const menu = btn.nextElementSibling;
      menu.classList.toggle('hidden');
    });
  });

  // فتح/إغلاق القائمة الجانبية
  document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
    document.getElementById('mobileSidebar')?.classList.remove('hidden');
  });

  document.getElementById('sidebar-close')?.addEventListener('click', () => {
    document.getElementById('mobileSidebar')?.classList.add('hidden');
  });

  // فتح/إغلاق عناصر الجوال الفرعية
  window.toggleMobileDropdown = function(button) {
    const menu = button.nextElementSibling;
    menu.classList.toggle('hidden');
  }
  const langSwitch = document.getElementById('lang-switch');
const langMenu = document.getElementById('lang-menu');

langSwitch?.addEventListener('click', () => {
  langMenu.classList.toggle('invisible');
  langMenu.classList.toggle('opacity-0');
  langMenu.classList.toggle('scale-95');
});


  const sidebar = document.getElementById('mobileSidebar');
  const overlay = document.getElementById('sidebar-overlay');

  document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
    sidebar.classList.remove('translate-x-full', 'invisible', 'opacity-0');
    sidebar.classList.add('translate-x-0');
    overlay.classList.remove('hidden');
    overlay.classList.add('block');
    setTimeout(() => overlay.classList.add('opacity-70'), 10);
  });

  function closeSidebar() {
    sidebar.classList.remove('translate-x-0');
    sidebar.classList.add('translate-x-full');
    overlay.classList.remove('opacity-100');
    setTimeout(() => {
      sidebar.classList.add('invisible', 'opacity-0');
      overlay.classList.remove('block');
      overlay.classList.add('hidden');
    }, 300);
  }

  document.getElementById('sidebar-close')?.addEventListener('click', closeSidebar);
  overlay?.addEventListener('click', closeSidebar);

  window.toggleMobileDropdown = function(button) {
    const menu = button.nextElementSibling;
    const isOpen = !menu.classList.contains('hidden');
    button.setAttribute('aria-expanded', String(!isOpen));

    document.querySelectorAll('#mobileSidebar .relative > div').forEach(dropdown => {
      if (dropdown !== menu) {
        dropdown.classList.add('hidden');
      }
    });

    menu.classList.toggle('hidden');
  };

  document.querySelectorAll('[data-header-dropdown-toggle]').forEach((button) => {
    const dropdownId = button.getAttribute('aria-controls');
    const dropdown = dropdownId ? document.getElementById(dropdownId) : null;
    const group = button.closest('.group');

    function setExpanded(expanded) {
      button.setAttribute('aria-expanded', String(expanded));
    }

    function closeIfFocusLeft() {
      setTimeout(() => {
        if (!group?.matches(':hover') && !group?.contains(document.activeElement)) {
          setExpanded(false);
        }
      }, 0);
    }

    group?.addEventListener('mouseenter', () => setExpanded(true));
    group?.addEventListener('mouseleave', closeIfFocusLeft);
    group?.addEventListener('focusin', () => setExpanded(true));
    group?.addEventListener('focusout', closeIfFocusLeft);

    button.addEventListener('click', () => {
      setExpanded(true);
    });

    button.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setExpanded(false);
        button.blur();
        return;
      }

      if ((event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') && dropdown) {
        setExpanded(true);
        dropdown.querySelector('a[href]')?.focus();
      }
    });
  });

  // Purple topbar header variant mobile drawer
  const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
  const mobileMenu = document.getElementById('mobile-menu');
  const mobileMenuContainer = document.getElementById('mobile-menu-container');
  let mobileMenuCloseTimer = null;

  function isMobileMenuOpen() {
    return !!mobileMenu && !mobileMenu.classList.contains('invisible');
  }

  function clearMobileMenuCloseTimer() {
    if (!mobileMenuCloseTimer) return;

    clearTimeout(mobileMenuCloseTimer);
    mobileMenuCloseTimer = null;
  }

  function focusMobileMenu() {
    if (!mobileMenuContainer) return;

    const focusTarget = mobileMenuContainer.querySelector('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')
      || mobileMenuContainer;

    if (typeof focusTarget.focus === 'function') {
      focusTarget.focus({ preventScroll: true });
    }
  }

  function openMobileMenu() {
    if (!mobileMenu || !mobileMenuContainer) return;

    clearMobileMenuCloseTimer();
    mobileMenu.classList.remove('invisible', 'opacity-0');
    mobileMenu.classList.add('opacity-100');

    mobileMenuContainer.classList.remove('ltr:translate-x-full', 'rtl:-translate-x-full');
    mobileMenuContainer.classList.add('translate-x-0');
    mobileMenuContainer.setAttribute('aria-hidden', 'false');
    mobileMenuToggle?.setAttribute('aria-expanded', 'true');
    document.body.classList.add('overflow-hidden');
    setTimeout(focusMobileMenu, 0);
  }

  function closeMobileMenu(returnFocus = true) {
    if (!mobileMenu || !mobileMenuContainer) return;

    clearMobileMenuCloseTimer();
    mobileMenu.classList.remove('opacity-100');
    mobileMenu.classList.add('opacity-0');

    mobileMenuContainer.classList.remove('translate-x-0');
    mobileMenuContainer.classList.add('ltr:translate-x-full', 'rtl:-translate-x-full');
    mobileMenuContainer.setAttribute('aria-hidden', 'true');
    mobileMenuToggle?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('overflow-hidden');

    if (returnFocus && mobileMenuToggle && document.contains(mobileMenuToggle)) {
      mobileMenuToggle.focus({ preventScroll: true });
    }

    mobileMenuCloseTimer = setTimeout(() => {
      mobileMenu.classList.add('invisible');
      mobileMenuCloseTimer = null;
    }, 500);
  }

  mobileMenuToggle?.addEventListener('click', () => {
    if (mobileMenu?.classList.contains('invisible')) {
      openMobileMenu();
      return;
    }

    closeMobileMenu();
  });

  mobileMenu?.addEventListener('click', (event) => {
    if (event.target === mobileMenu) {
      closeMobileMenu();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && isMobileMenuOpen()) {
      closeMobileMenu();
    }
  });

  mobileMenuContainer?.querySelectorAll('a[href]').forEach((link) => {
    link.addEventListener('click', () => closeMobileMenu());
  });
