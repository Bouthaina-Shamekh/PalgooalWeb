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
                                    <th>Company</th>
                                    <th>Email</th>
                                    <th>Subscriptions</th>
                                    <th>Sites</th>
                                    <th>Joined</th>
                                    <th>STATUS</th>
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
                                                <img src="{{ $client->avatar ? asset('storage/' . $client->avatar) : asset('assets/images/user/avatar-1.jpg') }}" class="rounded-full w-10" />
                                            </div>
                                            <div class="grow ltr:ml-3 rtl:mr-3">
                                                <h6 class="mb-0">{{ $client->first_name }} {{ $client->last_name }}</h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $client->email }}</td>
                                    <td>0</td>
                                    <td>0</td>
                                    <td>{{ $client->created_at->toDateString() }}</td>
                                    <td>
                                        @if ($client->can_login)
                                        <span class="badge bg-success-500/10 text-success-500 rounded-full text-sm">Active</span>
                                        @else
                                        <span class="badge text-danger bg-danger-500/10 rounded-full text-sm">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a wire:click="view({{ $client->id }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-eye text-xl leading-none"></i>
                                        </a>
                                        <a wire:click="showEdit({{ $client->id }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-edit text-xl leading-none"></i>
                                        </a>
                                        <a wire:click="delete({{ $client->id }})" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-trash text-xl leading-none"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-gray-500">No clients found.</td>
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
