<a class="pc-head-link dropdown-toggle me-0" data-pc-toggle="dropdown" href="#" role="button" aria-haspopup="false"
    aria-expanded="false">

    @if($currentLanguage?->flag)
        <img src="{{ asset($currentLanguage->flag) }}" alt="{{ $currentLanguage->native }}" class="inline w-4 h-4 mr-1">
    @else
        🌐
    @endif
    {{ $currentLanguage?->native ?? $currentLocale }}
</a>
<div class="dropdown-menu dropdown-menu-end pc-h-dropdown">

    @foreach($languages as $lang)
        <a href="{{ $lang->code !== $currentLocale ? route('change_locale', ['locale' => $lang->code]) : '#' }}"
            class="dropdown-item {{ $lang->code === $currentLocale ? 'aria-current=page' : '' }}">

            @if($lang->flag)
                <img src="{{ asset($lang->flag) }}" alt="{{ $lang->native }}" class="inline w-4 h-4 mr-1">
            @endif

            {{ $lang->native }}
        </a>
    @endforeach
</div>
