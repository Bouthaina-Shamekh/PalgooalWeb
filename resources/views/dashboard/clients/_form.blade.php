@php
    $isEdit      = isset($client) && $client;
    $clientStatus   = old('status',    $isEdit ? $client->status            : 'active');
    $clientCanLogin = old('can_login', $isEdit ? (string)(int)$client->can_login : '1');
    $clientCountry  = old('country',   $isEdit ? ($client->country ?? '')   : '');
@endphp

<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    {{-- ── Section ١: المعلومات الأساسية ────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary text-white text-sm font-bold shrink-0">١</span>
            <h5 class="mb-0">{{ t('dashboard.Basic_Info', 'Basic Information') }}</h5>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-12 gap-4">

                {{-- First Name --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="first_name" class="form-label">
                        {{ t('dashboard.First_Name', 'First Name') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input id="first_name" name="first_name" type="text" class="form-control"
                           value="{{ old('first_name', $isEdit ? $client->first_name : '') }}" required />
                    @error('first_name')
                        <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Last Name --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="last_name" class="form-label">
                        {{ t('dashboard.Last_Name', 'Last Name') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input id="last_name" name="last_name" type="text" class="form-control"
                           value="{{ old('last_name', $isEdit ? $client->last_name : '') }}" required />
                    @error('last_name')
                        <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Company --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="company_name" class="form-label">
                        {{ t('dashboard.Company_Name', 'Company Name') }}
                        <span class="text-xs text-gray-400 font-normal">— {{ t('dashboard.Optional', 'optional') }}</span>
                    </label>
                    <input id="company_name" name="company_name" type="text" class="form-control"
                           value="{{ old('company_name', $isEdit ? $client->company_name : '') }}" />
                    @error('company_name')
                        <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="email" class="form-label">
                        {{ t('dashboard.Email_Address', 'Email Address') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input id="email" name="email" type="email" class="form-control font-mono" dir="ltr"
                           value="{{ old('email', $isEdit ? $client->email : '') }}" required
                           autocomplete="off" />
                    @error('email')
                        <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Phone --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="phone" class="form-label">
                        {{ t('dashboard.Mobile_Number', 'Mobile Number') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <input id="phone" name="phone" type="text" class="form-control font-mono" dir="ltr"
                           value="{{ old('phone', $isEdit ? $client->phone : '') }}" required />
                    @error('phone')
                        <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Status — radio buttons (PHP loose comparison fix) --}}
                <div class="col-span-12 md:col-span-3">
                    <label class="form-label d-block mb-2">{{ t('dashboard.Client_Status', 'Account Status') }}</label>
                    <div class="flex items-center gap-5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="status" value="active"
                                   {{ $clientStatus === 'active' ? 'checked' : '' }}
                                   class="accent-primary w-4 h-4" />
                            <span class="text-sm text-gray-700">{{ t('dashboard.Status_Active', 'Active') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="status" value="inactive"
                                   {{ $clientStatus === 'inactive' ? 'checked' : '' }}
                                   class="accent-primary w-4 h-4" />
                            <span class="text-sm text-gray-700">{{ t('dashboard.Client_Inactive', 'Inactive') }}</span>
                        </label>
                    </div>
                </div>

                {{-- Can Login — radio buttons --}}
                <div class="col-span-12 md:col-span-3">
                    <label class="form-label d-block mb-2">{{ t('dashboard.Login_Access', 'Login Access') }}</label>
                    <div class="flex items-center gap-5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="can_login" value="1"
                                   {{ $clientCanLogin === '1' ? 'checked' : '' }}
                                   class="accent-primary w-4 h-4" />
                            <span class="text-sm text-gray-700">{{ t('dashboard.Can_Login', 'Can Login') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="can_login" value="0"
                                   {{ $clientCanLogin === '0' ? 'checked' : '' }}
                                   class="accent-primary w-4 h-4" />
                            <span class="text-sm text-gray-700">{{ t('dashboard.No_Login_Access', 'No Access') }}</span>
                        </label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Section ٢: العنوان ─────────────────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary text-white text-sm font-bold shrink-0">٢</span>
            <h5 class="mb-0">{{ t('dashboard.Address_Info', 'Address Information') }}</h5>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-12 gap-4">

                {{-- Country --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="country" class="form-label">{{ t('dashboard.Location', 'Country') }}</label>
                    <select id="country" name="country" class="form-select">
                        <option value="">{{ t('dashboard.Select_Country', 'Select Country') }}</option>
                        @foreach ($countryOptions as $value => $label)
                            @if ($value !== '')
                                <option value="{{ $value }}" {{ $clientCountry === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    @error('country')
                        <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- City --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="city" class="form-label">{{ t('dashboard.City', 'City') }}</label>
                    <input id="city" name="city" type="text" class="form-control"
                           value="{{ old('city', $isEdit ? $client->city : '') }}" />
                    @error('city')
                        <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Address --}}
                <div class="col-span-12 md:col-span-8">
                    <label for="address" class="form-label">{{ t('dashboard.Address', 'Address') }}</label>
                    <textarea id="address" name="address" rows="2" class="form-control">{{ old('address', $isEdit ? $client->address : '') }}</textarea>
                    @error('address')
                        <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Zip Code --}}
                <div class="col-span-12 md:col-span-4">
                    <label for="zip_code" class="form-label">{{ t('dashboard.Zip_Code', 'Zip Code') }}</label>
                    <input id="zip_code" name="zip_code" type="text" class="form-control font-mono" dir="ltr"
                           value="{{ old('zip_code', $isEdit ? $client->zip_code : '') }}" />
                    @error('zip_code')
                        <span class="text-danger text-sm">{{ $message }}</span>
                    @enderror
                </div>

            </div>
        </div>
    </div>

    {{-- ── Section ٣: كلمة المرور ─────────────────────────────────── --}}
    <div class="card mb-4">
        <div class="card-header flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary text-white text-sm font-bold shrink-0">٣</span>
            <h5 class="mb-0">
                {{ $isEdit
                    ? t('dashboard.Change_Password_Optional', 'Change Password (Optional)')
                    : t('dashboard.Security_Info', 'Security Information') }}
            </h5>
        </div>
        <div class="card-body">
            @if ($isEdit)
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl px-4 py-3 mb-4">
                    <p class="text-sm text-yellow-800 mb-0">
                        {{ t('dashboard.Password_Keep_Hint', 'Leave password fields empty to keep the current password unchanged.') }}
                    </p>
                </div>
            @endif

            <div class="grid grid-cols-12 gap-4">

                {{-- Password --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="password" class="form-label">
                        {{ $isEdit
                            ? t('dashboard.New_Password', 'New Password')
                            : t('dashboard.Password', 'Password') }}
                        @if (!$isEdit)<span class="text-red-500">*</span>@endif
                    </label>
                    <input id="password" name="password" type="password" class="form-control" dir="ltr"
                           {{ $isEdit ? '' : 'required' }} autocomplete="new-password" />
                    <small class="text-muted">
                        {{ t('dashboard.Password_Hint', 'Min 8 chars: uppercase, lowercase, number, special character.') }}
                    </small>
                    @error('password')
                        <span class="text-danger text-sm d-block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Confirm Password --}}
                <div class="col-span-12 md:col-span-6">
                    <label for="password_confirmation" class="form-label">
                        {{ t('dashboard.Confirm_Password', 'Confirm Password') }}
                        @if (!$isEdit)<span class="text-red-500">*</span>@endif
                    </label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                           class="form-control" dir="ltr"
                           {{ $isEdit ? '' : 'required' }} autocomplete="new-password" />
                </div>

            </div>
        </div>
    </div>

    {{-- ── Section ٤: الصورة الشخصية ──────────────────────────────── --}}
    @php
        $avatarPreviewUrl = ($isEdit && $client->avatar)
            ? asset('storage/' . $client->avatar)
            : null;
    @endphp
    <div class="card mb-4">
        <div class="card-header flex items-center gap-3">
            <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary text-white text-sm font-bold shrink-0">٤</span>
            <h5 class="mb-0">{{ t('dashboard.Profile_Picture', 'Profile Picture') }}</h5>
        </div>
        <div class="card-body">

            <label class="form-label">{{ t('dashboard.Avatar_Label', 'Avatar') }}</label>

            {{-- Hidden input — stores file_path from media library --}}
            <input type="hidden" name="avatar" id="avatar_media_input"
                   value="{{ old('avatar', $isEdit ? ($client->avatar ?? '') : '') }}" />

            {{-- Buttons row --}}
            <div class="flex items-center flex-wrap gap-2 mt-1">
                <button type="button"
                        class="btn btn-light btn-sm btn-open-media-picker flex items-center gap-1"
                        data-target-input="avatar_media_input"
                        data-target-preview="avatar_media_preview"
                        data-multiple="false"
                        data-store-value="path">
                    <i class="ti ti-photo text-base"></i>
                    {{ t('dashboard.Choose_From_Media', 'Choose From Media Library') }}
                </button>

                <button type="button"
                        class="btn btn-light btn-sm flex items-center gap-1 text-red-500 hover:bg-red-50"
                        onclick="
                            document.getElementById('avatar_media_input').value = '';
                            document.getElementById('avatar_media_preview').innerHTML =
                                '<span class=\'text-xs text-muted\'>' +
                                '{{ addslashes(t('dashboard.No_Image_Selected', 'No image selected')) }}' +
                                '</span>';
                        ">
                    <i class="ti ti-x text-base"></i>
                    {{ t('dashboard.Clear', 'Clear') }}
                </button>
            </div>

            {{-- Preview --}}
            <div id="avatar_media_preview" class="mt-3 flex flex-wrap gap-2 items-center min-h-[84px]">
                @if ($avatarPreviewUrl)
                    <div class="relative w-20 h-20 overflow-hidden rounded-full border-4 border-gray-200 shadow-sm bg-gray-50">
                        <img src="{{ $avatarPreviewUrl }}"
                             alt="{{ t('dashboard.Current_Avatar', 'Current Avatar') }}"
                             class="w-full h-full object-cover" />
                    </div>
                @else
                    <span class="text-xs text-muted">{{ t('dashboard.No_Image_Selected', 'No image selected') }}</span>
                @endif
            </div>

            @error('avatar')
                <span class="text-danger text-sm d-block mt-1">{{ $message }}</span>
            @enderror

        </div>
    </div>

    {{-- ── Buttons ─────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3 mt-2">
        <button type="submit" class="btn btn-primary flex items-center gap-2">
            <i class="ti ti-circle-check text-base"></i>
            {{ $isEdit
                ? t('dashboard.Update_Client', 'Update Client')
                : t('dashboard.Create_Client', 'Create Client') }}
        </button>
        <a href="{{ $cancelUrl }}" class="btn btn-light">
            {{ t('dashboard.Cancel', 'Cancel') }}
        </a>
    </div>

</form>
