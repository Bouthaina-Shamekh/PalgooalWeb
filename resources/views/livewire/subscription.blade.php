<div>
    <div class="alert alert-{{ $alertType }}  justify-between items-center {{ $alert === false ? 'hidden' : 'flex' }}">
        {{ $alertMessage }}
        <button type="button" class="btn-close" wire:click="closeModal">
            <span class="pc-micon">
                <i class="material-icons-two-tone pc-icon">close</i>
            </span>
        </button>
    </div>
    @if ($mode === 'index')
        @include('livewire.dashboard.subscription.index')
    @elseif ($mode === 'add')
        @include('livewire.dashboard.subscription.add')
    @elseif ($mode === 'edit')
        @include('livewire.dashboard.subscription.edit')
    @endif
</div>
