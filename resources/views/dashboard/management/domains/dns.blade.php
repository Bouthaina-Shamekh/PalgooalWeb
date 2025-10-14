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
            <h1 class="text-2xl font-bold mb-2">{{ __('Change DNS for :domain', ['domain' => $domain->domain_name]) }}</h1>
            <p class="text-sm text-gray-500">
                {{ __('Update the nameservers or leave a note for the operations team to process this request.') }}
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

        <form action="{{ route('dashboard.domains.dns.update', $domain->id) }}" method="POST"
            class="bg-white rounded-lg shadow p-6 space-y-6">
            @csrf
            @method('PUT')

            <section>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold">{{ __('Nameservers') }}</h2>
                        <p class="text-sm text-gray-500">
                            {{ __('Provide at least two nameservers. Additional entries are optional.') }}
                        </p>
                    </div>
                    <button type="button" id="add-nameserver"
                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <i class="ti ti-plus me-1"></i>
                        {{ __('Add nameserver') }}
                    </button>
                </div>

                <div id="nameserver-fields" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    @foreach (old('nameservers', $nameservers ?? []) as $index => $nameserver)
                        <div class="space-y-1">
                            <label for="nameserver_{{ $index }}" class="text-sm font-medium text-gray-700">
                                {{ __('Nameserver :number', ['number' => $index + 1]) }}
                            </label>
                            <input type="text" name="nameservers[{{ $index }}]" id="nameserver_{{ $index }}"
                                value="{{ $nameserver }}" class="form-control" placeholder="ns{{ $index + 1 }}.example.com">
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="space-y-2">
                <label for="dns-notes" class="text-sm font-medium text-gray-700">
                    {{ __('Internal note (optional)') }}
                </label>
                <textarea id="dns-notes" name="notes" rows="4" class="form-control"
                    placeholder="{{ __('Share any additional instructions for the team handling this change.') }}">{{ old('notes') }}</textarea>
            </section>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-gray-500">
                    {{ __('DNS updates may require manual confirmation with the registrar until automation is completed.') }}
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('dashboard.domains.index') }}" class="btn btn-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save DNS settings') }}
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fieldsContainer = document.getElementById('nameserver-fields');
            const addButton = document.getElementById('add-nameserver');

            if (!fieldsContainer || !addButton) {
                return;
            }

            addButton.addEventListener('click', () => {
                const inputs = fieldsContainer.querySelectorAll('input[name^="nameservers"]');
                const nextIndex = inputs.length;

                const wrapper = document.createElement('div');
                wrapper.className = 'space-y-1';

                const label = document.createElement('label');
                label.setAttribute('for', `nameserver_${nextIndex}`);
                label.className = 'text-sm font-medium text-gray-700';
                label.textContent = `{{ __('Nameserver') }} ${nextIndex + 1}`;

                const input = document.createElement('input');
                input.type = 'text';
                input.name = `nameservers[${nextIndex}]`;
                input.id = `nameserver_${nextIndex}`;
                input.className = 'form-control';
                input.placeholder = `ns${nextIndex + 1}.example.com`;

                wrapper.appendChild(label);
                wrapper.appendChild(input);
                fieldsContainer.appendChild(wrapper);
            });
        });
    </script>
</x-dashboard-layout>
