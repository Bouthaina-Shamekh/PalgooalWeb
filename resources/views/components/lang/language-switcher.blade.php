@props(['variant' => 'front']) {{-- front | builder --}}
@php
    use Illuminate\Support\Facades\View;

    $currentRoute   = request()->route()->getName();
    $slug           = request()->route('slug');
    $currentLocale  = app()->getLocale();
    $sharedPage     = View::shared('currentPage', null);

    // كلاس الزر الرئيسي حسب المكان
    $buttonClass = $variant === 'builder'
        ? 'flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-semibold text-slate-700 bg-slate-100 rounded-full hover:bg-slate-200'
        : 'flex items-center gap-1 text-primary dark:text-white font-semibold hover:text-secondary dark:hover:text-yellow-400 text-sm';

    // كلاس قائمة اللغات
    $menuClass = $variant === 'builder'
        ? 'absolute mt-2 w-40 bg-white border border-slate-200 rounded-xl shadow-lg z-40 py-1 rtl:right-0 rtl:left-auto ltr:left-0 ltr:right-auto'
        : 'absolute left-0 mt-2 w-28 bg-white dark:bg-[#2c2c2c] border border-gray-200 dark:border-gray-700 rounded-md shadow-md z-40';

    // كلاس العنصر داخل القائمة
    $itemClass = $variant === 'builder'
        ? 'block w-full text-right px-3 py-1.5 text-[12px] hover:bg-slate-100 rounded-lg'
        : 'block w-full text-right px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-white/20';
@endphp

<div class="relative group" id="lang-container">
    <button id="lang-switch"
        class="{{ $buttonClass }}"
        type="button"
        aria-haspopup="true"
        aria-controls="lang-menu">
        @if($variant === 'builder')
            <span class="uppercase tracking-wide">{{ $currentLanguage?->code ?? strtoupper($currentLocale) }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-slate-500" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 21a9 9 0 100-18 9 9 0 000 18z" />
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M3.6 9h16.8M3.6 15h16.8M12 3c2.5 2.6 3.9 5.6 3.9 9s-1.4 6.4-3.9 9c-2.5-2.6-3.9-5.6-3.9-9S9.5 5.6 12 3z" />
            </svg>
        @else
            🌐
            @if($currentLanguage?->flag)
                <img src="{{ asset($currentLanguage->flag) }}" alt="{{ $currentLanguage->native }}" class="inline w-4 h-4 mr-1">
            @endif
            {{ $currentLanguage?->native ?? strtoupper($currentLocale) }}
        @endif
    </button>

    <div id="lang-menu"
         class="{{ $menuClass }} opacity-0 invisible group-hover:opacity-100 group-hover:visible md:transition-all md:duration-200">

        @foreach($languages as $lang)
            @php
                $redirectUrl = '#';

                if ($sharedPage && $lang->code !== $currentLocale) {
                    $translatedPage = $sharedPage->translations->firstWhere('locale', $lang->code);
                    $translatedSlug = $translatedPage?->slug;

                    if ($sharedPage->is_home) {
                        $redirectUrl = url('/') . '?change-locale=' . $lang->code;
                    } elseif ($translatedSlug) {
                        $redirectUrl = url($translatedSlug) . '?change-locale=' . $lang->code;
                    } else {
                        $redirectUrl = route('change_locale', ['locale' => $lang->code]);
                    }
                } elseif ($currentRoute === 'template.show' && $slug && $lang->code !== $currentLocale) {
                    $template = \App\Models\Template::with('translations')->whereHas('translations', function ($q) use ($slug) {
                        $q->where('slug', $slug);
                    })->first();

                    $translatedSlug = $template?->translations->firstWhere('locale', $lang->code)?->slug;

                    if ($translatedSlug) {
                        $redirectUrl = route('template.show', ['slug' => $translatedSlug]) . '?change-locale=' . $lang->code;
                    }
                } elseif ($lang->code !== $currentLocale) {
                    $redirectUrl = route('change_locale', ['locale' => $lang->code]);
                }
            @endphp

            <a href="{{ $redirectUrl }}"
               class="{{ $itemClass }} {{ $lang->code === $currentLocale ? 'bg-slate-100 dark:bg-white/10 font-bold' : '' }}">
                @if($variant !== 'builder' && $lang->flag)
                    <img src="{{ asset($lang->flag) }}" alt="{{ $lang->native }}" class="inline w-4 h-4 mr-1">
                @endif
                {{ $lang->native }}
            </a>
        @endforeach

    </div>
</div>
