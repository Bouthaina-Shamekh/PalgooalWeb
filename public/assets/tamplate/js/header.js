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
