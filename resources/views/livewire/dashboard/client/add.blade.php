    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript: void(0)">Clients</a></li>
                <li class="breadcrumb-item" aria-current="page">Clients Add</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Clients Add</h2>
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
                        <!-- First & Last Name -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="first_name" wire:model="client.first_name" label="First Name" />
                            @error('client.first_name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="last_name" wire:model="client.last_name" label="Last Name" />
                            @error('client.last_name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="company_name" wire:model="client.company_name" label="company name" />
                            @error('client.company_name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="phone" type="number" required wire:model="client.phone" label="Mobile Number" />
                            @error('client.phone') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="email" type="email" wire:model="client.email" label="email" />
                            @error('client.email') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.select wire:model="client.can_login" name="can_login" label="status" :options="[
                                '1'   => 'active',
                                '0' => 'inactive',
                                ]" />
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="password" wire:input="checkPassword" type="password" wire:model.defer="client.password" label="password" />
                            @error('client.password') <span class="text-red-600">{{ $message }}</span> @enderror

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
                            <x-form.input wire:input="checkPassword" wire:model.defer="client.confirm_password" type="password" label="Confirm Password" />

                            @error('client.confirm_password') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <div class="col-span-12 md:col-span-6">
                                <label class="form-label">Avatar</label>
                                <input type="file" wire:model="client.avatar" accept="image/*" class="form-control" />
                                @error('client.avatar') <span class="text-red-600">{{ $message }}</span> @enderror
                                {{-- @if ($client.avatar) --}}
                                <img src="...." class="mt-2 w-20 h-20 rounded-full" />
                                {{-- @endif --}}
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
    <!-- [ Main Content ] end -->
