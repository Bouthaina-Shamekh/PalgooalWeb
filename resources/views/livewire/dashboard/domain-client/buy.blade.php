
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript: void(0)">Clients</a></li>
                <li class="breadcrumb-item" aria-current="page">Domain Buy</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Domain Buy</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->

    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-body">
                    {{--Success messages--}}
                    @if (session()->has('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                    @endif

                    <form wire:submit.prevent="save" class="grid grid-cols-12 gap-x-6">
                        @csrf
                        <input type="hidden" wire:model="domain.client_id">
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <x-form.input
                                    label="Domain Name"
                                    wire:model="domainData.domain_name"
                                    disabled
                                    name="domain_name"
                                    type="text"
                                    placeholder="e.g. example.com or client.palgoals.com"
                                />
                                @error('domainData.domain_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <label for="registrar" class="form-label">Registrar Domain</label>
                                <select id="registrar" wire:model="domainData.registrar" class="form-select">
                                    <option value="" @selected($domainData['registrar'] == '')>-- Select Registrar Domain --</option>
                                    <option value="enom" @selected($domainData['registrar'] == 'enom')>enom</option>
                                    <option value="namcheap" @selected($domainData['registrar'] == 'namcheap')>namcheap</option>
                                </select>
                                @error('domainData.registrar') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <x-form.input
                                    label="Registration Date"
                                    wire:model.defer="domainData.registration_date"
                                    name="registration_date"
                                    type="date"
                                    placeholder="Registration Date"
                                />
                                @error('domainData.registration_date') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <x-form.input
                                    label="Renewal Date"
                                    wire:model.defer="domainData.renewal_date"
                                    name="domain_name"
                                    type="date"
                                    placeholder="Renewal Date"
                                />
                                @error('domainData.renewal_date') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <label for="domain.status" class="form-label">Status</label>
                                <select id="domain.status" wire:model.defer="domainData.status" class="form-select">
                                    <option value="">-- Select Registrar Domain --</option>
                                    <option value="active" @selected($domainData['status'] == 'active')>active</option>
                                    <option value="expired" @selected($domainData['status'] == 'expired')>expired</option>
                                    <option value="pending" @selected($domainData['status'] == 'pending')>pending</option>
                                </select>
                                @error('domainData.status') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-span-12 text-right">
                            <button type="button" wire:click="showSearch" class="btn btn-secondary">Cancel</button>
                            <button type="button" wire:click="save" class="btn btn-primary">Buy</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
