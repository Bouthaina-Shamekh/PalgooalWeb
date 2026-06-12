<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.clients') }}">{{ t('dashboard.clients', 'Clients') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_Client', 'Add Client') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_New_Client', 'Add New Client') }}</h2>
            </div>
        </div>
    </div>

    @include('dashboard.clients._alerts')

    <div class="grid grid-cols-12 gap-6">

        {{-- ═══ FORM (col-span-8) ═══════════════════════════════════════ --}}
        <div class="col-span-12 xl:col-span-8">
            @include('dashboard.clients._form', [
                'action'    => route('dashboard.clients.store'),
                'method'    => 'POST',
                'cancelUrl' => route('dashboard.clients'),
            ])
        </div>

        {{-- ═══ HELP SIDEBAR (col-span-4) ══════════════════════════════ --}}
        <div class="col-span-12 xl:col-span-4">
            <div class="card sticky top-6">
                <div class="card-header">
                    <h5 class="mb-0 flex items-center gap-2">
                        <i class="ti ti-info-circle text-primary"></i>
                        {{ t('dashboard.Help', 'Help') }}
                    </h5>
                </div>
                <div class="card-body space-y-5 text-sm text-gray-600">

                    <div>
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Client_Status', 'Account Status') }}</p>
                        <p class="text-muted">{{ t('dashboard.Help_Client_Status_Desc', 'Active clients can access their account dashboard. Inactive clients are blocked from logging in.') }}</p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Login_Access', 'Login Access') }}</p>
                        <p class="text-muted">{{ t('dashboard.Help_Login_Access_Desc', 'Controls whether this client is allowed to log in to the client portal at all, regardless of status.') }}</p>
                    </div>

                    <div class="border-t pt-4">
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Password', 'Password') }}</p>
                        <p class="text-muted">{{ t('dashboard.Help_Password_Desc', 'Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.') }}</p>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
