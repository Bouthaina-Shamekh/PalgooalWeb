<div>
    <div class="alert alert-{{ $alertType }}  justify-between items-center {{ $alert === false ? 'hidden' : 'flex' }}">
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
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript: void(0)">General Setting</a></li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">General Setting</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">General Setting</h5>
                </div>
                <div class="card-body">
                    {{--Success messages--}}
                    @if (session()->has('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                    @endif
                    <form wire:submit.prevent="save" class="grid grid-cols-12 gap-x-6">
                        <!-- Site Title -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                name="site_title"
                                wire:model="generalSetting.site_title"
                                label="Site Title" />
                            @error('generalSetting.site_title') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Site Discretion -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input
                                name="site_discretion"
                                wire:model="generalSetting.site_discretion"
                                label="Site Discretion" />
                            @error('generalSetting.site_discretion') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Logo -->
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">Logo</label>
                            <input type="file"
                                wire:model="generalSetting.logo"
                                accept="image/*"
                                class="form-control" />
                            @if ($generalSetting['logo'])
                                <img src="{{ asset('storage/' . $generalSetting['logo']) }}" width="50" alt="Logo" class="mt-2">
                            @endif
                            @error('generalSetting.logo') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Dark Logo -->
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">Dark Logo</label>
                            <input type="file"
                                wire:model="generalSetting.dark_logo"
                                accept="image/*"
                                class="form-control" />
                            @if ($generalSetting['dark_logo'])
                                <img src="{{ asset('storage/' . $generalSetting['dark_logo']) }}" width="50" alt="Dark Logo" class="mt-2">
                            @endif
                            @error('generalSetting.dark_logo') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Sticky Logo -->
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">Sticky Logo</label>
                            <input type="file"
                                wire:model="generalSetting.sticky_logo"
                                accept="image/*"
                                class="form-control" />
                            @if ($generalSetting['sticky_logo'])
                                <img src="{{ asset('storage/' . $generalSetting['sticky_logo']) }}" width="50" alt="Sticky Logo" class="mt-2">
                            @endif
                            @error('generalSetting.sticky_logo') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Dark Sticky Logo -->
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">Dark Sticky Logo</label>
                            <input type="file"
                                wire:model="generalSetting.dark_sticky_logo"
                                accept="image/*"
                                class="form-control" />
                            @if ($generalSetting['dark_sticky_logo'])
                                <img src="{{ asset('storage/' . $generalSetting['dark_sticky_logo']) }}" width="50" alt="Dark Sticky Logo" class="mt-2">
                            @endif
                            @error('generalSetting.dark_sticky_logo') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Admin Logo -->
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">Admin Logo</label>
                            <input type="file"
                                wire:model="generalSetting.admin_logo"
                                accept="image/*"
                                class="form-control" />
                            @if ($generalSetting['admin_logo'])
                                <img src="{{ asset('storage/' . $generalSetting['admin_logo']) }}" width="50" alt="Admin Logo" class="mt-2">
                            @endif
                            @error('generalSetting.admin_logo') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Admin Dark Logo -->
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">Admin Dark Logo</label>
                            <input type="file"
                                wire:model="generalSetting.admin_dark_logo"
                                accept="image/*"
                                class="form-control" />
                            @if ($generalSetting['admin_dark_logo'])
                                <img src="{{ asset('storage/' . $generalSetting['admin_dark_logo']) }}" width="50" alt="Admin Dark Logo" class="mt-2">
                            @endif
                            @error('generalSetting.admin_dark_logo') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Favicon -->
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">Favicon</label>
                            <input type="file"
                                wire:model="generalSetting.favicon"
                                accept="image/*"
                                class="form-control" />
                            @if ($generalSetting['favicon'])
                                <img src="{{ asset('storage/' . $generalSetting['favicon']) }}" width="50" alt="Favicon" class="mt-2">
                            @endif
                            @error('generalSetting.favicon') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Default Language -->
                        <div class="col-span-12 md:col-span-6">
                            <select wire:model="generalSetting.default_language" class="form-control">
                                <option value="">Select Language</option>
                                @foreach ($languages as $language)
                                    <option value="{{ $language->code }}" @selected($language->code == $generalSetting->default_language)>{{ $language->name }}</option>
                                @endforeach
                            </select>
                            @error('generalSetting.default_language') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="col-span-12 text-right">
                            <button type="button" wire:click="showIndex" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
</div>
