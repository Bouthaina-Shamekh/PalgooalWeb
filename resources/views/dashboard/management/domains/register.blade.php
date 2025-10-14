<x-dashboard-layout>
    <div class="container mx-auto py-6 max-w-5xl space-y-6">
        <div>
            <a href="{{ route('dashboard.domains.index') }}"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition">
                <i class="ti ti-arrow-left me-1"></i>
                {{ __('Back to domains') }}
            </a>
        </div>

        <div>
            <h1 class="text-2xl font-bold mb-2">{{ __('Register Domain: :domain', ['domain' => $domain->domain_name]) }}</h1>
            <p class="text-sm text-gray-500">
                {{ __('Confirm registrar details and important dates before submitting the registration request.') }}
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
            $registrationDateValue = old('registration_date', $domain->registration_date ? \Illuminate\Support\Carbon::parse($domain->registration_date)->format('Y-m-d') : '');
            $renewalDateValue = old('renewal_date', $domain->renewal_date ? \Illuminate\Support\Carbon::parse($domain->renewal_date)->format('Y-m-d') : '');
        @endphp

        <form action="{{ route('dashboard.domains.register.update', $domain->id) }}" method="POST"
            class="bg-white rounded-lg shadow p-6 space-y-6">
            @csrf
            @method('PUT')

            <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="registrar" class="text-sm font-medium text-gray-700">
                        {{ __('Registrar') }}
                    </label>
                    <select id="registrar" name="registrar" class="form-select">
                        <option value="">{{ __('Select registrar') }}</option>
                        @foreach ($registrarOptions as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('registrar', $domain->registrar) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label for="status" class="text-sm font-medium text-gray-700">
                        {{ __('Status') }}
                    </label>
                    @php
                        $statusOptions = [
                            'active' => __('Active'),
                            'pending' => __('Pending'),
                            'expired' => __('Expired'),
                        ];
                    @endphp
                    <select id="status" name="status" class="form-select">
                        <option value="">{{ __('Select status') }}</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('status', $domain->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="registration_date" class="text-sm font-medium text-gray-700">
                        {{ __('Registration date') }}
                    </label>
                    <input type="date" id="registration_date" name="registration_date"
                        value="{{ $registrationDateValue }}" class="form-control">
                </div>
                <div class="space-y-2">
                    <label for="renewal_date" class="text-sm font-medium text-gray-700">
                        {{ __('Renewal date') }}
                    </label>
                    <input type="date" id="renewal_date" name="renewal_date" value="{{ $renewalDateValue }}"
                        class="form-control">
                </div>
            </section>

            <section class="space-y-2">
                <label for="register-notes" class="text-sm font-medium text-gray-700">
                    {{ __('Internal note (optional)') }}
                </label>
                <textarea id="register-notes" name="notes" rows="4" class="form-control"
                    placeholder="{{ __('Include any information the operations team should review before processing.') }}">{{ old('notes') }}</textarea>
            </section>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-gray-500">
                    {{ __('Submitting will update the domain record. Automated registrar communication will be added in a future release.') }}
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('dashboard.domains.index') }}" class="btn btn-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save registration details') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-dashboard-layout>
