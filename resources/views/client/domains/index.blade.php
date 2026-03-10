<x-client-layout>
    @php
        $statusStyles = [
            'active' => 'bg-success-500/10 text-success-600',
            'pending' => 'bg-warning-500/10 text-warning-600',
            'expired' => 'bg-danger-500/10 text-danger-600',
        ];
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('client.home') }}">{{ t('frontend.client_nav.home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            {{ t('frontend.client_domains.index.title', 'My Domains') }}
                        </li>
                    </ul>
                    <div class="page-header-title">
                        <h2 class="mb-1">{{ t('frontend.client_domains.index.title', 'My Domains') }}</h2>
                        <p class="mb-0 text-sm text-muted">
                            {{ t('frontend.client_domains.index.subtitle', 'Review your registered domains, renewal dates, and current status.') }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('client.domains.search') }}" class="btn btn-primary">
                        <i class="ti ti-search me-1"></i>
                        {{ t('frontend.client_domains.index.search_cta', 'Search New Domain') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">
        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500 mb-2">{{ t('frontend.client_domains.index.total_domains', 'Total Domains') }}</p>
                    <div class="flex items-center justify-between">
                        <h3 class="mb-0">{{ $domainStats['total'] ?? 0 }}</h3>
                        <span class="w-10 h-10 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center">
                            <i class="ti ti-world text-lg leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500 mb-2">{{ t('frontend.client_domains.index.active_domains', 'Active Domains') }}</p>
                    <div class="flex items-center justify-between">
                        <h3 class="mb-0">{{ $domainStats['active'] ?? 0 }}</h3>
                        <span class="w-10 h-10 rounded-full bg-success-500/10 text-success-500 inline-flex items-center justify-center">
                            <i class="ti ti-circle-check text-lg leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500 mb-2">{{ t('frontend.client_domains.index.pending_domains', 'Pending Domains') }}</p>
                    <div class="flex items-center justify-between">
                        <h3 class="mb-0">{{ $domainStats['pending'] ?? 0 }}</h3>
                        <span class="w-10 h-10 rounded-full bg-warning-500/10 text-warning-500 inline-flex items-center justify-center">
                            <i class="ti ti-loader-2 text-lg leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 md:col-span-6 xl:col-span-3">
            <div class="card">
                <div class="card-body">
                    <p class="text-sm text-gray-500 mb-2">{{ t('frontend.client_domains.index.expired_domains', 'Expired Domains') }}</p>
                    <div class="flex items-center justify-between">
                        <h3 class="mb-0">{{ $domainStats['expired'] ?? 0 }}</h3>
                        <span class="w-10 h-10 rounded-full bg-danger-500/10 text-danger-500 inline-flex items-center justify-center">
                            <i class="ti ti-alert-circle text-lg leading-none"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h5 class="mb-1">{{ t('frontend.client_domains.index.portfolio_title', 'Domain Portfolio') }}</h5>
                            <p class="mb-0 text-sm text-muted">
                                {{ t('frontend.client_domains.index.portfolio_subtitle', 'All domains associated with your account are listed below.') }}
                            </p>
                        </div>
                        <span class="badge bg-light-primary text-primary px-3 py-2">
                            {{ $domains->total() }} {{ t('frontend.client_domains.index.records_label', 'records') }}
                        </span>
                    </div>
                </div>
                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ t('frontend.client_domains.index.domain_name', 'Domain Name') }}</th>
                                    <th>{{ t('frontend.client_domains.index.registrar', 'Registrar') }}</th>
                                    <th>{{ t('frontend.client_domains.index.registration_date', 'Registered At') }}</th>
                                    <th>{{ t('frontend.client_domains.index.renewal_date', 'Renewal Date') }}</th>
                                    <th>{{ t('frontend.client_domains.index.status', 'Status') }}</th>
                                    <th>{{ t('frontend.client_domains.index.template', 'Template') }}</th>
                                    <th class="text-end">{{ t('frontend.client_domains.index.actions', 'Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($domains as $domain)
                                    @php
                                        $statusKey = strtolower((string) $domain->status);
                                        $statusClass = $statusStyles[$statusKey] ?? 'bg-light-secondary text-secondary';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="flex flex-col">
                                                <span class="font-semibold text-body">{{ $domain->domain_name }}</span>
                                                <span class="text-xs text-muted">
                                                    {{ t('frontend.client_domains.index.domain_id', 'ID') }} #{{ $domain->id }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light-secondary text-secondary px-3 py-2">
                                                {{ $domain->registrar ?: '-' }}
                                            </span>
                                        </td>
                                        <td>{{ $domain->registration_date ? \Illuminate\Support\Carbon::parse($domain->registration_date)->format('Y-m-d') : '-' }}</td>
                                        <td>{{ $domain->renewal_date ? \Illuminate\Support\Carbon::parse($domain->renewal_date)->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            <span class="badge rounded-full px-3 py-2 {{ $statusClass }}">
                                                {{ ucfirst($statusKey ?: 'unknown') }}
                                            </span>
                                        </td>
                                        <td>{{ $domain->template?->name ?: t('frontend.client_domains.index.no_template', 'No template assigned') }}</td>
                                        <td class="text-end">
                                            <div class="inline-flex items-center gap-2">
                                                <a href="{{ route('client.domains.edit', $domain->id) }}"
                                                    class="w-9 h-9 rounded-xl inline-flex items-center justify-center btn-light-primary"
                                                    title="{{ t('frontend.client_domains.index.edit', 'Edit') }}">
                                                    <i class="ti ti-edit text-lg leading-none"></i>
                                                </a>
                                                <form action="{{ route('client.domains.destroy', $domain->id) }}" method="POST"
                                                    onsubmit="return confirm('{{ t('frontend.client_domains.index.confirm_delete', 'Are you sure you want to delete this domain?') }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="w-9 h-9 rounded-xl inline-flex items-center justify-center btn-light-danger"
                                                        title="{{ t('frontend.client_domains.index.delete', 'Delete') }}">
                                                        <i class="ti ti-trash text-lg leading-none"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-10">
                                            <div class="flex flex-col items-center justify-center text-center">
                                                <span class="w-16 h-16 rounded-full bg-primary/10 text-primary inline-flex items-center justify-center mb-4">
                                                    <i class="ti ti-world text-2xl leading-none"></i>
                                                </span>
                                                <h6 class="mb-2">{{ t('frontend.client_domains.index.empty_title', 'No domains found yet') }}</h6>
                                                <p class="mb-4 text-sm text-muted">
                                                    {{ t('frontend.client_domains.index.empty_subtitle', 'Start by searching for a new domain and add it to your account.') }}
                                                </p>
                                                <a href="{{ route('client.domains.search') }}" class="btn btn-primary">
                                                    {{ t('frontend.client_domains.index.empty_cta', 'Search for a Domain') }}
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if ($domains->hasPages())
            <div class="col-span-12">
                <div class="flex justify-end">
                    {{ $domains->links() }}
                </div>
            </div>
        @endif
    </div>
</x-client-layout>
