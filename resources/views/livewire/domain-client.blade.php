<div>
    <div class="alert alert-{{ $alertType }} justify-between items-center {{ $alert === false ? 'hidden' : 'flex' }}">
        {{ $alertMessage }}
        <button type="button" class="btn-close" wire:click="closeModal">
            <span class="pc-micon">
                <i class="material-icons-two-tone pc-icon">close</i>
            </span>
        </button>
    </div>

    @if ($mode === 'search')
        @include('livewire.dashboard.domain-client.search')
    @endif

    @if ($mode === 'buy')
        @include('livewire.dashboard.domain-client.buy')
    @endif

    @if ($mode === 'index')
        @include('livewire.dashboard.domain-client.index')
    @endif
</div>
