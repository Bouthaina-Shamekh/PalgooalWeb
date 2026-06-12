<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.subscriptions', 'Subscriptions') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Subscriptions_List', 'Subscriptions') }}</h2>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @foreach(['ok', 'success', 'connection_result', 'info'] as $flashKey)
        @if(session($flashKey))
            <div class="alert alert-{{ $flashKey === 'connection_result' ? 'info' : ($flashKey === 'ok' || $flashKey === 'success' ? 'success' : 'info') }} mb-4 flex items-center gap-2"
                 style="{{ $flashKey === 'connection_result' ? 'white-space:pre-line' : '' }}">
                <i class="ti ti-circle-check text-xl"></i>
                <span>{{ session($flashKey) }}</span>
            </div>
        @endif
    @endforeach
    @if(session('error'))
        <div class="alert alert-danger mb-4 flex items-center gap-2">
            <i class="ti ti-alert-circle text-xl"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning mb-4 flex items-center gap-2">
            <i class="ti ti-alert-triangle text-xl"></i>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">

                {{-- Card toolbar --}}
                <div class="card-header">
                    <form method="GET" action="{{ route('dashboard.subscriptions.index') }}"
                          class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3">

                        {{-- Search (client / domain) --}}
                        <div class="relative flex-1 min-w-[160px]">
                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="ti ti-search text-base"></i>
                            </span>
                            <input type="text" name="q"
                                value="{{ $q ?? '' }}"
                                placeholder="{{ t('dashboard.Search_Client_Domain', 'Search client or domain…') }}"
                                class="w-full border rounded-xl pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        {{-- Domain filter --}}
                        <div class="relative shrink-0">
                            <input type="text" name="domain"
                                value="{{ $domain ?? '' }}"
                                placeholder="{{ t('dashboard.Filter_Domain_Placeholder', 'Filter by domain…') }}"
                                class="border rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 w-44"
                                dir="ltr" />
                        </div>

                        {{-- Status --}}
                        <select name="status" onchange="this.form.submit()"
                                class="border rounded-xl px-2 py-2 text-sm shrink-0 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">{{ t('dashboard.All_Statuses', 'All statuses') }}</option>
                            <option value="active"    {{ ($status ?? '') == 'active'    ? 'selected' : '' }}>{{ t('dashboard.Status_Active',    'Active') }}</option>
                            <option value="pending"   {{ ($status ?? '') == 'pending'   ? 'selected' : '' }}>{{ t('dashboard.Status_Pending',   'Pending') }}</option>
                            <option value="suspended" {{ ($status ?? '') == 'suspended' ? 'selected' : '' }}>{{ t('dashboard.Status_Suspended', 'Suspended') }}</option>
                            <option value="cancelled" {{ ($status ?? '') == 'cancelled' ? 'selected' : '' }}>{{ t('dashboard.Status_Cancelled', 'Cancelled') }}</option>
                        </select>

                        {{-- Sort --}}
                        <select name="sort" onchange="this.form.submit()"
                                class="border rounded-xl px-2 py-2 text-sm shrink-0 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">{{ t('dashboard.Sort_By', 'Sort') }}</option>
                            <option value="domain_name" {{ ($sort ?? '') == 'domain_name' ? 'selected' : '' }}>{{ t('dashboard.Sort_Domain',     'Domain') }}</option>
                            <option value="starts_at"   {{ ($sort ?? '') == 'starts_at'   ? 'selected' : '' }}>{{ t('dashboard.Sort_Start_Date', 'Start date') }}</option>
                        </select>

                        {{-- Direction --}}
                        <select name="direction" onchange="this.form.submit()"
                                class="border rounded-xl px-2 py-2 text-sm shrink-0 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="asc"  {{ ($direction ?? 'asc') == 'asc'  ? 'selected' : '' }}>{{ t('dashboard.Ascending',  'Ascending') }}</option>
                            <option value="desc" {{ ($direction ?? 'asc') == 'desc' ? 'selected' : '' }}>{{ t('dashboard.Descending', 'Descending') }}</option>
                        </select>

                        {{-- Per page --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-gray-500 whitespace-nowrap">{{ t('dashboard.Per_Page', 'Per page') }}</span>
                            <select name="per_page" onchange="this.form.submit()"
                                    class="border rounded-xl px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                                @foreach([10, 20, 50] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 20) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Search submit --}}
                        <button type="submit"
                                class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                            <i class="ti ti-search text-base"></i>
                            {{ t('dashboard.Search', 'Search') }}
                        </button>

                        {{-- Clear filters --}}
                        @if(($q ?? '') || ($domain ?? '') || ($status ?? '') || ($sort ?? ''))
                            <a href="{{ route('dashboard.subscriptions.index') }}"
                               class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                                <i class="ti ti-x text-base"></i>
                                {{ t('dashboard.Clear_Search', 'Clear') }}
                            </a>
                        @endif

                        {{-- Add subscription --}}
                        <a href="{{ route('dashboard.subscriptions.create') }}"
                           class="shrink-0 btn btn-primary flex items-center gap-2 whitespace-nowrap">
                            <i class="ti ti-plus text-base"></i>
                            {{ t('dashboard.Add_Subscription', 'Add subscription') }}
                        </a>

                    </form>
                </div>

                <div class="card-body pt-3">

                    {{-- Bulk actions bar --}}
                    <form id="bulk_form" method="POST" action="{{ route('dashboard.subscriptions.bulk') }}"
                          class="flex items-center gap-3 mb-4">
                        @csrf
                        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-3 py-2 shadow-sm">
                            <label for="bulk_action" class="sr-only">{{ t('dashboard.Bulk_Action_Placeholder', 'Bulk action') }}</label>
                            <select name="action" id="bulk_action"
                                    class="rounded-md border-none px-2 py-1 text-sm bg-transparent focus:outline-none">
                                <option value="">{{ t('dashboard.Bulk_Action_Placeholder', 'Select bulk action') }}</option>
                                <option value="suspend">{{ t('dashboard.Bulk_Suspend',   'Suspend') }}</option>
                                <option value="unsuspend">{{ t('dashboard.Bulk_Unsuspend', 'Unsuspend') }}</option>
                                <option value="sync">{{ t('dashboard.Bulk_Sync',      'Sync') }}</option>
                                <option value="terminate">{{ t('dashboard.Bulk_Terminate', 'Terminate (server)') }}</option>
                                <option value="delete">{{ t('dashboard.Bulk_Delete',    'Delete') }}</option>
                            </select>
                            <button type="button" id="bulk_apply"
                                    class="btn btn-primary btn-sm flex items-center gap-1">
                                <i class="ti ti-arrow-right text-sm"></i>
                                {{ t('dashboard.Apply', 'Apply') }}
                            </button>
                        </div>
                        <span class="text-sm text-gray-500">
                            {{ t('dashboard.Total', 'Total') }}: {{ $subscriptions->total() }}
                        </span>
                    </form>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th class="w-8 text-right">
                                        <input type="checkbox" id="select_all" />
                                    </th>
                                    <th class="text-right">{{ t('dashboard.Client', 'Client') }}</th>
                                    <th class="text-right">{{ t('dashboard.plans', 'Plan') }}</th>
                                    <th class="text-right">{{ t('dashboard.Server_Package_Col', 'Server package') }}</th>
                                    <th class="text-right">{{ t('dashboard.Domain_Col', 'Domain') }}</th>
                                    <th class="text-right">{{ t('dashboard.Status', 'Status') }}</th>
                                    <th class="text-right">{{ t('dashboard.Actions', 'Actions') }}</th>
                                    <th class="text-right">{{ t('dashboard.Sync_Result', 'Sync result') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($subscriptions as $sub)
                                    @php
                                        $domainVerificationBadge = ! $sub->requiresDomainVerification()
                                            ? ['label' => t('dashboard.Domain_Platform_Active', 'Platform subdomain active'), 'class' => 'bg-emerald-100 text-emerald-800']
                                            : match ($sub->effectiveDomainVerificationStatus()) {
                                                \App\Models\Tenancy\Subscription::DOMAIN_VERIFICATION_ACTIVE      => ['label' => t('dashboard.Domain_Custom_Active',     'Custom domain active'),             'class' => 'bg-emerald-100 text-emerald-800'],
                                                \App\Models\Tenancy\Subscription::DOMAIN_VERIFICATION_SSL_PENDING => ['label' => t('dashboard.Domain_SSL_Pending',        'Waiting for HTTPS'),                'class' => 'bg-sky-100 text-sky-800'],
                                                \App\Models\Tenancy\Subscription::DOMAIN_VERIFICATION_DNS_PENDING => ['label' => t('dashboard.Domain_DNS_Pending',        'Verification pending (DNS)'),       'class' => 'bg-yellow-100 text-yellow-800'],
                                                \App\Models\Tenancy\Subscription::DOMAIN_VERIFICATION_FAILED      => ['label' => t('dashboard.Domain_Verification_Failed', 'Verification failed'),             'class' => 'bg-red-100 text-red-800'],
                                                default                                                            => ['label' => t('dashboard.Domain_DNS_Pending',        'Verification pending (DNS)'),       'class' => 'bg-yellow-100 text-yellow-800'],
                                            };
                                    @endphp
                                    <tr data-subscription-row="{{ $sub->id }}">
                                        {{-- Checkbox --}}
                                        <td>
                                            <input type="checkbox" class="row_checkbox" name="ids[]" value="{{ $sub->id }}" />
                                        </td>

                                        {{-- Client --}}
                                        <td class="text-sm text-gray-700">
                                            {{ $sub->client->first_name ?? '' }} {{ $sub->client->last_name ?? '' }}
                                            @if($sub->client?->email)
                                                <div class="text-xs text-gray-400">{{ $sub->client->email }}</div>
                                            @endif
                                        </td>

                                        {{-- Plan --}}
                                        <td class="text-sm text-gray-700">{{ $sub->plan->name ?? '—' }}</td>

                                        {{-- Server Package --}}
                                        <td class="text-sm text-gray-500 font-mono text-xs">
                                            {{ $sub->server_package ?? ($sub->plan->server_package ?? ($sub->plan->name ?? '—')) }}
                                        </td>

                                        {{-- Domain --}}
                                        <td class="text-sm">
                                            @if ($sub->domain_name)
                                                <div class="flex items-center gap-2">
                                                    @php
                                                        $link = \Illuminate\Support\Str::startsWith($sub->domain_name, ['http://', 'https://'])
                                                            ? $sub->domain_name : 'http://' . $sub->domain_name;
                                                    @endphp
                                                    <a href="{{ $link }}" target="_blank" rel="noopener"
                                                       class="text-primary hover:underline font-mono text-xs">{{ $sub->domain_name }}</a>
                                                    <button type="button" data-copy-domain="{{ $sub->domain_name }}"
                                                            class="text-xs text-gray-400 hover:text-gray-700 transition">
                                                        {{ t('dashboard.Copy_Domain', 'Copy') }}
                                                    </button>
                                                </div>
                                                <div class="mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $domainVerificationBadge['class'] }}">
                                                        {{ $domainVerificationBadge['label'] }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>

                                        {{-- Status badge --}}
                                        <td>
                                            @php
                                                $statusConfig = [
                                                    'active'    => ['label' => t('dashboard.Status_Active',    'Active'),    'class' => 'bg-emerald-50 text-emerald-700'],
                                                    'pending'   => ['label' => t('dashboard.Status_Pending',   'Pending'),   'class' => 'bg-yellow-50 text-yellow-700'],
                                                    'suspended' => ['label' => t('dashboard.Status_Suspended', 'Suspended'), 'class' => 'bg-gray-100 text-gray-600'],
                                                    'cancelled' => ['label' => t('dashboard.Status_Cancelled', 'Cancelled'), 'class' => 'bg-red-50 text-red-700'],
                                                ];
                                                $sc = $statusConfig[$sub->status] ?? ['label' => $sub->status, 'class' => 'bg-gray-100 text-gray-600'];
                                            @endphp
                                            <span class="sub-status-badge inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sc['class'] }}">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $sub->status === 'active' ? 'bg-emerald-500' : ($sub->status === 'pending' ? 'bg-yellow-400' : ($sub->status === 'cancelled' ? 'bg-red-500' : 'bg-gray-400')) }}"></span>
                                                {{ $sc['label'] }}
                                            </span>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="whitespace-nowrap">
                                            <div class="flex items-center gap-0.5">

                                                {{-- cPanel login --}}
                                                <a href="{{ route('dashboard.subscriptions.cpanel-login', $sub) }}"
                                                   target="_blank" rel="noopener"
                                                   title="{{ t('dashboard.Login_CPanel', 'Login to cPanel') }}"
                                                   class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M12 2v4M12 18v4M4 12h4M16 12h4M5 5l4 4M15 15l4 4M5 19l4-4M15 9l4-4"></path>
                                                    </svg>
                                                </a>

                                                {{-- Sync --}}
                                                <form action="{{ route('dashboard.subscriptions.sync', $sub) }}" method="POST" class="ajax-action">
                                                    @csrf
                                                    <button type="submit"
                                                            title="{{ t('dashboard.Bulk_Sync', 'Sync') }}"
                                                            class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                                        <i class="ti ti-refresh text-base leading-none"></i>
                                                    </button>
                                                </form>

                                                {{-- Verify domain --}}
                                                <form action="{{ route('dashboard.subscriptions.verify-domain', $sub) }}" method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                            title="{{ t('dashboard.Verify_Domain', 'Verify domain') }}"
                                                            class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                                        <i class="ti ti-world-check text-base leading-none"></i>
                                                    </button>
                                                </form>

                                                {{-- Suspend / Unsuspend --}}
                                                @if ($sub->status === 'active')
                                                    <form action="{{ route('dashboard.subscriptions.suspend', $sub) }}" method="POST" class="ajax-action">
                                                        @csrf
                                                        <button type="submit"
                                                                title="{{ t('dashboard.Bulk_Suspend', 'Suspend') }}"
                                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                                            <i class="ti ti-player-pause text-base leading-none"></i>
                                                        </button>
                                                    </form>
                                                @elseif ($sub->status === 'suspended')
                                                    <form action="{{ route('dashboard.subscriptions.unsuspend', $sub) }}" method="POST" class="ajax-action">
                                                        @csrf
                                                        <button type="submit"
                                                                title="{{ t('dashboard.Bulk_Unsuspend', 'Unsuspend') }}"
                                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                                            <i class="ti ti-player-play text-base leading-none"></i>
                                                        </button>
                                                    </form>
                                                @endif

                                                {{-- Terminate (destructive) --}}
                                                <form action="{{ route('dashboard.subscriptions.terminate', $sub) }}" method="POST"
                                                      class="ajax-action ajax-destructive"
                                                      data-confirm="{{ t('dashboard.Terminate_Confirm', 'This will permanently delete the site from the server. Are you sure?') }}">
                                                    @csrf
                                                    <button type="submit"
                                                            title="{{ t('dashboard.Bulk_Terminate', 'Terminate') }}"
                                                            class="w-8 h-8 rounded-xl inline-flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 transition">
                                                        <i class="ti ti-trash text-base leading-none"></i>
                                                    </button>
                                                </form>

                                                {{-- Dropdown: Edit / Delete / Provision --}}
                                                <div class="relative inline-block">
                                                    <a class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary dropdown-toggle arrow-none"
                                                       href="#" data-pc-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <span class="sr-only">{{ t('dashboard.Actions', 'Actions') }}</span>
                                                        <i class="ti ti-dots-vertical text-lg leading-none"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end hidden origin-top-right absolute right-0 mt-2 w-52 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 p-2 z-50"
                                                         data-pc-dropdown role="menu" aria-hidden="true">

                                                        <a href="{{ route('dashboard.subscriptions.edit', $sub) }}"
                                                           role="menuitem"
                                                           class="flex items-center gap-2 px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                                            <i class="ti ti-edit text-gray-400 text-base"></i>
                                                            {{ t('dashboard.Edit', 'Edit') }}
                                                        </a>

                                                        <form action="{{ route('dashboard.subscriptions.provision', $sub) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" role="menuitem"
                                                                    class="flex items-center gap-2 w-full px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                                                <i class="ti ti-refresh-alert text-gray-400 text-base"></i>
                                                                {{ t('dashboard.Provision_Reactivate', 'Re-provision') }}
                                                            </button>
                                                        </form>

                                                        @if ($sub->status === 'active')
                                                            <form action="{{ route('dashboard.subscriptions.suspend', $sub) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" role="menuitem"
                                                                        class="flex items-center gap-2 w-full px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                                                    <i class="ti ti-player-pause text-gray-400 text-base"></i>
                                                                    {{ t('dashboard.Bulk_Suspend', 'Suspend') }}
                                                                </button>
                                                            </form>
                                                        @elseif ($sub->status === 'suspended')
                                                            <form action="{{ route('dashboard.subscriptions.unsuspend', $sub) }}" method="POST">
                                                                @csrf
                                                                <button type="submit" role="menuitem"
                                                                        class="flex items-center gap-2 w-full px-3 py-2 rounded hover:bg-gray-50 text-sm text-gray-700">
                                                                    <i class="ti ti-player-play text-gray-400 text-base"></i>
                                                                    {{ t('dashboard.Bulk_Unsuspend', 'Unsuspend') }}
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <form action="{{ route('dashboard.subscriptions.destroy', $sub) }}" method="POST"
                                                              onsubmit="return confirm('{{ t('dashboard.Confirm_Delete', 'Are you sure you want to delete this?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" role="menuitem"
                                                                    class="flex items-center gap-2 w-full px-3 py-2 rounded hover:bg-gray-50 text-sm text-red-600">
                                                                <i class="ti ti-trash text-red-400 text-base"></i>
                                                                {{ t('dashboard.Delete', 'Delete') }}
                                                            </button>
                                                        </form>

                                                    </div>
                                                </div>

                                            </div>
                                        </td>

                                        {{-- Sync result --}}
                                        <td class="text-sm">
                                            @php
                                                $syncStatus = $sub->last_sync_status ?? null;
                                                $syncAt     = $sub->last_synced_at  ?? null;
                                                $syncConfig = [
                                                    'success' => ['label' => t('dashboard.Sync_Success', 'Success'), 'class' => 'bg-emerald-50 text-emerald-700'],
                                                    'failed'  => ['label' => t('dashboard.Sync_Failed',  'Failed'),  'class' => 'bg-red-50 text-red-700'],
                                                    'pending' => ['label' => t('dashboard.Sync_Pending', 'Pending'), 'class' => 'bg-yellow-50 text-yellow-700'],
                                                ];
                                                $sc2 = $syncConfig[$syncStatus] ?? ['label' => t('dashboard.Sync_Unknown', 'Unknown'), 'class' => 'bg-gray-100 text-gray-500'];
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sc2['class'] }}">
                                                {{ $sc2['label'] }}
                                            </span>
                                            @if ($syncAt)
                                                <div class="text-xs text-gray-400 mt-1">
                                                    {{ \Illuminate\Support\Carbon::parse($syncAt)->diffForHumans() }}
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                                </svg>
                                                @if(($q ?? '') || ($domain ?? '') || ($status ?? ''))
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Search_Results', 'No results found') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.Try_Different_Search', 'Try a different search term') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.subscriptions.index') }}" class="btn btn-light btn-sm">
                                                        {{ t('dashboard.Clear_Search', 'Clear search') }}
                                                    </a>
                                                @else
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Subscriptions', 'No subscriptions yet') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.No_Subscriptions_Desc', 'Add your first subscription to start managing client sites') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.subscriptions.create') }}" class="btn btn-primary btn-sm flex items-center gap-2">
                                                        <i class="ti ti-plus text-base"></i>
                                                        {{ t('dashboard.Add_Subscription', 'Add subscription') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($subscriptions->hasPages())
                        <div class="mt-4">
                            {{ $subscriptions->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    <script>
        // ── Toast helper ──────────────────────────────────────────────────────
        function showToast(msg, ok) {
            var el = document.createElement('div');
            el.textContent = msg;
            el.style.cssText = 'position:fixed;right:20px;bottom:20px;padding:10px 16px;border-radius:8px;z-index:9999;color:#fff;font-size:14px;box-shadow:0 2px 8px rgba(0,0,0,.15);';
            el.style.background = ok !== false ? '#16a34a' : '#dc2626';
            document.body.appendChild(el);
            setTimeout(function () { document.body.removeChild(el); }, 3500);
        }

        @if(session('success'))  showToast(@json(session('success')), true);  @endif
        @if(session('error'))    showToast(@json(session('error')),   false); @endif
        @if(session('warning'))  showToast(@json(session('warning')), false); @endif
        @if(session('info'))     showToast(@json(session('info')),    true);  @endif

        // ── AJAX quick-action forms (ajax-action class) ───────────────────────
        // IMPORTANT: registered ONCE at the top level — NOT inside a click handler
        document.addEventListener('submit', function (e) {
            var form = e.target;
            if (!form.classList || !form.classList.contains('ajax-action')) return;

            // Confirm destructive actions
            if (form.classList.contains('ajax-destructive')) {
                var msg = form.dataset.confirm || '{{ t('dashboard.Terminate_Confirm', 'Are you sure?') }}';
                if (!confirm(msg)) { e.preventDefault(); return; }
            }

            e.preventDefault();
            var headers = { 'X-Requested-With': 'XMLHttpRequest' };
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            if (tokenMeta) headers['X-CSRF-TOKEN'] = tokenMeta.getAttribute('content');

            fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                headers: headers,
                body: new FormData(form),
                credentials: 'same-origin'
            })
            .then(function (res) {
                return res.json().catch(function () { return { ok: res.ok }; });
            })
            .then(function (json) {
                if (json && json.error)   { showToast(json.error,   false); return; }
                if (json && json.message) { showToast(json.message, true); }

                // Update status badge in-row
                if (json && json.subscription && json.subscription.id) {
                    var row = document.querySelector('[data-subscription-row="' + json.subscription.id + '"]');
                    if (row) {
                        var badge = row.querySelector('.sub-status-badge');
                        if (badge && json.subscription.status) {
                            badge.textContent = json.subscription.status;
                        }
                    }
                }

                // Remove row on destructive terminate
                if (form.classList.contains('ajax-destructive')) {
                    var tr = form.closest('tr');
                    if (tr) tr.remove();
                }
            })
            .catch(function (err) {
                console.error(err);
                showToast('{{ t('dashboard.Error_Try_Again', 'An error occurred, please try again') }}', false);
            });
        });

        // ── Dropdown toggle ───────────────────────────────────────────────────
        document.addEventListener('click', function (e) {
            document.querySelectorAll('[data-pc-dropdown]').forEach(function (el) {
                if (!el.contains(e.target) && !el.previousElementSibling?.contains(e.target)) {
                    el.classList.add('hidden');
                    el.setAttribute('aria-hidden', 'true');
                }
            });
        });
        document.querySelectorAll('[data-pc-toggle="dropdown"]').forEach(function (btn) {
            btn.addEventListener('click', function (ev) {
                ev.preventDefault(); ev.stopPropagation();
                var menu = btn.parentElement.querySelector('[data-pc-dropdown]');
                if (!menu) return;
                var hidden = menu.classList.contains('hidden');
                document.querySelectorAll('[data-pc-dropdown]').forEach(function (el) {
                    el.classList.add('hidden'); el.setAttribute('aria-hidden', 'true');
                    var tb = el.parentElement.querySelector('[data-pc-toggle="dropdown"]');
                    if (tb) tb.setAttribute('aria-expanded', 'false');
                });
                if (hidden) {
                    menu.classList.remove('hidden'); menu.setAttribute('aria-hidden', 'false');
                    btn.setAttribute('aria-expanded', 'true');
                    var first = menu.querySelector('[role="menuitem"]');
                    if (first) first.focus();
                }
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('[data-pc-dropdown]').forEach(function (el) {
                el.classList.add('hidden'); el.setAttribute('aria-hidden', 'true');
                var tb = el.parentElement.querySelector('[data-pc-toggle="dropdown"]');
                if (tb) tb.setAttribute('aria-expanded', 'false');
            });
        });

        // ── Select all checkboxes ─────────────────────────────────────────────
        var selectAll = document.getElementById('select_all');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.row_checkbox').forEach(function (cb) { cb.checked = selectAll.checked; });
            });
        }

        // ── Copy domain ───────────────────────────────────────────────────────
        document.querySelectorAll('[data-copy-domain]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var val = btn.getAttribute('data-copy-domain');
                var origText = btn.innerText;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(val);
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = val; document.body.appendChild(ta); ta.select();
                    document.execCommand('copy'); ta.remove();
                }
                btn.innerText = '{{ t('dashboard.Copied', 'Copied') }}';
                setTimeout(function () { btn.innerText = origText; }, 1500);
            });
        });

        // ── Bulk apply ────────────────────────────────────────────────────────
        var bulkApply = document.getElementById('bulk_apply');
        if (bulkApply) {
            bulkApply.addEventListener('click', function () {
                var action = document.getElementById('bulk_action').value;
                if (!action) {
                    alert('{{ t('dashboard.Bulk_Select_Action', 'Please select an action first') }}');
                    return;
                }
                var checked = Array.from(document.querySelectorAll('.row_checkbox:checked')).map(function (cb) { return cb.value; });
                if (checked.length === 0) {
                    alert('{{ t('dashboard.Bulk_Select_Min_One', 'Select at least one subscription') }}');
                    return;
                }
                var form = document.getElementById('bulk_form');
                form.querySelectorAll('input[name="ids[]"]').forEach(function (i) { i.remove(); });
                checked.forEach(function (id) {
                    var inp = document.createElement('input');
                    inp.type = 'hidden'; inp.name = 'ids[]'; inp.value = id;
                    form.appendChild(inp);
                });
                if (confirm(checked.length + ' {{ t('dashboard.Bulk_Confirm_Suffix', 'subscription(s) will be affected. Continue?') }}')) {
                    form.submit();
                }
            });
        }
    </script>
</x-dashboard-layout>
