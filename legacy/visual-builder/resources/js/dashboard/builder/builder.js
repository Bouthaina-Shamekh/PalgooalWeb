document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('builder-settings-btn');
    const menu = document.getElementById('builder-settings-menu');

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('hidden');
    });

    document.addEventListener('click', () => {
        menu.classList.add('hidden');
    });
});


document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const btn = document.getElementById('btnSidebar');
    const arrow = btn.querySelector('svg');

    const STORAGE_KEY = 'pg_sidebar_collapsed';
    const COLLAPSED_CLASS = 'sidebar-collapsed';

    const OPEN_CLASSES = ['-right-2.25', 'rtl:-left-2.25'];
    const CLOSED_CLASSES = ['-right-3', 'rtl:-left-3'];

    function setBtnPos(collapsed) {
        if (collapsed) {
            btn.classList.remove(...OPEN_CLASSES);
            btn.classList.add(...CLOSED_CLASSES);
        } else {
            btn.classList.remove(...CLOSED_CLASSES);
            btn.classList.add(...OPEN_CLASSES);
        }
    }

    function setCollapsed(collapsed) {
        sidebar.classList.toggle(COLLAPSED_CLASS, collapsed);

        if (collapsed) {
            sidebar.style.width = '0';
            sidebar.style.minWidth = '0';
            arrow.style.transform = 'rotate(180deg)';
        } else {
            sidebar.style.width = '';
            sidebar.style.minWidth = '';
            arrow.style.transform = 'rotate(0deg)';
        }
        setBtnPos(collapsed);
        localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
    }

    // استرجاع الحالة
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === '1') setCollapsed(true);

    btn.addEventListener('click', () => {
        const collapsed = sidebar.classList.contains(COLLAPSED_CLASS);
        setCollapsed(!collapsed);
    });
});


export function initSidebarResize() {
  const sidebar = document.getElementById('sidebar');
  const resizer = document.getElementById('sidebar-resizer');
  if (!sidebar || !resizer) return;

  const STORAGE_KEY = 'pg_sidebar_width';

  const isRTL =
    document.documentElement.dir === 'rtl' ||
    document.body.dir === 'rtl';

  const savedWidth = localStorage.getItem(STORAGE_KEY);
  if (savedWidth) {
    sidebar.style.width = savedWidth + 'px';
    sidebar.style.minWidth = savedWidth + 'px';
  }

  let isResizing = false;
  let pendingWidth = null;

  const min = 260;
  const max = 600;

  function applyWidth() {
    if (pendingWidth == null) return;

    sidebar.style.width = pendingWidth + 'px';
    sidebar.style.minWidth = pendingWidth + 'px';

    pendingWidth = null;
  }

  function smoothApply(width) {
    pendingWidth = width;
    requestAnimationFrame(applyWidth);
  }

  resizer.addEventListener('mousedown', () => {
    isResizing = true;
    document.body.style.cursor = 'col-resize';
  });

  document.addEventListener('mousemove', (e) => {
    if (!isResizing) return;

    let width;

    if (isRTL) {
      // الحساب من الجهة اليمنى
      width = window.innerWidth - e.clientX;
    } else {
      width = e.clientX;
    }

    width = Math.max(min, Math.min(max, width));
    smoothApply(width);
  });

  document.addEventListener('mouseup', () => {
    if (!isResizing) return;

    isResizing = false;
    document.body.style.cursor = '';

    localStorage.setItem(STORAGE_KEY, sidebar.offsetWidth);
  });
}


initSidebarResize()