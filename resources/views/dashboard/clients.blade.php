<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.clients', 'Clients') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Clients_List', 'Clients') }}</h2>
            </div>
        </div>
    </div>

    @include('dashboard.clients._alerts')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">

                {{-- Card toolbar --}}
                <div class="card-header">
                    <form method="GET" action="{{ route('dashboard.clients') }}"
                          class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3">

                        {{-- Search --}}
                        <div class="relative flex-1 min-w-[200px]">
                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="ti ti-search text-base"></i>
                            </span>
                            <input type="text" name="search"
                                value="{{ $search }}"
                                placeholder="{{ t('dashboard.Search_Clients', 'Search by name, email, company…') }}"
                                class="w-full border rounded-xl pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        {{-- Per page --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-gray-500 whitespace-nowrap">{{ t('dashboard.Per_Page', 'Per page') }}</span>
                            <select name="per_page" onchange="this.form.submit()"
                                    class="border rounded-xl px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                                @foreach([5, 10, 25] as $n)
                                    <option value="{{ $n }}" {{ $perPage === $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Search submit --}}
                        <button type="submit"
                                class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                            <i class="ti ti-search text-base"></i>
                            {{ t('dashboard.Search', 'Search') }}
                        </button>

                        {{-- Clear --}}
                        @if($search)
                            <a href="{{ route('dashboard.clients') }}"
                               class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                                <i class="ti ti-x text-base"></i>
                                {{ t('dashboard.Clear_Search', 'Clear') }}
                            </a>
                        @endif

                        {{-- Add client --}}
                        @can('create', 'App\\Models\\Client')
                            <a href="{{ route('dashboard.clients.create') }}"
                               class="shrink-0 btn btn-primary flex items-center gap-2 whitespace-nowrap">
                                <i class="ti ti-plus text-base"></i>
                                {{ t('dashboard.Add_Client', 'Add Client') }}
                            </a>
                        @endcan

                    </form>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th class="text-right">#</th>
                                    <th class="text-right">{{ t('dashboard.Client_Name', 'Client') }}</th>
                                    <th class="text-right">{{ t('dashboard.Company', 'Company') }}</th>
                                    <th class="text-right">{{ t('dashboard.Phone', 'Phone') }}</th>
                                    <th class="text-right">{{ t('dashboard.Location', 'Location') }}</th>
                                    <th class="text-right">{{ t('dashboard.subscriptions', 'Subscriptions') }}</th>
                                    <th class="text-right">{{ t('dashboard.domains', 'Domains') }}</th>
                                    <th class="text-right">{{ t('dashboard.Last_Login', 'Last Login') }}</th>
                                    <th class="text-right">{{ t('dashboard.Joined', 'Joined') }}</th>
                                    <th class="text-right">{{ t('dashboard.Status', 'Status') }}</th>
                                    <th class="text-right">{{ t('dashboard.Actions', 'Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($clients as $client)
                                    @php
                                        $lastLogin = $client->last_login_at
                                            ? \Illuminate\Support\Carbon::parse($client->last_login_at)
                                            : null;
                                    @endphp
                                    <tr>
                                        {{-- Row number --}}
                                        <td class="text-sm text-gray-500">
                                            {{ ($clients->firstItem() ?? 1) + $loop->index }}
                                        </td>

                                        {{-- Client name + avatar --}}
                                        <td>
                                            <div class="flex items-center gap-3 min-w-[160px]">
                                                <img src="{{ $client->avatar ? asset('storage/' . $client->avatar) : asset('assets/images/user/avatar-1.jpg') }}"
                                                     alt="{{ trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) ?: t('dashboard.Client_Avatar', 'Client avatar') }}"
                                                     class="rounded-full w-9 h-9 object-cover shrink-0" />
                                                <div>
                                                    <p class="mb-0 font-semibold text-sm text-gray-800 leading-tight">
                                                        {{ $client->first_name }} {{ $client->last_name }}
                                                    </p>
                                                    <p class="text-xs text-gray-400 mb-0">{{ $client->email }}</p>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Company --}}
                                        <td class="text-sm text-gray-600">{{ $client->company_name ?: '—' }}</td>

                                        {{-- Phone --}}
                                        <td class="text-sm text-gray-600 font-mono" dir="ltr">{{ $client->phone ?: '—' }}</td>

                                        {{-- Location --}}
                                        <td class="text-sm">
                                            @if ($client->city || $client->country)
                                                <span class="text-gray-700">{{ $client->city }}</span>
                                                @if ($client->city && $client->country)<br>@endif
                                                <span class="text-gray-400 text-xs">{{ $client->country }}</span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>

                                        {{-- Subscriptions count --}}
                                        <td>
                                            <span class="inline-flex items-center justify-center w-7 h-7 bg-blue-50 text-blue-600 rounded-full text-xs font-semibold">
                                                {{ $client->subscriptions_count ?? 0 }}
                                            </span>
                                        </td>

                                        {{-- Domains count --}}
                                        <td>
                                            <span class="inline-flex items-center justify-center w-7 h-7 bg-emerald-50 text-emerald-600 rounded-full text-xs font-semibold">
                                                {{ $client->domains_count ?? 0 }}
                                            </span>
                                        </td>

                                        {{-- Last Login --}}
                                        <td class="text-sm">
                                            @if ($lastLogin)
                                                <span class="text-gray-600">{{ $lastLogin->diffForHumans() }}</span>
                                                <br>
                                                <span class="text-xs text-gray-400">{{ $lastLogin->format('Y/m/d') }}</span>
                                            @else
                                                <span class="text-gray-300 text-xs">{{ t('dashboard.Never', 'Never') }}</span>
                                            @endif
                                        </td>

                                        {{-- Joined --}}
                                        <td class="text-sm">
                                            <span class="text-gray-600">{{ $client->created_at->format('Y/m/d') }}</span>
                                            <br>
                                            <span class="text-xs text-gray-400">{{ $client->created_at->diffForHumans() }}</span>
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            @if ($client->status === 'active')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    {{ t('dashboard.Status_Active', 'Active') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                                    {{ t('dashboard.Client_Inactive', 'Inactive') }}
                                                </span>
                                            @endif
                                            @if (!$client->can_login)
                                                <br>
                                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700">
                                                    <i class="ti ti-lock text-xs"></i>
                                                    {{ t('dashboard.No_Login', 'No Login') }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="whitespace-nowrap">
                                            <div class="flex items-center gap-0.5">

                                                @can('view', 'App\\Models\\Client')
                                                    <a href="{{ route('dashboard.clients.show', ['client' => $client, 'tab' => 'details']) }}"
                                                       title="{{ t('dashboard.View_Details', 'View Details') }}"
                                                       class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                                        <i class="ti ti-eye text-base leading-none"></i>
                                                    </a>
                                                @endcan

                                                @can('edit', 'App\\Models\\Client')
                                                    <a href="{{ route('dashboard.clients.edit', $client) }}"
                                                       title="{{ t('dashboard.Edit', 'Edit') }}"
                                                       class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary hover:bg-yellow-50 hover:text-yellow-600 transition-colors">
                                                        <i class="ti ti-edit text-base leading-none"></i>
                                                    </a>
                                                @endcan

                                                @can('login', 'App\\Models\\Client')
                                                    <a href="{{ route('dashboard.clients.impersonate', $client) }}"
                                                       title="{{ t('dashboard.Login_As_Client', 'Login as client') }}"
                                                       class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary hover:bg-purple-50 hover:text-purple-600 transition-colors">
                                                        <i class="ti ti-login text-base leading-none"></i>
                                                    </a>
                                                @endcan

                                                @can('delete', 'App\\Models\\Client')
                                                    <form method="POST" action="{{ route('dashboard.clients.destroy', $client) }}"
                                                          style="display:inline-block"
                                                          onsubmit="return confirm('{{ t('dashboard.Confirm_Delete_Client', 'Are you sure you want to delete this client?') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                title="{{ t('dashboard.Delete', 'Delete') }}"
                                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary hover:bg-red-50 hover:text-red-600 transition-colors">
                                                            <i class="ti ti-trash text-base leading-none"></i>
                                                        </button>
                                                    </form>
                                                @endcan

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11">
                                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                                @if($search)
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Search_Results', 'No results found') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.Try_Different_Search', 'Try a different search term') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.clients') }}" class="btn btn-light btn-sm">
                                                        {{ t('dashboard.Clear_Search', 'Clear search') }}
                                                    </a>
                                                @else
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Clients', 'No clients yet') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.No_Clients_Desc', 'Start by adding your first client') }}
                                                    </p>
                                                    @can('create', 'App\\Models\\Client')
                                                        <a href="{{ route('dashboard.clients.create') }}"
                                                           class="btn btn-primary btn-sm flex items-center gap-2">
                                                            <i class="ti ti-plus text-base"></i>
                                                            {{ t('dashboard.Add_Client', 'Add Client') }}
                                                        </a>
                                                    @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($clients->hasPages())
                        <div class="mt-4">
                            {{ $clients->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
