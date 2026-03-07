@php
    $contact = $settings?->resolved_contact_info ?? [];
    $listClass = $listClass ?? 'space-y-3 text-sm';
    $itemClass = $itemClass ?? 'flex items-center gap-2';
    $linkClass = $linkClass ?? 'hover:opacity-80 transition-opacity duration-200';
@endphp

<ul class="{{ $listClass }}">
    @if (! empty($contact['phone']))
        <li class="{{ $itemClass }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M3 5a2 2 0 012-2h1.2a1 1 0 01.9.6l1.2 2.4a1 1 0 01-.2 1.1L7.5 9a16 16 0 006.5 6.5l1.5-1.5a1 1 0 011.1-.2l2.4 1.2a1 1 0 01.6.9V19a2 2 0 01-2 2h-1C9.4 21 3 14.6 3 7V6a2 2 0 012-1z" />
            </svg>
            <a href="tel:{{ $contact['phone'] }}" class="{{ $linkClass }}">{{ $contact['phone'] }}</a>
        </li>
    @endif

    @if (! empty($contact['email']))
        <li class="{{ $itemClass }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16c1.1 0 2 .9 2 2v12a2 2 0 01-2 2H4c-1.1 0-2-.9-2-2V6a2 2 0 012-2z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M22 6l-10 7L2 6" />
            </svg>
            <a href="mailto:{{ $contact['email'] }}" class="{{ $linkClass }}">{{ $contact['email'] }}</a>
        </li>
    @endif

    @if (! empty($contact['address']))
        <li class="{{ $itemClass }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 11a4 4 0 100-8 4 4 0 000 8z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14c-4.4 0-8 1.8-8 4v2h16v-2c0-2.2-3.6-4-8-4z" />
            </svg>
            <span>{{ $contact['address'] }}</span>
        </li>
    @endif
</ul>
