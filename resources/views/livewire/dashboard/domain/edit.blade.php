<!-- [ breadcrumb ] start -->
<div class="page-header">
    <div class="page-block">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript: void(0)">Domains</a></li>
            <li class="breadcrumb-item" aria-current="page">Domains Edit</li>
        </ul>
        <div class="page-header-title">
            <h2 class="mb-0">Domains Edit</h2>
        </div>
    </div>
</div>
<!-- [ breadcrumb ] end -->
<!-- [ Main Content ] start -->
<div class="grid grid-cols-12 gap-x-6">
    <div class="col-span-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Domain</h5>
            </div>
            <div class="card-body">
                <form wire:submit.prevent="save" class="grid grid-cols-12 gap-x-6">
                    <div class="col-span-12 md:col-span-6">
                        <label for="client_id">Client</label>
                        <select name="client_id" id="" wire:model="domain.client_id" class="form-select">
                            <option value="">Select Client</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" @selected($domain['client_id'] == $client->id)>{{ $client->first_name }} {{ $client->last_name }}</option>
                            @endforeach
                        </select>
                        @error('domain.client_id') <span class="text-red-600">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <x-form.input
                                label="Domain Name"
                                wire:model="domain.domain_name"
                                name="domain_name"
                                type="text"
                                placeholder="e.g. example.com or client.palgoals.com"
                            />
                            @error('domain_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <label for="registrar" class="form-label">Registrar Domain</label>
                            <select id="registrar" wire:model="domain.registrar" class="form-select">
                                <option value="" @selected($domain['registrar'] == '')>-- Select Registrar Domain --</option>
                                    <option value="enom" @selected($domain['registrar'] == 'enom')>enom</option>
                                    <option value="namcheap" @selected($domain['registrar'] == 'namcheap')>namcheap</option>
                            </select>
                            @error('registrar') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <x-form.input
                                label="Registration Date"
                                wire:model.defer="domain.registration_date"
                                name="registration_date"
                                type="date"
                                placeholder="Registration Date"
                            />
                            @error('registration_date') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <x-form.input
                                label="Renewal Date"
                                wire:model.defer="domain.renewal_date"
                                name="domain_name"
                                type="date"
                                placeholder="Renewal Date"
                            />
                            @error('renewal_date') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <label for="domain.status" class="form-label">Status</label>
                            <select id="domain.status" wire:model.defer="domain.status" class="form-select">
                                <option value="">-- Select Registrar Domain --</option>
                                    <option value="active" @selected($domain['status'] == 'active')>active</option>
                                    <option value="expired" @selected($domain['status'] == 'expired')>expired</option>
                                    <option value="pending" @selected($domain['status'] == 'pending')>pending</option>
                            </select>
                            @error('status') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 text-right">
                        <button type="button" wire:click="showIndex" class="btn btn-secondary">Cancel</button>
                        <button type="button" wire:click="save" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

