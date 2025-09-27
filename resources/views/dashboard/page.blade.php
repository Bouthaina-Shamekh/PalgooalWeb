<x-dashboard-layout> 
    <livewire:dashboard.pages />
    @include('dashboard.partials.media-picker', ['modalId' => 'pageMediaModal'])
</x-dashboard-layout>
