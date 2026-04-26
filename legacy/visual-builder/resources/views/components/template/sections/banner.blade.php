<!-- Banner Section -->
<section class="relative bg-primary py-20 px-4 sm:px-8 lg:px-24 shadow-md text-white overflow-hidden">
    <!-- المحتوى -->
    <div class="relative z-10 max-w-4xl mx-auto text-center">
        <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-snug drop-shadow-lg mb-4">
            {{ $data['title'] ?? 'عنوان الترحيب' }}
        </h1>
        <p class="text-lg sm:text-xl font-light text-white/90 max-w-3xl mx-auto">
            {{ $data['subtitle'] ?? 'نص ترحيبي مختصر' }}
        </p>
        <!-- Breadcrumb -->
        <x-breadcrumb />

    </div>
</section>
