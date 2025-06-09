<!-- Language Switch -->
<div class="relative group" id="lang-container">
    <button id="lang-switch"
        class="flex items-center gap-1 text-primary dark:text-white font-semibold hover:text-secondary dark:hover:text-yellow-400 text-sm"
        aria-haspopup="true" aria-controls="lang-menu">
        🌐 
        @if($currentLanguage?->flag)
            <img src="{{ asset($currentLanguage->flag) }}" alt="{{ $currentLanguage->native }}" class="inline w-4 h-4 mr-1">
        @endif
        {{ $currentLanguage?->native ?? strtoupper($currentLocale) }}
    </button>

    <div id="lang-menu"
        class="absolute left-0 mt-2 w-28 bg-white dark:bg-[#2c2c2c] border border-gray-200 dark:border-gray-700 rounded-md shadow-md z-40 opacity-0 invisible group-hover:opacity-100 group-hover:visible md:transition-all md:duration-200">

        @foreach($languages as $lang)
            <a href="{{ $lang->code !== $currentLocale ? route('change_locale', ['locale' => $lang->code]) : '#' }}"
                class="block w-full text-right px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-white/20
                {{ $lang->code === $currentLocale ? 'aria-current=page' : '' }}">
                
                @if($lang->flag)
                    <img src="{{ asset($lang->flag) }}" alt="{{ $lang->native }}" class="inline w-4 h-4 mr-1">
                @endif

                {{ $lang->native }}
            </a>
        @endforeach

    </div>
</div>
