<section class="bg-primary py-16 text-center text-white">
  <div class="container mx-auto px-4 max-w-4xl">
    <h1 class="text-2xl md:text-4xl font-extrabold mb-4">ابحث عن اسم دومين</h1>
    <p class="text-sm md:text-base text-white/80 mb-8">
      اكتشف دومينك الفريد واشتريه وسجله باستخدام أداة البحث
    </p>

    <div class="flex flex-col sm:flex-row justify-center items-center gap-3 mb-8">
      <a href="#" class="inline-flex items-center gap-2 bg-secondary hover:bg-secondary/30 transition-colors text-white font-semibold px-6 py-3 rounded-full">
        ✨ مولد أسماء الدومينات بالـAI
      </a>
      <a href="#" class="inline-flex items-center gap-2 bg-white text-[#4C1D95] hover:bg-gray-100 transition-colors font-semibold px-6 py-3 rounded-full">
        البحث عن دومين
      </a>
    </div>

    <!-- Search input with button -->
    <div class="relative max-w-xl mx-auto will-change-transform">
      <input id="domainInput" type="text" placeholder="ابحث عن دومين"
        class="w-full text-right py-3 px-4 pr-10 rounded-lg text-white bg-[#2F1A53] placeholder-white/70 border border-white/30 focus:outline-none focus:ring-2 focus:ring-[#7C3AED] transition-shadow duration-200 shadow-inner" />
      <button id="searchButton"
        class="absolute left-2 top-1/2 -translate-y-1/2 bg-secondary hover:bg-secondary/30 text-white px-4 py-2 rounded-lg">
        بحث
      </button>
    </div>

    <div id="searchResults" class="mt-12"></div>
  </div>
</section>
<script>
  const tlds = ['com', 'net', 'org', 'shop', 'xyz', 'rocks', 'news', 'live', 'ninja', 'watches'];
  const prices = {
    com: 12.5,
    net: 13.99,
    org: 13.5,
    shop: 9.99,
    xyz: 1.99,
    rocks: 20.0,
    news: 30.0,
    live: 30.0,
    ninja: 28.0,
    watches: 266.0
  };
  const newTLDs = ['news', 'rocks', 'live', 'watches', 'ninja'];

  const searchInput = document.getElementById('domainInput');
  const searchBtn = document.getElementById('searchButton');
  const resultsContainer = document.getElementById('searchResults');

  searchInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      searchBtn.click();
    }
  });

  searchBtn.addEventListener('click', function () {
    const domain = searchInput.value.trim();
    if (!domain) {
      resultsContainer.innerHTML = '<p class="text-red-400 mt-4">الرجاء إدخال اسم الدومين.</p>';
      return;
    }

    resultsContainer.innerHTML = `
      <div class="flex justify-center items-center mt-10">
        <div class="w-10 h-10 border-4 border-white/30 border-t-white rounded-full animate-spin"></div>
      </div>
    `;

    Promise.all(tlds.map(tld =>
      fetch(`check-domain.php?sld=${domain}&tld=${tld}`)
        .then(res => res.text())
        .then(xmlText => {
          const parser = new DOMParser();
          const xml = parser.parseFromString(xmlText, 'application/xml');
          const rspCode = xml.querySelector('RRPCode')?.textContent;
          const isAvailable = rspCode === '210';
          return {
            tld,
            isAvailable,
            price: prices[tld] || 9.99,
            isNew: newTLDs.includes(tld)
          };
        }).catch(() => ({
          tld,
          isAvailable: false,
          price: prices[tld] || 9.99
        }))
    )).then(results => {
      const html = `
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4 mt-8">
          ${results.map(result => `
            <div class="border ${result.isAvailable ? 'border-green-500 bg-green-600/10' : 'border-red-400 bg-red-600/10'} 
                        rounded-lg p-4 text-center relative shadow-md hover:scale-[1.02] hover:shadow-xl transition-transform duration-300">
              ${result.isNew ? `<span class="absolute top-0 end-0 bg-green-500 text-xs text-white px-2 py-1 rounded-bl-lg font-bold">NEW</span>` : ''}
              <div class="text-lg font-bold text-white mb-1">.${result.tld}</div>
              <div class="text-sm text-white/70 mb-3">
                from: <span class="font-medium text-white">$${result.price.toFixed(2)}</span>
              </div>
              ${result.isAvailable
                ? `<button class="w-full bg-indigo-900 text-white text-sm font-semibold py-2 rounded hover:bg-indigo-700 transition">Add</button>`
                : `<button class="w-full bg-gray-500 text-white/70 text-sm font-semibold py-2 rounded cursor-not-allowed" disabled>Taken</button>`}
            </div>
          `).join('')}
        </div>
      `;
      resultsContainer.innerHTML = `<section class="bg-primary py-12 text-white">${html}</section>`;
    });
  });
</script>