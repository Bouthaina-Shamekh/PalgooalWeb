@php
    $isEdit = isset($client) && $client;
    $clientCanLogin = old('can_login', $isEdit ? (string) (int) $client->can_login : '1');
    $clientStatus = old('status', $isEdit ? $client->status : 'active');
    $clientCountry = old('country', $isEdit ? ($client->country ?? '') : '');
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-12 gap-x-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="col-span-12">
        <h4 class="text-lg font-semibold mb-4 flex items-center">
            <i class="ti ti-user mr-2"></i>Basic Information
        </h4>
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="first_name" class="form-label">First Name</label>
        <input id="first_name" name="first_name" type="text" class="form-control"
            value="{{ old('first_name', $isEdit ? $client->first_name : '') }}" required>
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="last_name" class="form-label">Last Name</label>
        <input id="last_name" name="last_name" type="text" class="form-control"
            value="{{ old('last_name', $isEdit ? $client->last_name : '') }}" required>
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="company_name" class="form-label">Company Name</label>
        <input id="company_name" name="company_name" type="text" class="form-control"
            value="{{ old('company_name', $isEdit ? $client->company_name : '') }}">
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="email" class="form-label">Email Address</label>
        <input id="email" name="email" type="email" class="form-control"
            value="{{ old('email', $isEdit ? $client->email : '') }}" required>
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="phone" class="form-label">Mobile Number</label>
        <input id="phone" name="phone" type="text" class="form-control"
            value="{{ old('phone', $isEdit ? $client->phone : '') }}" required>
    </div>

    <div class="col-span-12 md:col-span-3">
        <label for="status" class="form-label">Client Status</label>
        <select id="status" name="status" class="form-select" required>
            <option value="active" @selected($clientStatus === 'active')>Active</option>
            <option value="inactive" @selected($clientStatus === 'inactive')>Inactive</option>
        </select>
    </div>

    <div class="col-span-12 md:col-span-3">
        <label for="can_login" class="form-label">Login Access</label>
        <select id="can_login" name="can_login" class="form-select" required>
            <option value="1" @selected($clientCanLogin === '1')>Can Login</option>
            <option value="0" @selected($clientCanLogin === '0')>No Login Access</option>
        </select>
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
                <option value="{{ $value }}" @selected($clientCountry === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="city" class="form-label">City</label>
        <input id="city" name="city" type="text" class="form-control"
            value="{{ old('city', $isEdit ? $client->city : '') }}">
    </div>

    <div class="col-span-12 md:col-span-8">
        <label for="address" class="form-label">Address</label>
        <textarea id="address" name="address" rows="3" class="form-control">{{ old('address', $isEdit ? $client->address : '') }}</textarea>
    </div>

    <div class="col-span-12 md:col-span-4">
        <label for="zip_code" class="form-label">Zip Code</label>
        <input id="zip_code" name="zip_code" type="text" class="form-control"
            value="{{ old('zip_code', $isEdit ? $client->zip_code : '') }}">
    </div>

    <div class="col-span-12 mt-6">
        <h4 class="text-lg font-semibold mb-4 flex items-center">
            <i class="ti ti-shield-lock mr-2"></i>{{ $isEdit ? 'Change Password (Optional)' : 'Security Information' }}
        </h4>
        @if ($isEdit)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                <p class="text-sm text-yellow-800 mb-0">Leave password fields empty to keep the current password unchanged.</p>
            </div>
        @endif
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="password" class="form-label">{{ $isEdit ? 'New Password' : 'Password' }}</label>
        <input id="password" name="password" type="password" class="form-control" {{ $isEdit ? '' : 'required' }}>
        <small class="text-muted">Minimum 8 characters, including uppercase, lowercase, number, and special character.</small>
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control"
            {{ $isEdit ? '' : 'required' }}>
    </div>

    <div class="col-span-12 mt-6">
        <h4 class="text-lg font-semibold mb-4 flex items-center">
            <i class="ti ti-photo mr-2"></i>Profile Picture
        </h4>
    </div>

    <div class="col-span-12 md:col-span-6">
        <label for="avatar" class="form-label">Avatar</label>
        <input id="avatar" name="avatar" type="file" accept="image/*" class="form-control">
        <small class="text-muted">Supported formats: JPG, PNG, GIF. Max size: 2MB.</small>
    </div>

    <div class="col-span-12 md:col-span-6">
        @if ($isEdit && $client->avatar)
            <div class="text-center md:text-start">
                <img src="{{ asset('storage/' . $client->avatar) }}" alt="Client avatar"
                    class="w-20 h-20 rounded-full object-cover border-4 border-gray-200 shadow-sm">
                <div class="text-sm text-gray-500 mt-2">Current Avatar</div>
            </div>
        @endif
    </div>

    <div class="col-span-12 flex justify-end gap-3 mt-8 pt-6 border-t">
        <a href="{{ $cancelUrl }}" class="btn btn-secondary">
            <i class="ti ti-x mr-2"></i>Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-check mr-2"></i>{{ $submitLabel }}
        </button>
    </div>
</form>
