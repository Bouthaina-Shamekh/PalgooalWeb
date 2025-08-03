
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
    // resultCount.textContent = `{{ t(Frontend.show, show) }}${visibleTemplates.length} من ${filtered.length} `;
    noResults.classList.toggle('hidden', visibleTemplates.length > 0);
    loadMoreBtn.classList.toggle('hidden', visibleTemplates.length >= filtered.length);

    visibleTemplates.forEach(t => {
      templateGrid.innerHTML += `

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
