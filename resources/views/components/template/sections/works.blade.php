<section class="py-24 px-4 sm:px-10 lg:px-20" dir="auto">
    <!-- العنوان -->
    <div class="text-center max-w-3xl mx-auto mb-16">
        <h2 class="text-4xl md:text-5xl font-extrabold text-primary mb-4 font-almarai tracking-tight">
            {{ $data['title'] ?? 'عنوان غير متوفر' }}
        </h2>
        <p class="text-lg md:text-xl text-tertiary font-cairo leading-relaxed">
            {{ $data['subtitle'] ?? 'وصف غير متوفر' }}
        </p>
    </div>
    <!-- الفلاتر -->
    <div class="flex flex-wrap justify-center gap-3 mb-12">
        <button onclick="filterProjects('all')"
            class="filter-btn bg-primary text-white px-6 py-2 rounded-full text-base font-semibold shadow transition-all duration-200 hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-secondary">الكل</button>
        <button onclick="filterProjects('store')"
            class="filter-btn bg-gray-100 text-gray-800 px-6 py-2 rounded-full text-base font-semibold transition-all duration-200 hover:bg-secondary hover:text-white focus:outline-none focus:ring-2 focus:ring-secondary">متاجر</button>
        <button onclick="filterProjects('website')"
            class="filter-btn bg-gray-100 text-gray-800 px-6 py-2 rounded-full text-base font-semibold transition-all duration-200 hover:bg-secondary hover:text-white focus:outline-none focus:ring-2 focus:ring-secondary">مواقع</button>
        <button onclick="filterProjects('app')"
            class="filter-btn bg-gray-100 text-gray-800 px-6 py-2 rounded-full text-base font-semibold transition-all duration-200 hover:bg-secondary hover:text-white focus:outline-none focus:ring-2 focus:ring-secondary">تطبيقات</button>
    </div>
    <!-- الشبكة -->
    <div id="projects-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-10">
        <!-- قالب البطاقة (للاستخدام مع JS إذا أردت) -->
        <template id="project-card-template">
            <div class="project-card group bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition-transform duration-300 hover:-translate-y-2 border border-gray-100"
                data-category="">
                <div class="relative">
                    <img src="../assets/images/Group 34130.jpg" alt=""
                        class="w-full h-56 object-cover group-hover:opacity-90 transition duration-300">
                    <span
                        class="absolute top-3 left-3 bg-secondary text-white text-xs px-3 py-1 rounded-full font-bold shadow tag"></span>
                </div>
                <div class="p-6 flex flex-col h-full">
                    <h3
                        class="text-xl font-extrabold text-primary mb-2 group-hover:text-secondary transition-colors title">
                    </h3>
                    <p class="text-gray-600 text-sm mb-4 leading-relaxed desc"></p>
                    <a href="#"
                        class="mt-auto inline-block text-sm font-semibold text-secondary hover:underline">مشاهدة
                        المشروع</a>
                </div>
            </div>
        </template>
        <!-- بطاقات المشاريع -->
        @php
            $works = App\Models\Portfolio::get();
        @endphp
        @foreach ($works as $work)
        <div class="project-card group bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition-transform duration-300 hover:-translate-y-2 border border-gray-100"
            data-category="store" data-visible="true">
            <div class="relative">
                <img src="{{ asset('storage/' . $work->default_image) }}" alt="متجر إلكتروني 1"
                    class="w-full h-56 object-cover group-hover:opacity-90 transition duration-300">
                <span
                    class="absolute top-3 left-3 bg-secondary text-white text-xs px-3 py-1 rounded-full font-bold shadow">
                {{ $work->translations->firstWhere('locale', app()->getLocale())->type }}</span>
            </div>
            <div class="p-6 flex flex-col h-full">
                <h3 class="text-xl font-extrabold text-primary mb-2 group-hover:text-secondary transition-colors">
                    {{ $work->translations->firstWhere('locale', app()->getLocale())->title }}</h3>
                <p class="text-gray-600 text-sm mb-4">{{ $work->translations->firstWhere('locale', app()->getLocale())->description }}</p>
                <a href="/works/{{ $work->id }}" class="mt-auto text-sm font-semibold text-secondary hover:underline">مشاهدة
                    المشروع</a>
            </div>
        </div>
        @endforeach
        {{-- <!-- بطاقات مخفية (تحميل المزيد) -->
        <div class="project-card group bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition-transform duration-300 hover:-translate-y-2 border border-gray-100 hidden"
            data-category="store" data-visible="false">
            <div class="relative">
                <img src="https://via.placeholder.com/600x400/999/222?text=Store+3" alt="متجر 3"
                    class="w-full h-56 object-cover group-hover:opacity-90 transition duration-300">
                <span
                    class="absolute top-3 left-3 bg-secondary text-white text-xs px-3 py-1 rounded-full font-bold shadow">متجر</span>
            </div>
            <div class="p-6 flex flex-col h-full">
                <h3 class="text-xl font-extrabold text-primary mb-2 group-hover:text-secondary transition-colors">
                    متجر إلكتروني 3</h3>
                <p class="text-gray-600 text-sm mb-4">متجر منتجات رقمية مع نظام عضويات متقدم.</p>
                <a href="#" class="mt-auto text-sm font-semibold text-secondary hover:underline">مشاهدة
                    المشروع</a>
            </div>
        </div>
        <div class="project-card group bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition-transform duration-300 hover:-translate-y-2 border border-gray-100 hidden"
            data-category="website" data-visible="false">
            <div class="relative">
                <img src="https://via.placeholder.com/600x400/888/111?text=Website+3" alt="موقع 3"
                    class="w-full h-56 object-cover group-hover:opacity-90 transition duration-300">
                <span
                    class="absolute top-3 left-3 bg-primary text-white text-xs px-3 py-1 rounded-full font-bold shadow">موقع</span>
            </div>
            <div class="p-6 flex flex-col h-full">
                <h3 class="text-xl font-extrabold text-primary mb-2 group-hover:text-secondary transition-colors">
                    موقع إلكتروني 3</h3>
                <p class="text-gray-600 text-sm mb-4">موقع شخصي لمحترف أعمال مع معرض أعمال ديناميكي.</p>
                <a href="#" class="mt-auto text-sm font-semibold text-secondary hover:underline">مشاهدة
                    المشروع</a>
            </div>
        </div>
        <div class="project-card group bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition-transform duration-300 hover:-translate-y-2 border border-gray-100 hidden"
            data-category="app" data-visible="false">
            <div class="relative">
                <img src="https://via.placeholder.com/600x400/777/000?text=App+3" alt="تطبيق 3"
                    class="w-full h-56 object-cover group-hover:opacity-90 transition duration-300">
                <span
                    class="absolute top-3 left-3 bg-tertiary text-white text-xs px-3 py-1 rounded-full font-bold shadow">تطبيق</span>
            </div>
            <div class="p-6 flex flex-col h-full">
                <h3 class="text-xl font-extrabold text-primary mb-2 group-hover:text-secondary transition-colors">
                    تطبيق جوال 3</h3>
                <p class="text-gray-600 text-sm mb-4">تطبيق تعليمي تفاعلي للأطفال مع ألعاب تعليمية.</p>
                <a href="#" class="mt-auto text-sm font-semibold text-secondary hover:underline">مشاهدة
                    المشروع</a>
            </div>
        </div>
        <div class="project-card group bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition-transform duration-300 hover:-translate-y-2 border border-gray-100 hidden"
            data-category="store" data-visible="false">
            <div class="relative">
                <img src="https://via.placeholder.com/600x400/666/fff?text=Store+4" alt="متجر 4"
                    class="w-full h-56 object-cover group-hover:opacity-90 transition duration-300">
                <span
                    class="absolute top-3 left-3 bg-secondary text-white text-xs px-3 py-1 rounded-full font-bold shadow">متجر</span>
            </div>
            <div class="p-6 flex flex-col h-full">
                <h3 class="text-xl font-extrabold text-primary mb-2 group-hover:text-secondary transition-colors">
                    متجر إلكتروني 4</h3>
                <p class="text-gray-600 text-sm mb-4">متجر أدوات منزلية مع نظام كوبونات وعروض.</p>
                <a href="#" class="mt-auto text-sm font-semibold text-secondary hover:underline">مشاهدة
                    المشروع</a>
            </div>
        </div>
        <div class="project-card group bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition-transform duration-300 hover:-translate-y-2 border border-gray-100 hidden"
            data-category="website" data-visible="false">
            <div class="relative">
                <img src="https://via.placeholder.com/600x400/555/eee?text=Website+4" alt="موقع 4"
                    class="w-full h-56 object-cover group-hover:opacity-90 transition duration-300">
                <span
                    class="absolute top-3 left-3 bg-primary text-white text-xs px-3 py-1 rounded-full font-bold shadow">موقع</span>
            </div>
            <div class="p-6 flex flex-col h-full">
                <h3 class="text-xl font-extrabold text-primary mb-2 group-hover:text-secondary transition-colors">
                    موقع إلكتروني 4</h3>
                <p class="text-gray-600 text-sm mb-4">موقع إخباري متجاوب مع لوحة تحكم متقدمة.</p>
                <a href="#" class="mt-auto text-sm font-semibold text-secondary hover:underline">مشاهدة
                    المشروع</a>
            </div>
        </div>
        <div class="project-card group bg-white rounded-2xl shadow-lg hover:shadow-2xl overflow-hidden transition-transform duration-300 hover:-translate-y-2 border border-gray-100 hidden"
            data-category="app" data-visible="false">
            <div class="relative">
                <img src="https://via.placeholder.com/600x400/444/ccc?text=App+4" alt="تطبيق 4"
                    class="w-full h-56 object-cover group-hover:opacity-90 transition duration-300">
                <span
                    class="absolute top-3 left-3 bg-tertiary text-white text-xs px-3 py-1 rounded-full font-bold shadow">تطبيق</span>
            </div>
            <div class="p-6 flex flex-col h-full">
                <h3 class="text-xl font-extrabold text-primary mb-2 group-hover:text-secondary transition-colors">
                    تطبيق جوال 4</h3>
                <p class="text-gray-600 text-sm mb-4">تطبيق متابعة مهام للشركات مع تقارير فورية.</p>
                <a href="#" class="mt-auto text-sm font-semibold text-secondary hover:underline">مشاهدة
                    المشروع</a>
            </div>
        </div> --}}
    </div>
    <!-- زر تحميل المزيد -->
    <div class="mt-14 text-center">
        <button onclick="loadMoreProjects()" id="loadMoreBtn"
            class="px-8 py-3 bg-secondary text-white text-base font-semibold rounded-full shadow-lg hover:bg-primary transition duration-200 focus:outline-none focus:ring-2 focus:ring-primary">
            تحميل المزيد
        </button>
    </div>
</section>


<!-- Removed duplicate filterProjects function to prevent JavaScript errors -->
<script>
    function filterProjects(category) {
        const allCards = document.querySelectorAll('.project-card');

        allCards.forEach(card => {
            const type = card.dataset.category;
            const isVisible = !card.classList.contains('hidden');

            if (category === 'all' || type === category) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });

        // إعادة تفعيل زر تحميل المزيد فقط إذا كنا نظهر كل العناصر
        if (category === 'all') {
            document.getElementById('loadMoreBtn')?.classList.remove('hidden');
        } else {
            document.getElementById('loadMoreBtn')?.classList.add('hidden');
        }
    }

    function loadMoreProjects() {
        const hiddenCards = document.querySelectorAll('.project-card[data-visible="false"]');
        for (let i = 0; i < 4 && i < hiddenCards.length; i++) {
            hiddenCards[i].classList.remove('hidden');
            hiddenCards[i].dataset.visible = 'true';
        }

        if (document.querySelectorAll('.project-card[data-visible="false"]').length === 0) {
            document.getElementById('loadMoreBtn').classList.add('hidden');
        }
    }
</script>
