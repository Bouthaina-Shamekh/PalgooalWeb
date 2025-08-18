<!-- [ breadcrumb ] start -->
<div class="page-header">
    <div class="page-block">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
            <li class="breadcrumb-item"><a href="javascript: void(0)">Clients</a></li>
            <li class="breadcrumb-item" aria-current="page">Sites Add</li>
        </ul>
        <div class="page-header-title">
            <h2 class="mb-0">Sites Add</h2>
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
                <form wire:submit.prevent="submit" class="grid grid-cols-12 gap-x-6">
                    <!-- First & Last Name -->
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <label for="client_id" class="form-label">Client</label>
                            <select name="client_id" wire:model="site.client_id" class="form-select">
                                <option value="">Select Client</option>
                                @foreach ($clients as $client)
                                <option value="{{ $client['id'] }}">{{ $client['first_name'] }} {{ $client['last_name'] }}</option>
                                @endforeach
                            </select>
                            @error('site.client_id') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <label for="domain_id" class="form-label">Domain</label>
                            <select name="domain_id" wire:model="site.domain_id" class="form-select">
                                <option value="">Select Domain</option>
                                @foreach ($domains as $domain)
                                <option value="{{ $domain['id'] }}">{{ $domain['domain_name'] }}</option>
                                @endforeach
                            </select>
                            @error('site.domain_id') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <label for="subscription_id" class="form-label">Subscription</label>
                            <select name="subscription_id" wire:model="site.subscription_id" class="form-select">
                                <option value="">Select Subscription</option>
                                @foreach ($subscriptions as $subscription)
                                    <option value="{{ $subscription['id'] }}">{{ $subscription['domain_name'] }}</option>
                                @endforeach
                            </select>
                            @error('site.subscription_id') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <label for="plan_id" class="form-label">Plan</label>
                            <select name="plan_id" wire:model="site.plan_id" class="form-select">
                                <option value="">Select Plan</option>
                                @foreach ($plans as $plan)
                                <option value="{{ $plan['id'] }}">{{ $plan['name'] }}</option>
                                @endforeach
                            </select>
                            @error('site.plan_id') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <label for="site.provisioning_status" class="form-label">Provisioning Status</label>
                            <select name="site.provisioning_status" wire:model.defer="site.provisioning_status" class="form-select">
                                <option value="">Select Provisioning Status</option>
                                @foreach($provisioningStatuses as $status)
                                <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                            @error('site.provisioning_status') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <div class="mb-3">
                            <x-form.input type="url" name="site.cpanel_url" wire:model.defer="site.cpanel_url" disabled label="cPanel URL" />
                            @error('site.cpanel_url') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <x-form.input name="site.cpanel_username" type="text" wire:model.defer="site.cpanel_username" label="cPanel Username" />
                        @error('site.cpanel_username') <span class="text-red-600">{{ $message }}</span> @enderror

                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <x-form.input  name="site.cpanel_password" wire:input="checkPassword" type="password" wire:model.defer="site.cpanel_password" label="cPanel Password" />
                        @error('site.cpanel_password') <span class="text-red-600">{{ $message }}</span> @enderror
                        <span>
                            @if ($uppercase)
                            <span class="badge bg-success-500/10 text-success-500 rounded-full text-sm">Uppercase</span>
                            @else
                            <span class="badge text-danger bg-danger-500/10 rounded-full text-sm">Uppercase</span>
                            @endif
                            @if ($lowercase)
                            <span class="badge bg-success-500/10 text-success-500 rounded-full text-sm">Lowercase</span>
                            @else
                            <span class="badge text-danger bg-danger-500/10 rounded-full text-sm">Lowercase</span>
                            @endif
                            @if ($number)
                            <span class="badge bg-success-500/10 text-success-500 rounded-full text-sm">Number</span>
                            @else
                            <span class="badge text-danger bg-danger-500/10 rounded-full text-sm">Number</span>
                            @endif
                            @if ($specialChars)
                            <span class="badge bg-success-500/10 text-success-500 rounded-full text-sm">Special Character</span>
                            @else
                            <span class="badge text-danger bg-danger-500/10 rounded-full text-sm">Special Character</span>
                            @endif
                        </span>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <x-form.input name="provisioned_at" type="datetime-local" wire:model="site.provisioned_at" label="Provisioned At" />
                        @error('site.provisioned_at') <span class="text-red-600">{{ $message }}</span> @enderror
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
