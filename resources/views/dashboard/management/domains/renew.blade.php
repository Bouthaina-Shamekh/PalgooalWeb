<x-dashboard-layout>
    <div class="container mx-auto py-6 max-w-4xl space-y-6">
        <div>
            <a href="{{ route('dashboard.domains.index') }}"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition">
                <i class="ti ti-arrow-left me-1"></i>
                {{ __('Back to domains') }}
            </a>
        </div>

        <div>
            <h1 class="text-2xl font-bold mb-2">{{ __('Renew Domain: :domain', ['domain' => $domain->domain_name]) }}</h1>
            <p class="text-sm text-gray-500">
                {{ __('Review the current renewal information and update the next renewal date before confirming.') }}
            </p>
        </div>

        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-4 rounded">
                <ul class="ps-5 list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $renewalValue = old('renewal_date', $suggestedRenewal);
            $statusValue = old('status', $domain->status);
            $paymentMethodValue = old('payment_method', $domain->payment_method);
        @endphp

        <section class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-lg font-semibold">{{ __('Current renewal details') }}</h2>
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
                <div>
                    <dt class="text-gray-500">{{ __('Current renewal date') }}</dt>
                    <dd class="font-medium">{{ $currentRenewal }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('Suggested next renewal') }}</dt>
                    <dd class="font-medium">{{ $suggestedRenewal }}</dd>
                </div>
            </dl>
        </section>

        <form action="{{ route('dashboard.domains.renew.update', $domain->id) }}" method="POST"
            class="bg-white rounded-lg shadow p-6 space-y-6">
            @csrf
            @method('PUT')

            <section class="grid grid-cols-1 gap-6 md:grid-cols-2 items-end">
                <div class="space-y-2">
                    <label for="renewal_date" class="text-sm font-medium text-gray-700">
                        {{ __('Next renewal date') }}
                    </label>
                    <input type="date" id="renewal_date" name="renewal_date" value="{{ $renewalValue }}"
                        class="form-control">
                </div>
                <div class="flex gap-2">
                    <button type="button" id="use-suggested-renewal"
                        class="btn btn-secondary"
                        data-suggested="{{ $suggestedRenewal }}">
                        {{ __('Use suggested date') }}
                    </button>
                    <button type="button" id="add-year-renewal" class="btn btn-outline-secondary">
                        {{ __('Add 1 year') }}
                    </button>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="status" class="text-sm font-medium text-gray-700">
                        {{ __('Status after renewal') }}
                    </label>
                    <select id="status" name="status" class="form-select">
                        <option value="">{{ __('Select status') }}</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label for="payment_method" class="text-sm font-medium text-gray-700">
                        {{ __('Payment method (optional)') }}
                    </label>
                    <input type="text" id="payment_method" name="payment_method" class="form-control"
                        value="{{ $paymentMethodValue }}" placeholder="{{ __('e.g. credit card, bank transfer') }}">
                </div>
            </section>

            <section class="space-y-2">
                <label for="renew-notes" class="text-sm font-medium text-gray-700">
                    {{ __('Internal note (optional)') }}
                </label>
                <textarea id="renew-notes" name="notes" rows="4" class="form-control"
                    placeholder="{{ __('Add any context for the finance or operations team.') }}">{{ old('notes') }}</textarea>
            </section>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-gray-500">
                    {{ __('Renewal submission updates the domain record. Automated registrar updates will arrive in a future release.') }}
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('dashboard.domains.index') }}" class="btn btn-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save renewal') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const renewalInput = document.getElementById('renewal_date');
            const suggestedButton = document.getElementById('use-suggested-renewal');
            const addYearButton = document.getElementById('add-year-renewal');

            if (suggestedButton && renewalInput) {
                suggestedButton.addEventListener('click', () => {
                    const suggested = suggestedButton.dataset.suggested;
                    if (suggested) {
                        renewalInput.value = suggested;
                    }
                });
            }

            if (addYearButton && renewalInput) {
                addYearButton.addEventListener('click', () => {
                    if (!renewalInput.value) {
                        return;
                    }
                    const currentDate = new Date(renewalInput.value);
                    if (Number.isNaN(currentDate.getTime())) {
                        return;
                    }
                    currentDate.setFullYear(currentDate.getFullYear() + 1);
                    const month = String(currentDate.getMonth() + 1).padStart(2, '0');
                    const day = String(currentDate.getDate()).padStart(2, '0');
                    renewalInput.value = `${currentDate.getFullYear()}-${month}-${day}`;
                });
            }
        });
    </script>
</x-dashboard-layout>
