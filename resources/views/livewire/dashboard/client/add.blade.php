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
                        <!-- Basic Information Section -->
                        <div class="col-span-12">
                            <h4 class="text-lg font-semibold mb-4 flex items-center">
                                <i class="ti ti-user mr-2"></i>Basic Information
                            </h4>
                        </div>

                        <!-- First & Last Name -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="first_name" wire:model="client.first_name" label="First Name" required />
                            @error('client.first_name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="last_name" wire:model="client.last_name" label="Last Name" required />
                            @error('client.last_name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Company & Email -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="company_name" wire:model="client.company_name" label="Company Name" />
                            @error('client.company_name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="email" type="email" wire:model="client.email" label="Email Address" required />
                            @error('client.email') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Phone & Status -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="phone" type="tel" wire:model="client.phone" label="Mobile Number" required />
                            @error('client.phone') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.select wire:model="client.status" name="status" label="Client Status" :options="[
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ]" />
                            @error('client.status') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Login Access -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.select wire:model="client.can_login" name="can_login" label="Login Access" :options="[
                                '1' => 'Can Login',
                                '0' => 'No Login Access',
                            ]" />
                            @error('client.can_login') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Address Section -->
                        <div class="col-span-12 mt-6">
                            <h4 class="text-lg font-semibold mb-4 flex items-center">
                                <i class="ti ti-map-pin mr-2"></i>Address Information
                            </h4>
                        </div>

                        <!-- Country & City -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.select wire:model="client.country" name="country" label="Country" :options="[
                                '' => 'Select Country',
                                'PS' => 'Palestine',
                                'JO' => 'Jordan',
                                'SA' => 'Saudi Arabia',
                                'AE' => 'United Arab Emirates',
                                'EG' => 'Egypt',
                                'LB' => 'Lebanon',
                                'SY' => 'Syria',
                                'IQ' => 'Iraq',
                                'KW' => 'Kuwait',
                                'QA' => 'Qatar',
                                'BH' => 'Bahrain',
                                'OM' => 'Oman',
                                'YE' => 'Yemen',
                                'US' => 'United States',
                                'GB' => 'United Kingdom',
                                'CA' => 'Canada',
                                'AU' => 'Australia',
                                'DE' => 'Germany',
                                'FR' => 'France',
                            ]" />
                            @error('client.country') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="city" wire:model="client.city" label="City" />
                            @error('client.city') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Address & Zip Code -->
                        <div class="col-span-12 md:col-span-8">
                            <label class="form-label">Address</label>
                            <textarea wire:model="client.address" name="address" rows="3" class="form-control" placeholder="Enter full address"></textarea>
                            @error('client.address') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-4">
                            <x-form.input name="zip_code" wire:model="client.zip_code" label="Zip Code" />
                            @error('client.zip_code') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Security Section -->
                        <div class="col-span-12 mt-6">
                            <h4 class="text-lg font-semibold mb-4 flex items-center">
                                <i class="ti ti-shield-lock mr-2"></i>Security Information
                            </h4>
                        </div>

                        <!-- Password -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                name="password"
                                wire:input="checkPasswordError"
                                type="password"
                                wire:model.defer="client.password"
                                label="{{ $clientId ? 'New Password (leave blank to keep current)' : 'Password' }}"
                                :required="!$clientId" />
                            @error('client.password') <span class="text-red-600">{{ $message }}</span> @enderror

                            <!-- Password Strength Indicators -->
                            @if($client['password'])
                            <div class="mt-2 space-x-1">
                                <span class="badge {{ $uppercase ? 'bg-success-500/10 text-success-500' : 'bg-danger-500/10 text-danger' }} rounded-full text-xs">
                                    <i class="ti {{ $uppercase ? 'ti-check' : 'ti-x' }} w-3 h-3 mr-1"></i>Uppercase
                                </span>
                                <span class="badge {{ $lowercase ? 'bg-success-500/10 text-success-500' : 'bg-danger-500/10 text-danger' }} rounded-full text-xs">
                                    <i class="ti {{ $lowercase ? 'ti-check' : 'ti-x' }} w-3 h-3 mr-1"></i>Lowercase
                                </span>
                                <span class="badge {{ $number ? 'bg-success-500/10 text-success-500' : 'bg-danger-500/10 text-danger' }} rounded-full text-xs">
                                    <i class="ti {{ $number ? 'ti-check' : 'ti-x' }} w-3 h-3 mr-1"></i>Number
                                </span>
                                <span class="badge {{ $specialChars ? 'bg-success-500/10 text-success-500' : 'bg-danger-500/10 text-danger' }} rounded-full text-xs">
                                    <i class="ti {{ $specialChars ? 'ti-check' : 'ti-x' }} w-3 h-3 mr-1"></i>Special Char
                                </span>
                                <span class="badge {{ strlen($client['password']) >= 8 ? 'bg-success-500/10 text-success-500' : 'bg-danger-500/10 text-danger' }} rounded-full text-xs">
                                    <i class="ti {{ strlen($client['password']) >= 8 ? 'ti-check' : 'ti-x' }} w-3 h-3 mr-1"></i>8+ Characters
                                </span>
                            </div>
                            @endif
                        </div>

                        <!-- Confirm Password -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                wire:input="checkPasswordError"
                                wire:model.defer="client.confirm_password"
                                type="password"
                                label="Confirm Password"
                                :required="!$clientId" />
                            @error('client.confirm_password') <span class="text-red-600">{{ $message }}</span> @enderror

                            @if($client['password'] && $client['confirm_password'])
                            <div class="mt-2">
                                @if($client['password'] === $client['confirm_password'])
                                    <span class="badge bg-success-500/10 text-success-500 rounded-full text-xs">
                                        <i class="ti ti-check w-3 h-3 mr-1"></i>Passwords match
                                    </span>
                                @else
                                    <span class="badge bg-danger-500/10 text-danger rounded-full text-xs">
                                        <i class="ti ti-x w-3 h-3 mr-1"></i>Passwords don't match
                                    </span>
                                @endif
                            </div>
                            @endif
                        </div>

                        <!-- Avatar Section -->
                        <div class="col-span-12 mt-6">
                            <h4 class="text-lg font-semibold mb-4 flex items-center">
                                <i class="ti ti-photo mr-2"></i>Profile Picture
                            </h4>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">Avatar</label>
                            <input type="file" wire:model="client.avatar" accept="image/*" class="form-control" />
                            @error('client.avatar') <span class="text-red-600">{{ $message }}</span> @enderror

                            <div class="mt-3 flex items-center gap-4">
                                <!-- Preview Current/New Avatar -->
                                @if ($client['avatar'])
                                    <div class="text-center">
                                        <img src="{{ $client['avatar']->temporaryUrl() }}" class="w-16 h-16 rounded-full object-cover border-2 border-gray-200" />
                                        <span class="text-xs text-gray-500 mt-1 block">New Avatar</span>
                                    </div>
                                @endif

                                <!-- Current Avatar (Edit Mode) -->
                                @if ($clientId && $client['avatar_url'])
                                    <div class="text-center">
                                        <img src="{{ asset('storage/' . $client['avatar_url']) }}" class="w-16 h-16 rounded-full object-cover border-2 border-gray-200" />
                                        <span class="text-xs text-gray-500 mt-1 block">Current Avatar</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="col-span-12 flex justify-end gap-3 mt-8 pt-6 border-t">
                            <button type="button" wire:click="showIndex" class="btn btn-secondary">
                                <i class="ti ti-x mr-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check mr-2"></i>{{ $clientId ? 'Update Client' : 'Create Client' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
