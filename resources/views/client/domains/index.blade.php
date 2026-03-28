<x-client-layout>
    @php
        $statusStyles = [
            'active' => 'bg-success-500/10 text-success-600',
            'pending' => 'bg-warning-500/10 text-warning-600',
            'expired' => 'bg-danger-500/10 text-danger-600',
        ];
        $subscriptionsUrl = route('client.subscriptions');
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
        <div class="col-span-12">
            <div class="card overflow-hidden">
                <div class="card-body">
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,0.7fr)] lg:items-center">
                        <div>
                            <span class="inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">
                                Domain setup guidance
                            </span>
                            <h4 class="mt-3 mb-2">Use this page for your domains, and your site dashboard for live status</h4>
                            <p class="mb-0 text-sm text-muted leading-6">
                                This page manages the domains in your account. Your actual live website address and custom-domain verification state are shown inside each site dashboard. If you connect a branded domain later, your platform subdomain stays active until the custom domain is fully ready.
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('client.domains.search') }}" class="btn btn-primary">
                                    <i class="ti ti-search me-1"></i>
                                    Search New Domain
                                </a>
                                <a href="{{ $subscriptionsUrl }}" class="btn btn-light-secondary">
                                    <i class="ti ti-layout-dashboard me-1"></i>
                                    Open Site Dashboards
                                </a>
                            </div>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                            <div class="rounded-2xl border border-theme-border dark:border-themedark-border p-4">
                                <div class="text-xs uppercase tracking-wide text-muted mb-2">1. Add a domain</div>
                                <p class="mb-0 text-sm text-body">Search, buy, or keep managing the domains already attached to your account.</p>
                            </div>
                            <div class="rounded-2xl border border-theme-border dark:border-themedark-border p-4">
                                <div class="text-xs uppercase tracking-wide text-muted mb-2">2. Connect it to a site</div>
                                <p class="mb-0 text-sm text-body">Open the correct site dashboard to see the live address, verification badge, and the next required step.</p>
                            </div>
                            <div class="rounded-2xl border border-theme-border dark:border-themedark-border p-4">
                                <div class="text-xs uppercase tracking-wide text-muted mb-2">3. Keep sharing the fallback</div>
                                <p class="mb-0 text-sm text-body">Until verification completes, visitors should keep using the platform subdomain.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                                Your live site address and custom-domain readiness are still tracked from the related subscription.
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
                                    <th>{{ t('frontend.client_domains.index.registration_date', 'Registered At') }}</th>
                                    <th>{{ t('frontend.client_domains.index.renewal_date', 'Renewal Date') }}</th>
                                    <th>{{ t('frontend.client_domains.index.auto_renew', 'Auto-Renew') }}</th>
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
                                            <div class="flex flex-col gap-2">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="font-semibold text-body break-all">{{ $domain->domain_name }}</span>
                                                    <button type="button" data-copy-value="{{ $domain->domain_name }}"
                                                        class="inline-flex items-center gap-1 rounded-full border border-theme-border dark:border-themedark-border px-2.5 py-1 text-xs font-semibold text-muted transition hover:text-body">
                                                        <i class="ti ti-copy text-sm leading-none"></i>
                                                        <span data-copy-label>Copy</span>
                                                    </button>
                                                </div>
                                                <span class="text-xs text-muted">
                                                    {{ t('frontend.client_domains.index.domain_id', 'ID') }} #{{ $domain->id }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ $domain->registration_date ? \Illuminate\Support\Carbon::parse($domain->registration_date)->format('Y-m-d') : '-' }}</td>
                                        <td>{{ $domain->renewal_date ? \Illuminate\Support\Carbon::parse($domain->renewal_date)->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            <div class="flex flex-col gap-2">
                                                <span class="badge {{ $domain->auto_renew ? 'bg-success-500/10 text-success-600' : 'bg-secondary-500/10 text-secondary-600' }}">
                                                    {{ $domain->auto_renew ? t('frontend.client_domains.index.auto_renew_on', 'On') : t('frontend.client_domains.index.auto_renew_off', 'Off') }}
                                                </span>
                                                <form action="{{ route('client.domains.auto-renew', $domain->id) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm {{ $domain->auto_renew ? 'btn-light-danger' : 'btn-light-success' }}">
                                                        {{ $domain->auto_renew ? t('frontend.client_domains.index.disable_auto_renew', 'Disable') : t('frontend.client_domains.index.enable_auto_renew', 'Enable') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-full px-3 py-2 {{ $statusClass }}">
                                                {{ ucfirst($statusKey ?: 'unknown') }}
                                            </span>
                                        </td>
                                        <td>{{ $domain->template?->name ?: t('frontend.client_domains.index.no_template', 'No template assigned') }}</td>
                                        <td class="text-end">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <a href="{{ route('client.domains.dns.edit', $domain->id) }}"
                                                    class="btn btn-sm btn-light-info"
                                                    title="{{ t('frontend.client_domains.index.change_dns', 'Change DNS') }}">
                                                    <i class="ti ti-world me-1 text-base leading-none"></i>
                                                    {{ t('frontend.client_domains.index.change_dns', 'Change DNS') }}
                                                </a>
                                                <form action="{{ route('client.domains.renew', $domain->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                        class="btn btn-sm btn-light-warning"
                                                        title="{{ t('frontend.client_domains.index.renew', 'Renew') }}">
                                                        <i class="ti ti-refresh me-1 text-base leading-none"></i>
                                                        {{ t('frontend.client_domains.index.renew', 'Renew') }}
                                                    </button>
                                                </form>
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

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-copy-value]').forEach((button) => {
                    button.addEventListener('click', async function () {
                        const value = button.getAttribute('data-copy-value');
                        const label = button.querySelector('[data-copy-label]') || button;
                        const originalLabel = label.textContent;

                        if (!value) {
                            return;
                        }

                        try {
                            if (navigator.clipboard && window.isSecureContext) {
                                await navigator.clipboard.writeText(value);
                            } else {
                                const textarea = document.createElement('textarea');
                                textarea.value = value;
                                textarea.setAttribute('readonly', 'readonly');
                                textarea.style.position = 'absolute';
                                textarea.style.left = '-9999px';
                                document.body.appendChild(textarea);
                                textarea.select();
                                document.execCommand('copy');
                                textarea.remove();
                            }

                            label.textContent = 'Copied';
                        } catch (error) {
                            label.textContent = 'Copy failed';
                        }

                        window.setTimeout(() => {
                            label.textContent = originalLabel;
                        }, 1600);
                    });
                });
            });
        </script>
    @endpush
</x-client-layout>
