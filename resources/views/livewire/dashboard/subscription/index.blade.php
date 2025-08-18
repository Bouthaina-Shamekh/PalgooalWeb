<div>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Subscriptions</a></li>
                <li class="breadcrumb-item" aria-current="page">Subscriptions List</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Subscriptions List</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 sm:mb-0">Subscriptions List</h5>
                        <div>
                           <a href="#" wire:click="showAdd" class="btn btn-primary">Add Subscription</a>
                           {{-- <a href="#" wire:click="resetForm" class="btn btn-primary">Add Subscription</a> --}}
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
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Plan</th>
                                    <th>Starts At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($subscriptions as $sub)
                                <tr>
                                    <td>{{ $sub->id }}</td>
                                    <td>{{ $sub->client->company_name }}</td>
                                    <td>{{ $sub->plan->name }}</td>
                                    <td>{{ $sub->start_date}}</td>
                                    <td>
                                    <a wire:click="showView({{ $sub->id }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-eye text-xl leading-none"></i>
                                        </a>
                                        <a wire:click="showEdit({{ $sub->id }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-edit text-xl leading-none"></i>
                                        </a>
                                        <a wire:click="delete({{ $sub->id }})" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-trash text-xl leading-none"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">
                    {{ $subscriptions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
