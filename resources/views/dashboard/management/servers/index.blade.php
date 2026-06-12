<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.servers', 'Servers') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.servers', 'Servers') }}</h2>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('ok'))
        <div class="alert alert-success mb-4 flex items-center gap-2">
            <i class="ti ti-circle-check text-xl"></i>
            <span>{{ session('ok') }}</span>
        </div>
    @endif
    @if(session('connection_result'))
        <div class="alert alert-info mb-4 flex items-center gap-2">
            <i class="ti ti-info-circle text-xl"></i>
            <span>{{ session('connection_result') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">

                {{-- Card toolbar --}}
                <div class="card-header">
                    <form method="GET" action="{{ route('dashboard.servers.index') }}"
                          class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

                        {{-- Search --}}
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="ti ti-search text-base"></i>
                            </span>
                            <input
                                type="text"
                                name="search"
                                value="{{ $search ?? '' }}"
                                placeholder="{{ t('dashboard.Search_Servers', 'Search servers…') }}"
                                class="w-full border rounded-xl pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                            />
                        </div>

                        {{-- Per-page --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-gray-500 whitespace-nowrap">{{ t('dashboard.Per_Page', 'Per page') }}</span>
                            <select name="per_page" onchange="this.form.submit()"
                                    class="border rounded-xl px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                                @foreach([10, 25, 50] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 20) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Clear search button --}}
                        @if($search)
                            <a href="{{ route('dashboard.servers.index') }}"
                               class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                                <i class="ti ti-x text-base"></i>
                                {{ t('dashboard.Clear_Search', 'Clear') }}
                            </a>
                        @endif

                        {{-- Add server --}}
                        <a href="{{ route('dashboard.servers.create') }}"
                           class="shrink-0 btn btn-primary flex items-center gap-2 whitespace-nowrap">
                            <i class="ti ti-plus text-base"></i>
                            {{ t('dashboard.Add_Server', 'Add server') }}
                        </a>

                    </form>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th class="text-right w-10">#</th>
                                    <th class="text-right">{{ t('dashboard.Server_Name', 'Name') }}</th>
                                    <th class="text-right">{{ t('dashboard.Server_Type', 'Type') }}</th>
                                    <th class="text-right">IP</th>
                                    <th class="text-right">{{ t('dashboard.Hostname', 'Hostname') }}</th>
                                    <th class="text-right">{{ t('dashboard.Status', 'Status') }}</th>
                                    <th class="text-right">{{ t('dashboard.Actions', 'Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($servers as $server)
                                    @php $rowIndex = ($servers->firstItem() ?? 1) + $loop->index; @endphp
                                    <tr>
                                        <td class="text-gray-400 text-sm">{{ $rowIndex }}</td>

                                        <td class="font-semibold text-gray-800">{{ $server->name }}</td>

                                        <td>
                                            <span class="inline-flex items-center text-xs font-medium px-2.5 py-0.5 rounded-full
                                                {{ $server->type === 'cpanel'
                                                    ? 'bg-blue-100 text-blue-700'
                                                    : 'bg-violet-100 text-violet-700' }}">
                                                {{ $server->type }}
                                            </span>
                                        </td>

                                        <td class="text-sm text-gray-500 font-mono">{{ $server->ip ?: '—' }}</td>
                                        <td class="text-sm text-gray-500 font-mono">{{ $server->hostname ?: '—' }}</td>

                                        <td>
                                            @if($server->is_active)
                                                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    {{ t('dashboard.Active', 'Active') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-0.5 rounded-full bg-gray-100 text-gray-500">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                    {{ t('dashboard.Inactive', 'Inactive') }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap">
                                            <div class="flex items-center gap-0.5">
                                                <a href="{{ route('dashboard.servers.edit', $server) }}"
                                                   class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                   title="{{ t('dashboard.Edit', 'Edit') }}">
                                                    <i class="ti ti-edit text-lg leading-none"></i>
                                                </a>

                                                <a href="{{ route('dashboard.servers.test-connection', $server) }}"
                                                   class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                   title="{{ t('dashboard.Test_Connection', 'Test connection') }}">
                                                    <i class="ti ti-plug text-lg leading-none"></i>
                                                </a>

                                                <a href="{{ route('dashboard.servers.accounts', $server) }}"
                                                   class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                   title="{{ t('dashboard.View_Accounts', 'View accounts') }}">
                                                    <i class="ti ti-world text-lg leading-none"></i>
                                                </a>

                                                @if($server->type === 'cpanel')
                                                    <a href="{{ route('dashboard.servers.sso-whm', $server) }}"
                                                       target="_blank"
                                                       class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                       title="{{ t('dashboard.Login_SSO', 'Login (SSO)') }}">
                                                        <i class="ti ti-login text-lg leading-none"></i>
                                                    </a>
                                                @endif

                                                <form action="{{ route('dashboard.servers.destroy', $server) }}"
                                                      method="POST"
                                                      style="display:inline-block"
                                                      onsubmit="return confirm('{{ t('dashboard.Confirm_Delete', 'Are you sure you want to delete this server?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="w-8 h-8 rounded-xl inline-flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 transition"
                                                            title="{{ t('dashboard.Delete', 'Delete') }}">
                                                        <i class="ti ti-trash text-lg leading-none"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h13.5a3 3 0 0 1 3 3v5.25a3 3 0 0 1-3 3m-13.5 0v3.75m13.5-3.75v3.75m-13.5 3.75h13.5" />
                                                </svg>
                                                @if($search)
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Search_Results', 'No results found') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.Try_Different_Search', 'Try a different search term') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.servers.index') }}" class="btn btn-light btn-sm">
                                                        {{ t('dashboard.Clear_Search', 'Clear search') }}
                                                    </a>
                                                @else
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Servers', 'No servers yet') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.No_Servers_Desc', 'Add your first server to start managing hosting accounts') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.servers.create') }}" class="btn btn-primary btn-sm flex items-center gap-2">
                                                        <i class="ti ti-plus text-base"></i>
                                                        {{ t('dashboard.Add_Server', 'Add server') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($servers->hasPages())
                        <div class="mt-4">
                            {{ $servers->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-dashboard-layout>
