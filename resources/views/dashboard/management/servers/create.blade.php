<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.servers.index') }}">{{ t('dashboard.servers', 'Servers') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_Server', 'Add server') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_Server', 'Add server') }}</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 xl:col-span-8">

            {{-- Validation errors --}}
            @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <div class="flex items-start gap-3">
                        <i class="ti ti-alert-circle text-xl mt-0.5 shrink-0"></i>
                        <ul class="mb-0 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('dashboard.servers.store') }}" method="POST">
                @csrf

                {{-- ── Section 1: Basic info ─────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary/10 text-primary text-sm font-bold">١</span>
                            <h5 class="mb-0">{{ t('dashboard.Basic_Info', 'Basic information') }}</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            {{-- Name --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">
                                    {{ t('dashboard.Server_Name', 'Server name') }}
                                    <span class="text-red-500 mr-0.5">*</span>
                                </label>
                                <input
                                    type="text"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    required
                                    value="{{ old('name') }}"
                                    placeholder="{{ t('dashboard.Server_Name_Placeholder', 'e.g. Main Production Server') }}"
                                />
                                @error('name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Type --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">
                                    {{ t('dashboard.Panel_Type', 'Control panel type') }}
                                    <span class="text-red-500 mr-0.5">*</span>
                                </label>
                                <select name="type" class="form-control @error('type') is-invalid @enderror" required>
                                    <option value="cpanel"      {{ old('type', 'cpanel') === 'cpanel'      ? 'selected' : '' }}>cPanel / WHM</option>
                                    <option value="directadmin" {{ old('type') === 'directadmin' ? 'selected' : '' }}>DirectAdmin</option>
                                </select>
                                @error('type')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div class="md:col-span-2">
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Status', 'Status') }}</label>
                                <div class="flex items-center gap-6 mt-2">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="is_active" value="1"
                                               {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                                               class="accent-primary w-4 h-4" />
                                        <span class="text-sm text-emerald-600 font-medium">{{ t('dashboard.Active', 'Active') }}</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" name="is_active" value="0"
                                               {{ old('is_active') === '0' ? 'checked' : '' }}
                                               class="accent-primary w-4 h-4" />
                                        <span class="text-sm text-gray-500 font-medium">{{ t('dashboard.Inactive', 'Inactive') }}</span>
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ── Section 2: Connection ─────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary/10 text-primary text-sm font-bold">٢</span>
                            <h5 class="mb-0">{{ t('dashboard.Connection_Info', 'Connection details') }}</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="text-sm text-gray-500 mb-4">{{ t('dashboard.Connection_Hint', 'Enter the IP address or hostname — at least one is required for the connection.') }}</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            {{-- IP --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Server_IP', 'IP address') }}</label>
                                <input
                                    type="text"
                                    name="ip"
                                    class="form-control font-mono @error('ip') is-invalid @enderror"
                                    value="{{ old('ip') }}"
                                    placeholder="192.168.1.1"
                                    dir="ltr"
                                />
                                @error('ip')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Hostname --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Hostname', 'Hostname') }}</label>
                                <input
                                    type="text"
                                    name="hostname"
                                    class="form-control font-mono @error('hostname') is-invalid @enderror"
                                    value="{{ old('hostname') }}"
                                    placeholder="server.example.com"
                                    dir="ltr"
                                />
                                @error('hostname')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- ── Section 3: Authentication ─────────────────── --}}
                <div class="card mb-6">
                    <div class="card-header">
                        <div class="flex items-center gap-2">
                            <span class="flex items-center justify-center w-7 h-7 rounded-lg bg-primary/10 text-primary text-sm font-bold">٣</span>
                            <h5 class="mb-0">{{ t('dashboard.Auth_Info', 'Authentication') }}</h5>
                        </div>
                    </div>
                    <div class="card-body">

                        {{-- Username --}}
                        <div class="mb-5">
                            <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Username', 'Username') }}</label>
                            <input
                                type="text"
                                name="username"
                                class="form-control @error('username') is-invalid @enderror"
                                value="{{ old('username') }}"
                                placeholder="root"
                                dir="ltr"
                                autocomplete="off"
                            />
                            @error('username')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            {{-- Password --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">{{ t('dashboard.Password', 'Password') }}</label>
                                <input
                                    type="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    value="{{ old('password') }}"
                                    autocomplete="new-password"
                                />
                                <p class="text-xs text-gray-400 mt-1">{{ t('dashboard.Password_Or_Token_Hint', 'Use either password or API token') }}</p>
                                @error('password')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- API Token --}}
                            <div>
                                <label class="block mb-1 font-medium text-sm">
                                    {{ t('dashboard.Api_Token', 'API Token') }}
                                    <span class="text-xs font-normal text-primary ms-1">({{ t('dashboard.Recommended', 'Recommended') }})</span>
                                </label>
                                <input
                                    type="password"
                                    name="api_token"
                                    class="form-control @error('api_token') is-invalid @enderror"
                                    value="{{ old('api_token') }}"
                                    dir="ltr"
                                    autocomplete="new-password"
                                />
                                <p class="text-xs text-gray-400 mt-1">{{ t('dashboard.Api_Token_Hint', 'Generate from WHM → API Tokens') }}</p>
                                @error('api_token')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Form actions --}}
                <div class="flex items-center gap-3">
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i class="ti ti-device-floppy text-base"></i>
                        {{ t('dashboard.Save', 'Save') }}
                    </button>
                    <a href="{{ route('dashboard.servers.index') }}" class="btn btn-light">
                        {{ t('dashboard.Cancel', 'Cancel') }}
                    </a>
                </div>

            </form>
        </div>

        {{-- ── Sidebar: Help card ───────────────────────── --}}
        <div class="col-span-12 xl:col-span-4">
            <div class="card sticky top-6">
                <div class="card-header">
                    <div class="flex items-center gap-2">
                        <i class="ti ti-help-circle text-primary text-lg"></i>
                        <h5 class="mb-0">{{ t('dashboard.Help', 'Help') }}</h5>
                    </div>
                </div>
                <div class="card-body space-y-4 text-sm text-gray-600">
                    <div>
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_IP_Hostname', 'IP vs Hostname') }}</p>
                        <p>{{ t('dashboard.Help_IP_Hostname_Desc', 'You can enter an IP address, a hostname, or both. The hostname takes priority when both are provided.') }}</p>
                    </div>
                    <hr class="border-slate-100">
                    <div>
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Api_Token', 'API Token') }}</p>
                        <p>{{ t('dashboard.Help_Api_Token_Desc', 'The API Token is more secure than a password. Generate it from WHM → Development → API Tokens.') }}</p>
                    </div>
                    <hr class="border-slate-100">
                    <div>
                        <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Panel_Type', 'Control panel type') }}</p>
                        <p>{{ t('dashboard.Help_Panel_Type_Desc', 'Choose cPanel/WHM for cPanel servers. DirectAdmin support is available for other panel types.') }}</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-dashboard-layout>
