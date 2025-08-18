<div class="page-header">
    <div class="page-block">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">Home</a></li>
            <li class="breadcrumb-item"><a href="#">Sites</a></li>
            <li class="breadcrumb-item" aria-current="page">Sites List</li>
        </ul>
        <div class="page-header-title">
            <h2 class="mb-0">Sites List</h2>
        </div>
    </div>
</div>
<div class="grid grid-cols-12 gap-x-6">
    <div class="col-span-12">
        <div class="card table-card">
            <div class="card-header">
                <div class="sm:flex items-center justify-between">
                    <h5 class="mb-3 sm:mb-0">Sites List</h5>
                    <div>
                        <a href="#" wire:click="showAdd" class="btn btn-primary">Add Site</a>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between mb-4 px-5 py-4">
                <x-form.input type="text" wire:model="search" wire:input="updateSearch" placeholder="Search Sites..." />
                <x-form.select wire:model="perPage" wire:change="updatePerPage" name="perPage"
                    :options="[
                        '5' => '5 per page',
                        '10' => '10 per page',
                        '25' => '25 per page',
                    ]"
                />
            </div>
            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Domain</th>
                                <th>Status</th>
                                <th>Provisioned At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sites as $site)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $site->client->first_name }} {{ $site->client->last_name }} </td>
                                <td>{{ $site->domain->domain_name }}</td>
                                <td>{{ $site->provisioning_status }}</td>
                                <td>{{ optional($site->provisioned_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td>
                                    <a wire:click="showView({{ $site->id }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                        <i class="ti ti-eye text-xl leading-none"></i>
                                    </a>
                                    <a wire:click="showEdit({{ $site->id }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                        <i class="ti ti-edit text-xl leading-none"></i>
                                    </a>
                                    <a wire:click="delete({{ $site->id }})" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                        <i class="ti ti-trash text-xl leading-none"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-gray-500">No Sites found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4">
                {{ $sites->links() }}
            </div>
        </div>
    </div>
</div>
