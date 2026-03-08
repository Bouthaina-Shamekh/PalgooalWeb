<x-client-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('client.home') }}">Home</a></li>
                <li class="breadcrumb-item" aria-current="page">Update Account</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Update Account</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Update Account {{ $client->first_name }} - {{ $client->last_name }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('client.update_account.save') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-12 gap-x-6">
                        @csrf

                        <div class="col-span-12">
                            <h4 class="text-lg font-semibold mb-4 flex items-center">
                                <i class="ti ti-user mr-2"></i>Basic Information
                            </h4>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="first_name" class="form-label">First Name</label>
                            <input id="first_name" name="first_name" type="text" class="form-control"
                                value="{{ old('first_name', $client->first_name) }}" required>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input id="last_name" name="last_name" type="text" class="form-control"
                                value="{{ old('last_name', $client->last_name) }}" required>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="company_name" class="form-label">Company Name</label>
                            <input id="company_name" name="company_name" type="text" class="form-control"
                                value="{{ old('company_name', $client->company_name) }}">
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="email" class="form-label">Email Address</label>
                            <input id="email" name="email" type="email" class="form-control"
                                value="{{ old('email', $client->email) }}" required>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="phone" class="form-label">Mobile Number</label>
                            <input id="phone" name="phone" type="text" class="form-control"
                                value="{{ old('phone', $client->phone) }}" required>
                        </div>

                        <div class="col-span-12 mt-6">
                            <h4 class="text-lg font-semibold mb-4 flex items-center">
                                <i class="ti ti-map-pin mr-2"></i>Address Information
                            </h4>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="country" class="form-label">Country</label>
                            <select id="country" name="country" class="form-select">
                                @foreach ($countryOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('country', $client->country ?? '') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="city" class="form-label">City</label>
                            <input id="city" name="city" type="text" class="form-control"
                                value="{{ old('city', $client->city) }}">
                        </div>

                        <div class="col-span-12 md:col-span-8">
                            <label for="address" class="form-label">Address</label>
                            <textarea id="address" name="address" rows="3" class="form-control" placeholder="Enter full address">{{ old('address', $client->address) }}</textarea>
                        </div>

                        <div class="col-span-12 md:col-span-4">
                            <label for="zip_code" class="form-label">Zip Code</label>
                            <input id="zip_code" name="zip_code" type="text" class="form-control"
                                value="{{ old('zip_code', $client->zip_code) }}">
                        </div>

                        <div class="col-span-12 mt-6">
                            <h4 class="text-lg font-semibold mb-4 flex items-center">
                                <i class="ti ti-shield-lock mr-2"></i>Change Password (Optional)
                            </h4>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                                <p class="text-sm text-yellow-800 mb-0">
                                    <i class="ti ti-info-circle mr-1"></i>
                                    Leave password fields empty to keep the current password unchanged.
                                </p>
                            </div>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="password" class="form-label">New Password</label>
                            <input id="password" name="password" type="password" class="form-control"
                                placeholder="Enter new password...">
                            <small class="text-muted">Minimum 8 characters, including uppercase, lowercase, number, and special character.</small>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control"
                                placeholder="Confirm new password...">
                        </div>

                        <div class="col-span-12 mt-6">
                            <h4 class="text-lg font-semibold mb-4 flex items-center">
                                <i class="ti ti-photo mr-2"></i>Profile Picture
                            </h4>
                        </div>

                        <div class="col-span-12">
                            <label for="avatar" class="form-label">Avatar</label>
                            <input id="avatar" name="avatar" type="file" accept="image/*" class="form-control">

                            <div class="mt-4 flex items-center gap-6">
                                @if ($client->avatar)
                                    <div class="text-center">
                                        <div class="relative">
                                            <img src="{{ asset('storage/' . $client->avatar) }}"
                                                class="w-20 h-20 rounded-full object-cover border-4 border-gray-200 shadow-sm"
                                                alt="Current avatar">
                                            <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs">
                                                <i class="ti ti-user"></i>
                                            </div>
                                        </div>
                                        <span class="text-sm text-gray-600 mt-2 block font-medium">Current Avatar</span>
                                    </div>
                                @else
                                    <div class="text-center">
                                        <div class="relative">
                                            <img src="{{ asset('assets/images/user/avatar-1.jpg') }}"
                                                class="w-20 h-20 rounded-full object-cover border-4 border-gray-200 shadow-sm"
                                                alt="Default avatar">
                                            <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-gray-400 text-white rounded-full flex items-center justify-center text-xs">
                                                <i class="ti ti-photo"></i>
                                            </div>
                                        </div>
                                        <span class="text-sm text-gray-500 mt-2 block">Default Avatar</span>
                                    </div>
                                @endif

                                <div class="flex-1">
                                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                        <h6 class="font-medium text-gray-900 mb-2">Upload Instructions:</h6>
                                        <ul class="text-sm text-gray-600 space-y-1">
                                            <li>* Recommended size: 200x200 pixels</li>
                                            <li>* Supported formats: JPG, PNG, GIF</li>
                                            <li>* Maximum file size: 2MB</li>
                                            <li>* Square images work best</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-12 flex justify-end gap-3 mt-8 pt-6 border-t">
                            <a href="{{ route('client.home') }}" class="btn btn-secondary">
                                <i class="ti ti-x mr-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy mr-2"></i>Update Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-client-layout>
