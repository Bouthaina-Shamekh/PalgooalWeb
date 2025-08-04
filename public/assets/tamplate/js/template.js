
  const categoryButtons = document.querySelectorAll('#categoryFilter button');
  const sortSelect = document.getElementById('sortSelect');
  const priceRange = document.getElementById('priceRange');
  const priceValue = document.getElementById('priceValue');
  const resultCount = document.getElementById('resultCount');
  const templateGrid = document.getElementById('templateGrid');
  const noResults = document.getElementById('noResults');
  const loadMoreBtn = document.getElementById('loadMoreBtn');

  const templates = [
    { id: 1, title: 'قالب متجر ملابس', category: 'المتاجر', price: 56, oldPrice: 96 },
    { id: 2, title: 'قالب مطعم عصري', category: 'منيو مطاعم', price: 80, oldPrice: 100 },
    { id: 3, title: 'قالب الكترونيات', category: 'المتاجر', price: 120, oldPrice: 140 },
    { id: 4, title: 'قالب منيو برجر', category: 'منيو مطاعم', price: 60, oldPrice: 75 },
    { id: 5, title: 'قالب متجر أدوات منزلية', category: 'المتاجر', price: 90, oldPrice: 110 },
    { id: 6, title: 'قالب مطعم شرقي', category: 'منيو مطاعم', price: 70, oldPrice: 95 },
    { id: 7, title: 'قالب متجر أحذية', category: 'المتاجر', price: 105, oldPrice: 125 },
    { id: 8, title: 'قالب مشاوي', category: 'منيو مطاعم', price: 66, oldPrice: 85 },
    { id: 9, title: 'قالب عبايات', category: 'المتاجر', price: 99, oldPrice: 130 },
    { id: 10, title: 'قالب منيو إيطالي', category: 'منيو مطاعم', price: 77, oldPrice: 100 }
  ];

  let currentCategory = 'all';
  let currentSort = 'default';
  let maxPrice = 250;
  let visibleCount = 6;

  function renderTemplates() {
    templateGrid.innerHTML = '';
    let filtered = templates
      .filter(t => currentCategory === 'all' || t.category === currentCategory)
      .filter(t => t.price <= maxPrice);

    if (currentSort === 'low') filtered.sort((a, b) => a.price - b.price);
    if (currentSort === 'high') filtered.sort((a, b) => b.price - a.price);

    const visibleTemplates = filtered.slice(0, visibleCount);
    resultCount.textContent = `عرض ${visibleTemplates.length} من ${filtered.length} نتيجة`;
    noResults.classList.toggle('hidden', visibleTemplates.length > 0);
    loadMoreBtn.classList.toggle('hidden', visibleTemplates.length >= filtered.length);

    visibleTemplates.forEach(t => {
      templateGrid.innerHTML += `
      
      <a href="/dist/template/single-template.html" class="block group">
        <article style="will-change: transform" class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden relative group transition-transform duration-300 hover:-translate-y-2 hover:shadow-2xl animate-fade-in-up border border-primary/10" itemscope itemtype="https://schema.org/Product" lang="ar">
          <meta itemprop="name" content="${t.title}">
          <meta itemprop="description" content="وصف مختصر للقالب">
          <meta itemprop="sku" content="template-${t.id}">
          <meta itemprop="category" content="قوالب مواقع">
          <meta itemprop="brand" content="Palgoals">
          <meta itemprop="priceCurrency" content="USD" />
          <meta itemprop="price" content="${t.price}" />
          <meta itemprop="availability" content="https://schema.org/InStock" />
          <div class="relative">
            <img itemprop="image" src="./assets/images/2-1-1.webp" alt="${t.title}" class="w-full h-40 object-cover transition-transform duration-300 group-hover:scale-105 group-hover:brightness-95" loading="lazy" decoding="async">
            <div class="bg-gradient-to-tr from-secondary to-primary text-white flex items-end justify-center w-24 h-10 absolute -top-2 rtl:-left-10 ltr:-right-10 ltr:rotate-[40deg] rtl:rotate-[320deg] animate-bounce shadow-lg font-bold text-base tracking-wide">جديد</div>
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition duration-300"></div>
            <div class="absolute top-2 right-2 rtl:right-auto rtl:left-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <button class="bg-white/80 dark:bg-white/20 hover:bg-primary text-primary hover:text-white rounded-full p-2 shadow-md transition" title="معاينة القالب" aria-label="معاينة القالب">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0c0 5-9 9-9 9s-9-4-9-9a9 9 0 0118 0z"/></svg>
              </button>
              <button class="bg-white/80 dark:bg-white/20 hover:bg-secondary text-secondary hover:text-white rounded-full p-2 shadow-md transition" title="شراء القالب" aria-label="شراء القالب">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.35 2.7A1 1 0 007 17h10a1 1 0 00.95-.68L19 13M7 13V6a1 1 0 011-1h5a1 1 0 011 1v7"/></svg>
              </button>
            </div>
          </div>
          <div class="p-5 rtl:text-right ltr:text-left flex flex-col items-start">
            <h3 itemprop="name" class="text-suptitle font-bold mb-1 text-primary/90 dark:text-white group-hover:text-secondary transition-colors leading-snug">${t.title}</h3>
            <p itemprop="description" class="text-suptitle font-light mb-2 text-primary/70 dark:text-gray-300">وصف مختصر للقالب</p>
            <div class="flex justify-between items-center text-sm font-bold rtl:flex-row-reverse ltr:flex-row mt-3 w-full">
              <div class="flex items-center gap-1" aria-label="التقييم 4 من 5 نجوم">
                <span class="text-yellow-400 text-base">★★★★☆</span>
              </div>
              <div class="flex items-center gap-2 rtl:flex-row-reverse ltr:flex-row">
                <span class="line-through text-suptitle text-primary/40 dark:text-gray-400">$${t.oldPrice}</span>
                <span itemprop="price" class="text-title-h3 text-secondary dark:text-yellow-400">$${t.price}</span>
              </div>
            </div>
          </div>
        </article>
        </a>
      `;
    });
  }

  categoryButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      currentCategory = btn.dataset.category;
      visibleCount = 6;
      renderTemplates();
    });
  });

  sortSelect.addEventListener('change', e => {
    currentSort = e.target.value;
    visibleCount = 6;
    renderTemplates();
  });

  priceRange.addEventListener('input', e => {
    maxPrice = parseInt(e.target.value);
    priceValue.textContent = maxPrice;
    visibleCount = 6;
    renderTemplates();
  });

  loadMoreBtn.addEventListener('click', () => {
    visibleCount += 6;
    renderTemplates();
  });

  document.addEventListener('DOMContentLoaded', renderTemplates);
