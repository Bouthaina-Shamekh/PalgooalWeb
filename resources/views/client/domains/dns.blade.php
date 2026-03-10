<x-client-layout>
    @php
        $requiredCount = $minNameservers ?? 2;
        $maxSlots = $maxNameservers ?? 12;
        $oldNs = array_values(old('nameservers', $nameservers ?? []));
        $displaySlots = min($maxSlots, max($requiredCount, count($oldNs)));
        $oldNs = array_pad($oldNs, $displaySlots, '');
        $oldIps = array_values(old('nameserver_ips', []));
        $oldIps = array_pad($oldIps, $displaySlots, '');
        $dnsModeMap = [
            'default' => t('frontend.client_domains.dns.snapshot_default', 'Provider default nameservers'),
            'custom' => t('frontend.client_domains.dns.snapshot_custom', 'Custom nameservers enabled'),
            'park' => t('frontend.client_domains.dns.snapshot_park', 'Parked at provider'),
        ];
        $dnsMode = $remoteDns['status'] ?? null;
        $dnsModeText = $dnsMode ? ($dnsModeMap[$dnsMode] ?? \Illuminate\Support\Str::title($dnsMode)) : t('frontend.client_domains.dns.snapshot_unknown', 'Unknown');
        $headingText = str_replace(':domain', $domain->domain_name, t('frontend.client_domains.dns.heading', 'Change DNS for :domain'));
        $fetchedAtText = !empty($remoteDns['fetched_at'])
            ? str_replace(':date', $remoteDns['fetched_at']->timezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i'), t('frontend.client_domains.dns.snapshot_fetched', 'Fetched at :date'))
            : null;
        $syncDateText = $domain->dns_last_synced_at
            ? str_replace(':date', $domain->dns_last_synced_at->timezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i'), t('frontend.client_domains.dns.sync_date', 'Last sync with registrar on :date'))
            : null;
        $syncNoteText = $domain->dns_last_note
            ? str_replace(':note', $domain->dns_last_note, t('frontend.client_domains.dns.sync_note', 'Last note: :note'))
            : null;
        $formSubtitleText = str_replace(':max', (string) $maxSlots, t('frontend.client_domains.dns.form_subtitle', 'Provide at least two nameservers. You can add up to :max nameservers.'));
        $validationMinText = str_replace(':n', (string) $requiredCount, t('frontend.client_domains.dns.validation_min', 'Please provide at least :n nameservers.'));
        $validationMaxText = str_replace(':n', (string) $maxSlots, t('frontend.client_domains.dns.validation_max', 'You can add up to :n nameservers.'));
        $domainName = strtolower(trim($domain->domain_name));
    @endphp

    <div class="page-header">
        <div class="page-block">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('client.home') }}">{{ t('frontend.client_nav.home', 'Home') }}</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('client.domains.index') }}">{{ t('frontend.client_domains.index.title', 'My Domains') }}</a>
                        </li>
                        <li class="breadcrumb-item" aria-current="page">
                            {{ t('frontend.client_domains.dns.title', 'Change DNS') }}
                        </li>
                    </ul>
                    <div class="page-header-title">
                        <h2 class="mb-1">{{ $headingText }}</h2>
                        <p class="mb-0 text-sm text-muted">
                            {{ t('frontend.client_domains.dns.subtitle', 'Update your nameservers and sync them directly with the registrar.') }}
                        </p>
                    </div>
                </div>
                <a href="{{ route('client.domains.index') }}" class="btn btn-light-secondary">
                    <i class="ti ti-arrow-left me-1"></i>
                    {{ t('frontend.client_domains.dns.back', 'Back to Domains') }}
                </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 list-disc ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6 gap-y-6">
        <div class="col-span-12 xl:col-span-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ t('frontend.client_domains.dns.snapshot_title', 'Registrar Snapshot') }}</h5>
                </div>
                <div class="card-body space-y-4">
                    <div class="rounded-2xl border border-theme-border dark:border-themedark-border p-4">
                        <div class="text-xs uppercase tracking-wide text-muted mb-2">
                            {{ t('frontend.client_domains.dns.snapshot_provider', 'Provider') }}
                        </div>
                        <div class="font-medium text-body">
                            {{ $remoteDns['provider'] ? \Illuminate\Support\Str::title($remoteDns['provider']) : t('frontend.client_domains.dns.snapshot_unavailable', 'Unavailable') }}
                        </div>
                    </div>

                    <div class="rounded-2xl border border-theme-border dark:border-themedark-border p-4">
                        <div class="text-xs uppercase tracking-wide text-muted mb-2">
                            {{ t('frontend.client_domains.dns.snapshot_mode', 'Current Mode') }}
                        </div>
                        <div class="font-medium text-body">{{ $dnsModeText }}</div>
                    </div>

                    @if (!empty($remoteDns['error']))
                        <div class="rounded-2xl border border-danger-200 bg-danger-50 p-4 text-danger-700 text-sm">
                            {{ $remoteDns['error'] }}
                        </div>
                    @else
                        <div class="rounded-2xl border border-theme-border dark:border-themedark-border p-4">
                            <div class="text-xs uppercase tracking-wide text-muted mb-3">
                                {{ t('frontend.client_domains.dns.snapshot_nameservers', 'Nameservers At Registrar') }}
                            </div>
                            <div class="space-y-2">
                                @forelse (($remoteDns['nameservers'] ?? []) as $nameserver)
                                    <div class="rounded-xl bg-theme-bodybg dark:bg-themedark-bodybg px-3 py-2 text-sm font-medium text-body">
                                        {{ $nameserver }}
                                    </div>
                                @empty
                                    <div class="text-sm text-muted">
                                        {{ t('frontend.client_domains.dns.snapshot_empty', 'No nameservers were returned by the registrar.') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    @if (!empty($remoteDns['fetched_at']))
                        <div class="text-xs text-muted">
                            {{ $fetchedAtText }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ t('frontend.client_domains.dns.sync_title', 'Last Sync') }}</h5>
                </div>
                <div class="card-body">
                    @if ($domain->dns_last_synced_at)
                        <div class="text-sm text-body mb-2">
                            {{ $syncDateText }}
                        </div>
                        @if ($domain->dns_last_note)
                            <div class="text-sm text-muted">
                                {{ $syncNoteText }}
                            </div>
                        @endif
                    @else
                        <div class="text-sm text-muted">
                            {{ t('frontend.client_domains.dns.sync_empty', 'This domain has not been synced yet.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-span-12 xl:col-span-8">
            <form action="{{ route('client.domains.dns.update', $domain->id) }}" method="POST" class="card" novalidate>
                @csrf
                @method('PUT')

                <div class="card-header">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h5 class="mb-1">{{ t('frontend.client_domains.dns.form_title', 'Nameserver Configuration') }}</h5>
                            <p class="mb-0 text-sm text-muted">
                                {{ $formSubtitleText }}
                            </p>
                        </div>
                        <button type="button" id="add-nameserver" class="btn btn-light-primary">
                            <i class="ti ti-plus me-1"></i>
                            {{ t('frontend.client_domains.dns.add_nameserver', 'Add Nameserver') }}
                        </button>
                    </div>
                </div>

                <div class="card-body space-y-6">
                    <div id="nameserver-fields" class="grid grid-cols-1 gap-4 md:grid-cols-2" data-min="{{ $requiredCount }}" data-max="{{ $maxSlots }}" data-fixed-count="{{ $requiredCount }}">
                        @foreach ($oldNs as $index => $nameserver)
                            @php $isFixed = $index < $requiredCount; @endphp
                            <div class="space-y-2 ns-item" data-fixed="{{ $isFixed ? 'true' : 'false' }}">
                                <label for="nameserver_{{ $index }}" class="flex items-center gap-2 text-sm font-medium text-body">
                                    <span class="ns-label-text">{{ t('frontend.client_domains.dns.nameserver_label', 'Nameserver') }} {{ $index + 1 }}</span>
                                    <span class="badge-placeholder badge bg-light-primary text-primary {{ $isFixed ? '' : 'hidden' }}">
                                        {{ t('frontend.client_domains.dns.required', 'Required') }}
                                    </span>
                                </label>
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        name="nameservers[{{ $index }}]"
                                        id="nameserver_{{ $index }}"
                                        value="{{ $nameserver }}"
                                        class="form-control flex-1"
                                        inputmode="url"
                                        dir="ltr"
                                        placeholder="ns{{ $index + 1 }}.example.com"
                                        pattern="^([a-z0-9-]+\.)+[a-z]{2,}$"
                                        aria-invalid="false"
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-light-danger remove-ns {{ $isFixed ? 'opacity-0 pointer-events-none' : '' }}"
                                        data-fixed="{{ $isFixed ? 'true' : 'false' }}"
                                        title="{{ t('frontend.client_domains.dns.remove', 'Remove') }}"
                                    >
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                                <p class="mb-0 text-xs text-muted">{{ t('frontend.client_domains.dns.example', 'Example: ns1.example.com') }}</p>
                                <div class="glue-ip-wrap {{ (filled($nameserver) && strtolower(trim($nameserver)) !== $domainName && str_ends_with(strtolower(trim($nameserver)), '.' . $domainName)) ? '' : 'hidden' }}">
                                    <label for="nameserver_ip_{{ $index }}" class="text-xs font-medium text-body">
                                        {{ t('frontend.client_domains.dns.glue_ip', 'Glue Record IP') }}
                                    </label>
                                    <input
                                        type="text"
                                        name="nameserver_ips[{{ $index }}]"
                                        id="nameserver_ip_{{ $index }}"
                                        value="{{ $oldIps[$index] ?? '' }}"
                                        class="form-control mt-2"
                                        inputmode="decimal"
                                        dir="ltr"
                                        placeholder="192.0.2.10"
                                    />
                                    <p class="mb-0 mt-2 text-xs text-warning-600">
                                        {{ t('frontend.client_domains.dns.glue_help', 'Required when the nameserver belongs to the same domain, such as ns1.yourdomain.com.') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <p class="mb-0 text-xs text-muted">
                            {{ t('frontend.client_domains.dns.form_help', 'DNS updates can take time to propagate after the registrar accepts the change.') }}
                        </p>
                        <p id="ns-counter" class="mb-0 text-xs text-muted"></p>
                    </div>

                    <div id="ns-errors" class="hidden rounded-2xl border border-danger-200 bg-danger-50 p-3 text-sm text-danger-700" role="alert" aria-live="polite"></div>

                    <div>
                        <label for="dns-notes" class="form-label">{{ t('frontend.client_domains.dns.notes', 'Note (optional)') }}</label>
                        <textarea id="dns-notes" name="notes" rows="4" class="form-control"
                            placeholder="{{ t('frontend.client_domains.dns.notes_placeholder', 'Share any context you want to keep with this DNS change.') }}">{{ old('notes', $domain->dns_last_note) }}</textarea>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="text-sm text-muted">
                            {{ t('frontend.client_domains.dns.footer_note', 'Changes are pushed to the registrar immediately when the provider accepts the request.') }}
                        </div>
                        <div class="flex gap-2">
                            <a href="{{ route('client.domains.index') }}" class="btn btn-light-secondary">
                                {{ t('frontend.client_domains.dns.cancel', 'Cancel') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                {{ t('frontend.client_domains.dns.submit', 'Save DNS Settings') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <template id="ns-template">
        <div class="space-y-2 ns-item" data-fixed="false">
            <label class="flex items-center gap-2 text-sm font-medium text-body">
                <span class="ns-label-text"></span>
                <span class="badge-placeholder badge bg-light-primary text-primary hidden"></span>
            </label>
            <div class="flex gap-2">
                <input type="text" class="form-control flex-1" inputmode="url" dir="ltr" placeholder="" pattern="^([a-z0-9-]+\.)+[a-z]{2,}$" aria-invalid="false" />
                <button type="button" class="btn btn-light-danger remove-ns" data-fixed="false" title="{{ t('frontend.client_domains.dns.remove', 'Remove') }}">
                    <i class="ti ti-x"></i>
                </button>
            </div>
            <p class="mb-0 text-xs text-muted">{{ t('frontend.client_domains.dns.example', 'Example: ns1.example.com') }}</p>
            <div class="glue-ip-wrap hidden">
                <label class="text-xs font-medium text-body">{{ t('frontend.client_domains.dns.glue_ip', 'Glue Record IP') }}</label>
                <input type="text" class="form-control mt-2 glue-ip-input" inputmode="decimal" dir="ltr" placeholder="192.0.2.10" />
                <p class="mb-0 mt-2 text-xs text-warning-600">{{ t('frontend.client_domains.dns.glue_help', 'Required when the nameserver belongs to the same domain, such as ns1.yourdomain.com.') }}</p>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fieldsContainer = document.getElementById('nameserver-fields');
            const addButton = document.getElementById('add-nameserver');
            const template = document.getElementById('ns-template');
            const errorsBox = document.getElementById('ns-errors');
            const counterEl = document.getElementById('ns-counter');

            if (!fieldsContainer || !template) {
                return;
            }

            const minCount = parseInt(fieldsContainer.dataset.min || '2', 10);
            const maxCount = parseInt(fieldsContainer.dataset.max || '12', 10);
            const baseCount = parseInt(fieldsContainer.dataset.fixedCount || '0', 10);
            const labelText = @json(t('frontend.client_domains.dns.nameserver_label', 'Nameserver'));
            const requiredBadge = @json(t('frontend.client_domains.dns.required', 'Required'));
            const minMessage = @json($validationMinText);
            const maxMessage = @json($validationMaxText);
            const fixMessage = @json(t('frontend.client_domains.dns.validation_fix', 'Please fix the highlighted fields.'));
            const glueMessage = @json(t('frontend.client_domains.dns.validation_glue_ip', 'Provide a glue record IP for each nameserver that belongs to the same domain.'));
            const hostRegex = /^([a-z0-9-]+\.)+[a-z]{2,}$/i;
            const ipRegex = /^(?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)$/;
            const domainName = @json($domainName);

            const needsGlueIp = (host) => {
                const normalized = String(host || '').trim().toLowerCase();
                return normalized !== '' && normalized !== domainName && normalized.endsWith(`.${domainName}`);
            };

            const showError = (message) => {
                if (!message) {
                    errorsBox.classList.add('hidden');
                    errorsBox.textContent = '';
                    return;
                }

                errorsBox.textContent = message;
                errorsBox.classList.remove('hidden');
            };

            const updateLabels = () => {
                const items = fieldsContainer.querySelectorAll('.ns-item');

                items.forEach((item, index) => {
                    const label = item.querySelector('label');
                    const labelTextEl = item.querySelector('.ns-label-text');
                    const badge = item.querySelector('.badge-placeholder');
                    const input = item.querySelector('input');
                    const removeButton = item.querySelector('.remove-ns');
                    const glueWrap = item.querySelector('.glue-ip-wrap');
                    const glueInput = item.querySelector('.glue-ip-input') || glueWrap?.querySelector('input');
                    const isFixed = index < baseCount;

                    item.dataset.fixed = isFixed ? 'true' : 'false';

                    if (label) {
                        label.setAttribute('for', `nameserver_${index}`);
                    }

                    if (labelTextEl) {
                        labelTextEl.textContent = `${labelText} ${index + 1}`;
                    }

                    if (badge) {
                        badge.textContent = requiredBadge;
                        badge.classList.toggle('hidden', !isFixed);
                    }

                    if (input) {
                        input.id = `nameserver_${index}`;
                        input.name = `nameservers[${index}]`;
                        input.placeholder = `ns${index + 1}.example.com`;
                    }

                    if (glueInput) {
                        glueInput.id = `nameserver_ip_${index}`;
                        glueInput.name = `nameserver_ips[${index}]`;
                    }

                    if (removeButton) {
                        removeButton.dataset.fixed = isFixed ? 'true' : 'false';
                        removeButton.classList.toggle('opacity-0', isFixed);
                        removeButton.classList.toggle('pointer-events-none', isFixed);
                    }

                    if (glueWrap && glueInput && input) {
                        const required = needsGlueIp(input.value);
                        glueWrap.classList.toggle('hidden', !required);
                        if (!required) {
                            glueInput.value = '';
                            glueInput.setAttribute('aria-invalid', 'false');
                            glueInput.classList.remove('border-danger-500');
                        }
                    }
                });

                counterEl.textContent = `${items.length}/${maxCount}`;
            };

            const validateAll = () => {
                let valid = true;
                const items = fieldsContainer.querySelectorAll('.ns-item');

                if (items.length < minCount) {
                    showError(minMessage);
                    valid = false;
                } else if (items.length > maxCount) {
                    showError(maxMessage);
                    valid = false;
                } else {
                    showError('');
                }

                items.forEach((item) => {
                    const input = item.querySelector('input');
                    const glueInput = item.querySelector('.glue-ip-input') || item.querySelector('.glue-ip-wrap input');
                    const inputValid = input.value.trim() === '' ? true : hostRegex.test(input.value.trim());

                    input.setAttribute('aria-invalid', inputValid ? 'false' : 'true');
                    input.classList.toggle('border-danger-500', !inputValid);

                    if (!inputValid) {
                        valid = false;
                    }

                    if (glueInput && needsGlueIp(input.value)) {
                        const glueValid = ipRegex.test(glueInput.value.trim());
                        glueInput.setAttribute('aria-invalid', glueValid ? 'false' : 'true');
                        glueInput.classList.toggle('border-danger-500', !glueValid);

                        if (!glueValid) {
                            valid = false;
                            if (!errorsBox.textContent) {
                                showError(glueMessage);
                            }
                        }
                    } else if (glueInput) {
                        glueInput.setAttribute('aria-invalid', 'false');
                        glueInput.classList.remove('border-danger-500');
                    }
                });

                return valid;
            };

            const bindRemoveAction = (button) => {
                button.addEventListener('click', (event) => {
                    if (event.currentTarget.dataset.fixed === 'true') {
                        return;
                    }

                    event.currentTarget.closest('.ns-item')?.remove();
                    updateLabels();
                    validateAll();
                });
            };

            const addField = (value = '') => {
                const count = fieldsContainer.querySelectorAll('.ns-item').length;

                if (count >= maxCount) {
                    showError(maxMessage);
                    return;
                }

                const node = template.content.firstElementChild.cloneNode(true);
                const input = node.querySelector('input');
                const removeButton = node.querySelector('.remove-ns');
                const glueInput = node.querySelector('.glue-ip-input');

                input.value = value;
                if (glueInput) {
                    glueInput.value = '';
                }
                bindRemoveAction(removeButton);
                fieldsContainer.appendChild(node);

                updateLabels();
                validateAll();
                input.focus();
            };

            fieldsContainer.querySelectorAll('.remove-ns').forEach(bindRemoveAction);

            addButton?.addEventListener('click', () => addField(''));

            fieldsContainer.addEventListener('input', (event) => {
                if (event.target.matches('input[type="text"]')) {
                    updateLabels();
                    validateAll();
                }
            });

            fieldsContainer.closest('form')?.addEventListener('submit', (event) => {
                if (!validateAll()) {
                    event.preventDefault();
                    showError(fixMessage);
                    fieldsContainer.querySelector('[aria-invalid="true"]')?.focus();
                }
            });

            updateLabels();
            validateAll();
        });
    </script>
</x-client-layout>
