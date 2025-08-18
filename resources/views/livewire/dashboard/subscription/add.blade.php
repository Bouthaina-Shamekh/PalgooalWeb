    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript: void(0)">Subscriptions</a></li>
                <li class="breadcrumb-item" aria-current="page">Subscriptions Add</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Subscriptions Add</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    {{--Success messages--}}
                    @if (session()->has('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <form wire:submit.prevent="save" class="grid grid-cols-12 gap-x-6">
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client</label>
                                <select id="client_id" wire:model.defer="subscription.client_id" class="form-select">
                                    <option value="">-- Select Client --</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->first_name }} {{ $client->last_name }}</option>
                                    @endforeach
                                </select>
                                @error('client_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <label class="form-label">Plan</label>
                                <select wire:model.defer="subscription.plan_id" class="form-select">
                                    <option value="">-- Select Plan --</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                    @endforeach
                                </select>
                                @error('client_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.select
                                label="Domain Option"
                                wire:model.defer="subscription.domain_option"
                                name="status"
                                :options="[
                                    'new' => 'Register New Domain',
                                    'subdomain' => 'Use Our Subdomain',
                                    'existing' => 'Use Existing Domain',
                                ]"
                            />
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                label="Domain Name"
                                wire:model.defer="subscription.domain_name"
                                name="domain_name"
                                type="text"
                                placeholder="e.g. example.com or client.palgoals.com"
                            />
                            @error('subscription.domain_name')
                            <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                label="Starts At"
                                wire:model.defer="subscription.start_date"
                                name="start_date"
                                type="date"
                                placeholder="e.g. example.com or client.palgoals.com"
                            />
                            @error('subscription.domain_name')
                            <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                label="Ends At"
                                wire:model.defer="subscription.end_date"
                                name="end_date"
                                type="date"
                                placeholder="e.g. example.com or client.palgoals.com"
                            />
                            @error('subscription.end_date')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.select
                                label="Domain Option"
                                wire:model.defer="subscription.status"
                                name="status"
                                :options="[
                                    'active' => 'active',
                                    'pending' => 'pending',
                                    'canceled' => 'canceled',
                                ]"
                            />
                            @error('subscription.status')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
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
    <!-- [ Main Content ] end -->
