<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.subscriptions.index') }}">{{ t('dashboard.subscriptions', 'Subscriptions') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_Subscription', 'Add subscription') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_New_Subscription', 'Add New Subscription') }}</h2>
            </div>
        </div>
    </div>

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('dashboard.subscriptions.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-12 gap-6">

            {{-- ═══ FORM COLUMN (col-span-8) ═════════════════════════════════ --}}
            <div class="col-span-12 xl:col-span-8">

                {{-- ── Section ١: Client & Plan ──────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary text-white text-sm font-bold shrink-0">١</span>
                        <h5 class="mb-0">{{ t('dashboard.Subscription_Info', 'Subscription Details') }}</h5>
                    </div>
                    <div class="card-body grid grid-cols-12 gap-x-6">

                        {{-- Client --}}
                        <div class="col-span-12 md:col-span-6 mb-3">
                            <label class="form-label">
                                {{ t('dashboard.Client', 'Client') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select name="client_id" class="form-select" id="clientSelect" required>
                                <option value="">{{ t('dashboard.Choose_Client', '-- Select a client --') }}</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}"
                                        data-email="{{ $client->email ?? '' }}"
                                        data-first="{{ $client->first_name ?? '' }}"
                                        data-last="{{ $client->last_name ?? '' }}"
                                        {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->first_name }} {{ $client->last_name }}
                                        @if($client->email) ({{ $client->email }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Plan --}}
                        <div class="col-span-12 md:col-span-6 mb-3">
                            <label class="form-label">
                                {{ t('dashboard.plans', 'Plan') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select name="plan_id" class="form-select" id="planSelect" required>
                                <option value="">{{ t('dashboard.Choose_Plan', '-- Select a plan --') }}</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}"
                                        data-price="{{ $plan->monthly_price }}"
                                        {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }}
                                        @if($plan->monthly_price)
                                            — ${{ number_format($plan->monthly_price, 2) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Price --}}
                        <div class="col-span-12 md:col-span-6 mb-3">
                            <label class="form-label">
                                {{ t('dashboard.Price_USD', 'Price (USD)') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text font-mono">$</span>
                                <input type="number" name="price" id="priceInput"
                                    class="form-control font-mono" dir="ltr"
                                    min="0" step="0.01" required
                                    value="{{ old('price') }}"
                                    placeholder="0.00" />
                            </div>
                            <small class="text-muted">{{ t('dashboard.Price_Hint', 'Auto-filled when you select a plan. You can override it.') }}</small>
                            @error('price')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Status --}}
                        <div class="col-span-12 md:col-span-6 mb-3">
                            <label class="form-label">
                                {{ t('dashboard.Status', 'Status') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select name="status" class="form-select" required>
                                <option value="pending"   {{ old('status', 'pending') === 'pending'   ? 'selected' : '' }}>{{ t('dashboard.Status_Pending',   'Pending') }}</option>
                                <option value="active"    {{ old('status')            === 'active'    ? 'selected' : '' }}>{{ t('dashboard.Status_Active',    'Active') }}</option>
                                <option value="suspended" {{ old('status')            === 'suspended' ? 'selected' : '' }}>{{ t('dashboard.Status_Suspended', 'Suspended') }}</option>
                                <option value="cancelled" {{ old('status')            === 'cancelled' ? 'selected' : '' }}>{{ t('dashboard.Status_Cancelled', 'Cancelled') }}</option>
                            </select>
                            @error('status')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ── Section ٢: Domain & Server ───────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary text-white text-sm font-bold shrink-0">٢</span>
                        <h5 class="mb-0">{{ t('dashboard.Domain_And_Server', 'Domain & Server') }}</h5>
                    </div>
                    <div class="card-body grid grid-cols-12 gap-x-6">

                        {{-- Domain type --}}
                        <div class="col-span-12 md:col-span-6 mb-3">
                            <label class="form-label">
                                {{ t('dashboard.Domain_Type', 'Domain Type') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <select name="domain_option" class="form-select" id="domainOption" required>
                                <option value="subdomain" {{ old('domain_option', 'subdomain') === 'subdomain' ? 'selected' : '' }}>
                                    {{ t('dashboard.Domain_Subdomain', 'Use a subdomain (platform)') }}
                                </option>
                                <option value="existing" {{ old('domain_option') === 'existing' ? 'selected' : '' }}>
                                    {{ t('dashboard.Domain_Existing', "Client's own domain") }}
                                </option>
                                <option value="new" {{ old('domain_option') === 'new' ? 'selected' : '' }}>
                                    {{ t('dashboard.Domain_New', 'Register new domain') }}
                                </option>
                            </select>
                            @error('domain_option')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Domain name --}}
                        <div class="col-span-12 md:col-span-6 mb-3">
                            <label class="form-label">{{ t('dashboard.Domain_Name_Label', 'Domain Name') }}</label>
                            <input type="text" name="domain_name" id="domainNameInput"
                                class="form-control font-mono" dir="ltr"
                                value="{{ old('domain_name') }}"
                                placeholder="{{ t('dashboard.Domain_Name_Placeholder', 'e.g. example.com') }}" />
                            <small class="text-muted" id="domainHint"></small>
                            @error('domain_name')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Server --}}
                        <div class="col-span-12 md:col-span-6 mb-3">
                            <label class="form-label">{{ t('dashboard.servers', 'Server') }}</label>
                            <select name="server_id" class="form-select">
                                <option value="">{{ t('dashboard.None', 'None') }}</option>
                                @foreach ($servers as $server)
                                    <option value="{{ $server->id }}"
                                        {{ old('server_id') == $server->id ? 'selected' : '' }}>
                                        {{ $server->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('server_id')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Username --}}
                        <div class="col-span-12 md:col-span-6 mb-3">
                            <label class="form-label">{{ t('dashboard.Username_Label', 'Username (cPanel)') }}</label>
                            <div class="flex items-center gap-2">
                                <input type="text" name="username" id="usernameInput"
                                    class="form-control font-mono" dir="ltr"
                                    value="{{ old('username') }}"
                                    placeholder="{{ t('dashboard.Username_Placeholder', 'e.g. john123') }}" />
                                <button type="button" id="suggestUsernameBtn"
                                    class="shrink-0 btn btn-light whitespace-nowrap">
                                    {{ t('dashboard.Suggest', 'Suggest') }}
                                </button>
                            </div>
                            <small class="text-muted">{{ t('dashboard.Username_Hint', 'Auto-generated from client name / domain. Max 8 chars for cPanel.') }}</small>
                            @error('username')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- ── Section ٣: Schedule ──────────────────────────── --}}
                <div class="card mb-4">
                    <div class="card-header flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-primary text-white text-sm font-bold shrink-0">٣</span>
                        <h5 class="mb-0">{{ t('dashboard.Schedule', 'Schedule') }}</h5>
                    </div>
                    <div class="card-body grid grid-cols-12 gap-x-6">

                        {{-- Starts at --}}
                        <div class="col-span-12 md:col-span-4 mb-3">
                            <label class="form-label">
                                {{ t('dashboard.Start_Date', 'Start Date') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="starts_at" class="form-control" required
                                value="{{ old('starts_at', \Carbon\Carbon::today()->toDateString()) }}" />
                            @error('starts_at')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Next due date --}}
                        <div class="col-span-12 md:col-span-4 mb-3">
                            <label class="form-label">{{ t('dashboard.Next_Due_Date', 'Next Due Date') }}</label>
                            <input type="date" name="next_due_date" class="form-control"
                                value="{{ old('next_due_date') }}" />
                            @error('next_due_date')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Ends at --}}
                        <div class="col-span-12 md:col-span-4 mb-3">
                            <label class="form-label">{{ t('dashboard.End_Date', 'End Date') }}</label>
                            <input type="date" name="ends_at" class="form-control"
                                value="{{ old('ends_at') }}" />
                            <small class="text-muted">{{ t('dashboard.End_Date_Hint', 'Optional — leave blank for open-ended subscriptions') }}</small>
                            @error('ends_at')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Submit buttons --}}
                <div class="flex items-center gap-3 mt-2">
                    <button type="submit" class="btn btn-primary flex items-center gap-2">
                        <i class="ti ti-circle-check text-base"></i>
                        {{ t('dashboard.Create_Subscription', 'Create Subscription') }}
                    </button>
                    <a href="{{ route('dashboard.subscriptions.index') }}" class="btn btn-light">
                        {{ t('dashboard.Cancel', 'Cancel') }}
                    </a>
                </div>

            </div>

            {{-- ═══ HELP SIDEBAR (col-span-4) ═══════════════════════════════ --}}
            <div class="col-span-12 xl:col-span-4">
                <div class="card sticky top-6">
                    <div class="card-header">
                        <h5 class="mb-0 flex items-center gap-2">
                            <i class="ti ti-info-circle text-primary"></i>
                            {{ t('dashboard.Help', 'Help') }}
                        </h5>
                    </div>
                    <div class="card-body space-y-5 text-sm text-gray-600">

                        <div>
                            <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Price', 'Plan Price') }}</p>
                            <p class="text-muted">{{ t('dashboard.Help_Price_Desc', 'The price is auto-filled from the plan\'s monthly price when you select a plan. You can override it for custom pricing.') }}</p>
                        </div>

                        <div class="border-t pt-4">
                            <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Domain_Type', 'Domain Type') }}</p>
                            <p class="text-muted">{{ t('dashboard.Help_Domain_Type_Desc', 'Subdomain: uses a platform subdomain (e.g. client.palgoals.com). Own domain: client brings their own domain. New domain: register a new domain (manual process).') }}</p>
                        </div>

                        <div class="border-t pt-4">
                            <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Server', 'Server & Username') }}</p>
                            <p class="text-muted">{{ t('dashboard.Help_Server_Desc', 'For hosting plans, select the cPanel server and set a username (max 8 alphanumeric characters). Click "Suggest" to auto-generate based on client name.') }}</p>
                        </div>

                        <div class="border-t pt-4">
                            <p class="font-semibold text-gray-800 mb-1">{{ t('dashboard.Help_Schedule', 'Dates') }}</p>
                            <p class="text-muted">{{ t('dashboard.Help_Schedule_Desc', 'Start date is required. Next due date is used for billing reminders. End date is optional — leave blank for subscriptions without an expiry.') }}</p>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>

    <script>
        // ── Plan price auto-fill ──────────────────────────────────────────────
        (function () {
            var planSelect = document.getElementById('planSelect');
            var priceInput = document.getElementById('priceInput');
            if (!planSelect || !priceInput) return;

            planSelect.addEventListener('change', function () {
                var opt = this.options[this.selectedIndex];
                var price = opt ? opt.getAttribute('data-price') : null;
                if (price && parseFloat(price) > 0) {
                    priceInput.value = parseFloat(price).toFixed(2);
                }
            });
        })();

        // ── Domain hint text based on domain_option ───────────────────────────
        (function () {
            var domainOption = document.getElementById('domainOption');
            var domainHint   = document.getElementById('domainHint');
            var domainInput  = document.getElementById('domainNameInput');
            if (!domainOption || !domainHint) return;

            var hints = {
                subdomain: '{{ t('dashboard.Domain_Subdomain_Hint', 'e.g. myclient.palgoals.com') }}',
                existing:  '{{ t('dashboard.Domain_Existing_Hint',  'Enter the client\'s domain without http(s)://') }}',
                new:       '{{ t('dashboard.Domain_New_Hint',        'Enter the new domain to register, e.g. newsite.com') }}',
            };

            function update() {
                var v = domainOption.value;
                domainHint.textContent = hints[v] || '';
                if (domainInput) {
                    domainInput.required = (v === 'new' || v === 'existing');
                }
            }

            domainOption.addEventListener('change', update);
            update();
        })();

        // ── Suggest username ──────────────────────────────────────────────────
        (function () {
            var btn           = document.getElementById('suggestUsernameBtn');
            var usernameInput = document.getElementById('usernameInput');
            var domainInput   = document.getElementById('domainNameInput');
            var clientSelect  = document.getElementById('clientSelect');
            if (!btn) return;

            function sanitize(s) {
                return String(s || '').toLowerCase().replace(/[^a-z0-9]/g, '').slice(0, 8);
            }

            // Auto-suggest on client change (if username is blank)
            clientSelect?.addEventListener('change', function () {
                if (usernameInput && usernameInput.value.trim() !== '') return;
                var opt = clientSelect.options[clientSelect.selectedIndex];
                if (!opt || !opt.value) return;
                var firstName = opt.getAttribute('data-first') || '';
                var lastName  = opt.getAttribute('data-last')  || '';
                var email     = opt.getAttribute('data-email') || '';
                var base = '';
                if (email.indexOf('@') !== -1) {
                    base = email.split('@')[0];
                } else {
                    base = firstName + lastName;
                }
                if (usernameInput) usernameInput.value = sanitize(base) || ('user' + opt.value);
            });

            // Suggest button — calls API
            btn.addEventListener('click', function () {
                btn.disabled = true;
                fetch('{{ route('dashboard.subscriptions.username-suggest') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        domain_name:         domainInput?.value    || null,
                        client_id:           clientSelect?.value   || null,
                        preferred_username:  usernameInput?.value  || null,
                    }),
                    credentials: 'same-origin'
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.username && usernameInput) {
                        usernameInput.value = data.username;
                    }
                })
                .catch(function (err) { console.error(err); })
                .finally(function () { btn.disabled = false; });
            });
        })();
    </script>
</x-dashboard-layout>
