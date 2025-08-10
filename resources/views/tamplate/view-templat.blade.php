<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ current_dir() }}" class="h-full overflow-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>معاينة: {{ $translation->name }}</title>
    
    <!-- Tailwind CSS via CDN -->
    <link rel="stylesheet" href="{{ asset('assets/tamplate/css/app.css') }}">
    
    <!-- Alpine.js for UI interactivity -->
    <script src="//unpkg.com/alpinejs" defer></script>
    
    <!-- Custom CSS for minor adjustments -->
    <style>
        /* Basic reset and iframe styling */
        body { margin: 0; }
        iframe { border: none; }

        /* Style for the active device switcher button */
        .device-switcher button.active {
            background-color: white;
            color: #111827; /* gray-900 */
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1 ), 0 1px 2px -1px rgb(0 0 0 / 0.1);
        }
        .dark .device-switcher button.active {
            background-color: #1f2937; /* gray-800 */
            color: #f9fafb; /* gray-50 */
        }
    </style>
</head>

<!-- 
  Key layout strategy:
  - The body is a CSS Grid container that fills the entire screen height (h-full).
  - It's divided into two rows: 
    1. `auto`: The header takes up only the space it needs.
    2. `1fr`: The main content area takes up all the remaining fractional space.
  This is the most reliable method for full-screen layouts.
-->
<body class="h-full bg-gray-200 dark:bg-gray-700 grid grid-rows-[auto_1fr] font-Cairo" x-data="{ device: 'desktop' }">

    <!-- Header: First row of the grid -->
    <header class="bg-white dark:bg-gray-800 shadow-lg sticky top-0 z-50 h-16 flex items-center flex-shrink-0">
        <div class="w-full px-4 flex justify-between items-center">
            
            <!-- Right Side: Logo & Template Name -->
            <div class="flex items-center gap-4">
                <a href="{{ route('template.show', $translation->slug) }}" title="العودة لصفحة المنتج">
                    <!-- Replace with your actual logo URL -->
                    <img src="{{ asset('assets/tamplate/images/logo.svg') }}" alt="شركة بال قول " class="h-8 w-auto">
                </a>
                <div class="hidden md:block border-r border-gray-200 dark:border-gray-600 h-8 mx-2"></div>
                <h1 class="hidden md:block text-lg font-bold text-gray-800 dark:text-white">{{ $translation->name }}</h1>
            </div>

            <!-- Center: Device Switcher -->
            <div class="device-switcher flex items-center gap-1 p-1 bg-gray-100 dark:bg-gray-900/50 rounded-full">
                <button @click="device = 'desktop'" :class="{ 'active': device === 'desktop' }" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/80 transition-all" title="عرض سطح المكتب">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                </button>
                <button @click="device = 'tablet'" :class="{ 'active': device === 'tablet' }" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/80 transition-all" title="عرض التابلت">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </button>
                <button @click="device = 'mobile'" :class="{ 'active': device === 'mobile' }" class="p-2 rounded-full text-gray-500 dark:text-gray-400 hover:bg-white/60 dark:hover:bg-gray-800/80 transition-all" title="عرض الجوال">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                </button>
            </div>

            <!-- Left Side: Purchase & Close Buttons -->
            <div class="flex items-center gap-4">
                <a href="#" class="btn-primary">
                    🛒 اشترِ الآن
                </a>
                <a href="{{ route('template.show', $translation->slug) }}" title="إغلاق المعاينة" class="text-3xl text-gray-500 hover:text-red-500 transition">
                    &times;
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content: Second row of the grid -->
    <!-- 
      - `min-h-0` is a crucial fix for grid/flex children to prevent overflow.
      - `flex` and centering classes are for positioning the iframe container itself.
    -->
    <main class="min-h-0 w-full flex items-center justify-center transition-all duration-500 ease-in-out" 
          :class="device === 'desktop' ? 'p-0' : 'p-4 md:p-8'">
        
        <!-- This div is the "device" that changes size -->
        <div 
            class="bg-white rounded-xl shadow-2xl transition-all duration-500 ease-in-out"
            :class="{
                'w-full h-full rounded-none shadow-none': device === 'desktop',
                'w-[768px] h-full max-w-full': device === 'tablet',
                'w-[375px] h-full max-w-full': device === 'mobile'
            }"
        >
        @if($embedAllowed)
            <iframe 
                src="{{ $previewUrl }}" 
                class="w-full h-full"
                :class="{ 'rounded-xl': device !== 'desktop' }"
                title="معاينة حية: {{ $translation->name }}">
            </iframe>
            @else
            <div class="text-center px-6">
    <div class="max-w-xl mx-auto bg-white/70 dark:bg-gray-800/70 backdrop-blur rounded-2xl p-6 border">
      <h2 class="text-xl font-bold mb-2">لا يمكن عرض المعاينة داخل الصفحة</h2>
      <p class="text-gray-600 dark:text-gray-300 mb-4">
        هذا الموقع لا يسمح بالتضمين داخل iframe. افتح المعاينة في تبويب جديد.
      </p>
      <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
         class="inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-primary text-white font-bold hover:bg-primary/90">
        فتح المعاينة
      </a>
    </div>
  </div>
@endif
        </div>
    </main>

</body>
</html>
