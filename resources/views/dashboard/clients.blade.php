<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">Home</a></li>
                <li class="breadcrumb-item" aria-current="page">Clients</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Clients List</h2>
            </div>
        </div>
    </div>

    @include('dashboard.clients._alerts')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="sm:flex items-center justify-between gap-3">
                        <h5 class="mb-3 sm:mb-0">Clients List</h5>
                        @can('create', 'App\\Models\\Client')
                            <a href="{{ route('dashboard.clients.create') }}" class="btn btn-primary">Add Client</a>
                        @endcan
                    </div>
                </div>

                <div class="card-body pt-3">
                    <form method="GET" action="{{ route('dashboard.clients') }}" class="flex flex-col sm:flex-row gap-3 mb-4">
                        <input type="text" name="search" class="form-control" placeholder="Search clients..."
                            value="{{ $search }}">
                        <select name="per_page" class="form-select max-w-40" onchange="this.form.submit()">
                            <option value="5" @selected($perPage === 5)>5 per page</option>
                            <option value="10" @selected($perPage === 10)>10 per page</option>
                            <option value="25" @selected($perPage === 25)>25 per page</option>
                        </select>
                        <button type="submit" class="btn btn-outline-primary">Search</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Client</th>
                                    <th>Company</th>
                                    <th>Phone</th>
                                    <th>Location</th>
                                    <th>Subscriptions</th>
                                    <th>Domains</th>
                                    <th>Last Login</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($clients as $client)
                                    @php
                                        $lastLogin = $client->last_login_at ? \Illuminate\Support\Carbon::parse($client->last_login_at) : null;
                                    @endphp
                                    <tr>
                                        <td>{{ ($clients->firstItem() ?? 1) + $loop->index }}</td>
                                        <td>
                                            <div class="flex items-center w-44">
                                                <div class="shrink-0">
                                                    <img
                                                        src="{{ $client->avatar ? asset('storage/' . $client->avatar) : asset('assets/images/user/avatar-1.jpg') }}"
                                                        alt="{{ trim(($client->first_name ?? '') . ' ' . ($client->last_name ?? '')) ?: 'Client avatar' }}"
                                                        class="rounded-full w-10 h-10 object-cover" />
                                                </div>
                                                <div class="grow ltr:ml-3 rtl:mr-3">
                                                    <h6 class="mb-0 font-semibold">{{ $client->first_name }} {{ $client->last_name }}</h6>
                                                    <p class="text-sm text-gray-500 mb-0">{{ $client->email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="text-sm">{{ $client->company_name ?: '-' }}</span></td>
                                        <td><span class="text-sm">{{ $client->phone ?: '-' }}</span></td>
                                        <td>
                                            <div class="text-sm">
                                                @if ($client->city || $client->country)
                                                    <span>{{ $client->city }}</span>
                                                    @if ($client->city && $client->country)
                                                        <br>
                                                    @endif
                                                    <span class="text-gray-500">{{ $client->country }}</span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-full text-sm font-semibold">
                                                {{ $client->subscriptions_count ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="inline-flex items-center justify-center w-8 h-8 bg-green-100 text-green-600 rounded-full text-sm font-semibold">
                                                {{ $client->domains_count ?? 0 }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-sm">
                                                @if ($lastLogin)
                                                    <span class="text-gray-600">{{ $lastLogin->diffForHumans() }}</span>
                                                    <br>
                                                    <span class="text-xs text-gray-400">{{ $lastLogin->format('M j, Y') }}</span>
                                                @else
                                                    <span class="text-gray-400">Never</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="text-sm">
                                                <span class="text-gray-600">{{ $client->created_at->format('M j, Y') }}</span>
                                                <br>
                                                <span class="text-xs text-gray-400">{{ $client->created_at->diffForHumans() }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($client->status === 'active')
                                                <span class="badge bg-success-500/10 text-success-500 rounded-full text-sm px-3 py-1">
                                                    <i class="ti ti-check w-3 h-3 mr-1"></i>Active
                                                </span>
                                            @else
                                                <span class="badge bg-danger-500/10 text-danger-500 rounded-full text-sm px-3 py-1">
                                                    <i class="ti ti-ban w-3 h-3 mr-1"></i>Inactive
                                                </span>
                                            @endif

                                            @if (!$client->can_login)
                                                <br>
                                                <span class="badge bg-warning-500/10 text-warning-500 rounded-full text-xs px-2 py-1 mt-1">
                                                    <i class="ti ti-lock w-2 h-2 mr-1"></i>No Login
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex items-center gap-1">
                                                @can('view', 'App\\Models\\Client')
                                                    <a href="{{ route('dashboard.clients.show', ['client' => $client, 'tab' => 'details']) }}"
                                                        class="w-8 h-8 rounded-lg inline-flex items-center justify-center btn-link-secondary hover:bg-blue-50 hover:text-blue-600 transition-colors"
                                                        title="View Details">
                                                        <i class="ti ti-eye text-lg leading-none"></i>
                                                    </a>
                                                @endcan

                                                @can('edit', 'App\\Models\\Client')
                                                    <a href="{{ route('dashboard.clients.edit', $client) }}"
                                                        class="w-8 h-8 rounded-lg inline-flex items-center justify-center btn-link-secondary hover:bg-yellow-50 hover:text-yellow-600 transition-colors"
                                                        title="Edit Client">
                                                        <i class="ti ti-edit text-lg leading-none"></i>
                                                    </a>
                                                @endcan

                                                @can('login', 'App\\Models\\Client')
                                                    <a href="{{ route('dashboard.clients.impersonate', $client) }}"
                                                        class="w-8 h-8 rounded-lg inline-flex items-center justify-center btn-link-secondary hover:bg-purple-50 hover:text-purple-600 transition-colors {{ !$client->can_login ? 'pointer-events-none opacity-50' : '' }}"
                                                        title="Login as Client" @if (!$client->can_login) aria-disabled="true" @endif>
                                                        <i class="ti ti-login text-lg leading-none"></i>
                                                    </a>
                                                @endcan

                                                @can('delete', 'App\\Models\\Client')
                                                    <form method="POST" action="{{ route('dashboard.clients.destroy', $client) }}" class="inline"
                                                        onsubmit="return confirm('Are you sure you want to delete this client?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                            class="w-8 h-8 rounded-lg inline-flex items-center justify-center btn-link-secondary hover:bg-red-50 hover:text-red-600 transition-colors"
                                                            title="Delete Client">
                                                            <i class="ti ti-trash text-lg leading-none"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center py-8">
                                            <div class="text-gray-500">
                                                <i class="ti ti-users text-4xl mb-2 opacity-50"></i>
                                                <p class="text-lg mb-1">No clients found</p>
                                                <p class="text-sm">Start by adding your first client</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $clients->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
