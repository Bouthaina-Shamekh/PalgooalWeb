<!-- Banner Section -->
<section class="bg-primary text-white py-28 px-4 sm:px-12 lg:px-36 flex flex-col items-center justify-center text-center overflow-hidden">
    <!-- المحتوى -->
    <div class="relative z-10 max-w-3xl mx-auto">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold font-almarai mb-6 drop-shadow-lg animate-fade-in">
            {{ $data['title'] ?? 'عنوان الترحيب' }}
        </h1>
        <p class="text-lg md:text-2xl text-gray-100/90 font-cairo mb-8 leading-relaxed animate-fade-in">
            {{ $data['subtitle'] ?? 'نص ترحيبي مختصر' }}
        </p>
        <!-- Breadcrumb -->
        <x-breadcrumb />

    </div>
</section>
