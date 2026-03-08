<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.clients') }}">Clients</a></li>
                <li class="breadcrumb-item" aria-current="page">Add Client</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Add Client</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    @include('dashboard.clients._alerts')
                    @include('dashboard.clients._form', [
                        'action' => route('dashboard.clients.store'),
                        'method' => 'POST',
                        'submitLabel' => 'Create Client',
                        'cancelUrl' => route('dashboard.clients'),
                    ])
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
