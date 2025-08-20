<div>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Clients</a></li>
                <li class="breadcrumb-item" aria-current="page">Clients List</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Clients List</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 sm:mb-0">Clients List</h5>
                        <div>
                           <a href="#" wire:click="showAdd" class="btn btn-primary">Add Client</a>
                           {{-- <a href="#" wire:click="resetForm" class="btn btn-primary">Add Client</a> --}}
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <input type="text" wire:model="search" wire:input="updateSearch" placeholder="Search clients..." />
                    <select wire:model="perPage" wire:change="updatePerPage" class="border rounded px-2 py-1">
                        <option value="5">5 per page</option>
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                    </select>
                </div>
                <div class="card-body pt-3">
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
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="flex items-center w-44">
                                            <div class="shrink-0">
                                                <img src="{{ $client->avatar ? asset('storage/' . $client->avatar) : asset('assets/images/user/avatar-1.jpg') }}"
                                                     class="rounded-full w-10 h-10 object-cover" />
                                            </div>
                                            <div class="grow ltr:ml-3 rtl:mr-3">
                                                <h6 class="mb-0 font-semibold">{{ $client->first_name }} {{ $client->last_name }}</h6>
                                                <p class="text-sm text-gray-500 mb-0">{{ $client->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-sm">{{ $client->company_name ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-sm">{{ $client->phone ?: '-' }}</span>
                                    </td>
                                    <td>
                                        <div class="text-sm">
                                            @if($client->city || $client->country)
                                                <span>{{ $client->city }}</span>
                                                @if($client->city && $client->country)<br>@endif
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
                                            @if($client->last_login_at)
                                                <span class="text-gray-600">{{ $client->last_login_at->diffForHumans() }}</span>
                                                <br>
                                                <span class="text-xs text-gray-400">{{ $client->last_login_at->format('M j, Y') }}</span>
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
                                            <button wire:click="showDetails({{ $client->id }})"
                                                    class="w-8 h-8 rounded-lg inline-flex items-center justify-center btn-link-secondary hover:bg-blue-50 hover:text-blue-600 transition-colors"
                                                    title="View Details">
                                                <i class="ti ti-eye text-lg leading-none"></i>
                                            </button>

                                            <button wire:click="showEdit({{ $client->id }})"
                                                    class="w-8 h-8 rounded-lg inline-flex items-center justify-center btn-link-secondary hover:bg-yellow-50 hover:text-yellow-600 transition-colors"
                                                    title="Edit Client">
                                                <i class="ti ti-edit text-lg leading-none"></i>
                                            </button>

                                            <button wire:click="delete({{ $client->id }})"
                                                    onclick="confirm('Are you sure you want to delete this client?') || event.stopImmediatePropagation()"
                                                    class="w-8 h-8 rounded-lg inline-flex items-center justify-center btn-link-secondary hover:bg-red-50 hover:text-red-600 transition-colors"
                                                    title="Delete Client">
                                                <i class="ti ti-trash text-lg leading-none"></i>
                                            </button>
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
                </div>
                <div class="mt-4">
                    {{ $clients->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
