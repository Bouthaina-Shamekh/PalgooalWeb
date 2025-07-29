<div>
    <div class="alert alert-{{ $alertType }} justify-between items-center {{ $alert === false ? 'hidden' : 'flex' }}">
        {{ $alertMessage }}
        <button type="button" class="btn-close" wire:click="closeModal">
            <span class="pc-micon">
                <i class="material-icons-two-tone pc-icon">close</i>
            </span>
        </button>
    </div>

    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript: void(0)">Clients</a></li>
                <li class="breadcrumb-item" aria-current="page">Update Account</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Update Account</h2>
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

                        <!-- First & Last Name -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                name="first_name"
                                wire:model="client.first_name"
                                label="First Name" />
                            @error('client.first_name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                name="last_name"
                                wire:model="client.last_name"
                                label="Last Name" />
                            @error('client.last_name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                name="email"
                                type="email"
                                wire:model="client.email"
                                label="Email" />
                            @error('client.email') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                name="phone"
                                type="number"
                                wire:model="client.phone"
                                label="Mobile Number" required />
                            @error('client.phone') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                name="password"
                                type="password"
                                wire:model="client.password"
                                wire:input="checkPassword"
                                label="Password" />
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
                            <x-form.input
                                name="confirm_password"
                                type="password"
                                wire:model="client.confirm_password"
                                wire:input="checkPassword"
                                label="Confirm Password" />
                            @error('client.confirm_password') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                name="company_name"
                                wire:model="client.company_name"
                                label="Company Name" />
                            @error('client.company_name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <div>
                                <label class="form-label">Avatar</label>
                                <input type="file"
                                    wire:model="client.avatar"
                                    accept="image/*"
                                    class="form-control" />
                                @error('client.avatar') <span class="text-red-600">{{ $message }}</span> @enderror

                                @if($client['avatar_url'])
                                <div class="mt-2">
                                    <img src="{{ Storage::url($client['avatar_url']) }}"
                                         alt="Current Avatar"
                                         class="w-16 h-16 rounded-full object-cover" />
                                </div>
                                @endif
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
</div>
