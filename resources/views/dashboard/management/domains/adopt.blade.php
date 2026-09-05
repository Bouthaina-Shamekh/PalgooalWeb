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
            <h1 class="text-2xl font-bold mb-2">{{ __('Import Existing Domain') }}</h1>
            <p class="text-sm text-gray-500">
                {{ __('هذا الإجراء مخصص فقط للنطاقات المملوكة بالفعل في حساب المزوّد المحدد. لا يقوم هذا الإجراء بتسجيل النطاق أو شرائه — سيقوم PALGOALS بالتحقق من وجود النطاق في حساب Enom المحدد قبل استيراده.') }}
            </p>
        </div>

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-4 rounded">
                <ul class="ps-5 list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('dashboard.domains.adopt.store') }}" method="POST"
            class="bg-white rounded-lg shadow p-6 space-y-6">
            @csrf

            <section class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-2">
                    <label for="domain_name" class="text-sm font-medium text-gray-700">
                        {{ __('Existing domain name') }}
                    </label>
                    <input type="text" id="domain_name" name="domain_name"
                        value="{{ old('domain_name') }}" class="form-control"
                        placeholder="e.g. example.com">
                </div>

                <div class="space-y-2">
                    <label for="client_id" class="text-sm font-medium text-gray-700">
                        {{ __('Client') }}
                    </label>
                    <select id="client_id" name="client_id" class="form-select">
                        <option value="">{{ __('Select Client') }}</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((int) old('client_id') === (int) $client->id)>
                                {{ $client->first_name }} {{ $client->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </section>

            <section class="space-y-2">
                <label for="provider_id" class="text-sm font-medium text-gray-700">
                    {{ __('Enom Live provider') }}
                </label>
                {{-- TLD-3G.1B: only active + live + enom provider rows are ever offered here —
                     the exact provider_id submitted is the exact provider verified, with no
                     fallback of any kind. --}}
                <select id="provider_id" name="provider_id" class="form-select">
                    <option value="">{{ __('Select provider') }}</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}" @selected((int) old('provider_id') === (int) $provider->id)>
                            {{ $provider->name ?: 'eNom' }}
                        </option>
                    @endforeach
                </select>
                @if ($providers->isEmpty())
                    <p class="text-xs text-amber-600 mt-1">
                        {{ __('No active live eNom provider is configured yet.') }}
                    </p>
                @endif
            </section>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-gray-500">
                    {{ __('This does not register the domain. PALGOALS will verify the domain exists in the selected Enom account before importing it.') }}
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('dashboard.domains.index') }}" class="btn btn-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Import domain') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-dashboard-layout>
