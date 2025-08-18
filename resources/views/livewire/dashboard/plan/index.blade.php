<div>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Plans</a></li>
                <li class="breadcrumb-item" aria-current="page">Plans List</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Plans List</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 sm:mb-0">Plans List</h5>
                        <div>
                           <a href="#" wire:click="showAdd" class="btn btn-primary">Add Plan</a>
                           {{-- <a href="#" wire:click="resetForm" class="btn btn-primary">Add Plan</a> --}}
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between mb-4">
                    <input type="text" wire:model="search" wire:input="updateSearch" placeholder="Search plans..." />
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
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Features</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($plans as $plan)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                       {{ $plan->name }}
                                    </td>
                                    <td>{{ $plan->price }}</td>
                                    <td>
                                        @foreach (array_slice(json_decode($plan->features, true), 0, 3) as $feature)
                                            <span class="badge bg-success-500/10 text-success-500 rounded-full text-sm">{{ $feature }}</span>
                                        @endforeach
                                        @if (count(json_decode($plan->features, true)) > 3)
                                            <span class="badge bg-success-500/10 text-success-500 rounded-full text-sm">+{{ count(json_decode($plan->features, true)) - 3 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a wire:click="showEdit({{ $plan->id }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-edit text-xl leading-none"></i>
                                        </a>
                                        <a wire:click="delete({{ $plan->id }})" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-trash text-xl leading-none"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-gray-500">No plans found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">
                    {{ $plans->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
