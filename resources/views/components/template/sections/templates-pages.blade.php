@props(['data'])

<livewire:dashboard.template.frontend-templates-page 
    :max-price="$data['max_price'] ?? 250" 
    :sort-by="$data['sort_by'] ?? 'default'"
    :show-sidebar="true"
/>
