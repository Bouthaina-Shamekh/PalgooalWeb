<x-client-layout>
    {{-- resources/views/client/domains/search.blade.php --}}

    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript: void(0)">Clients</a></li>
                <li class="breadcrumb-item" aria-current="page">Domain Name Search</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Domain Name Search</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->

    {{-- Success/Error Messages --}}
    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('client.domains.search.process') }}"
                        class="grid grid-cols-12 gap-x-6">
                        @csrf
                        <div class="col-span-12">
                            <div class="input-group btn-group mb-4">
                                <input type="text" class="form-control" placeholder="Domain Name" name="domain_name"
                                    value="{{ old('domain_name') }}" required />
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @foreach ($domain_extensions as $extension => $price)
                                        <li><a class="dropdown-item extension-option"
                                                data-extension="{{ $extension }}">{{ $extension }} -
                                                {{ $price }}$</a></li>
                                    @endforeach
                                    <li>
                                        <hr class="dropdown-divider" />
                                    </li>
                                    <li>
                                        <input type="text" class="form-control dropdown-item" placeholder=".com"
                                            id="customExtension">
                                    </li>
                                </ul>
                                <button class="btn btn-outline-success dropdown-toggle" type="button"
                                    data-pc-toggle="dropdown" aria-expanded="false" id="extensionButton">
                                    .com
                                </button>
                                <input type="hidden" name="domain_extension" value=".com" id="selectedExtension">
                            </div>

                            @error('domain_name')
                                <div class="text-danger mb-3">{{ $message }}</div>
                            @enderror
                            @error('domain_extension')
                                <div class="text-danger mb-3">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-span-12 text-right">
                            <a href="{{ route('client.domains.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Handle extension selection
                const extensionOptions = document.querySelectorAll('.extension-option');
                const extensionButton = document.getElementById('extensionButton');
                const selectedExtension = document.getElementById('selectedExtension');
                const customExtension = document.getElementById('customExtension');

                extensionOptions.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.preventDefault();
                        const extension = this.dataset.extension;
                        extensionButton.textContent = extension;
                        selectedExtension.value = extension;
                    });
                });

                // Handle custom extension
                customExtension.addEventListener('input', function() {
                    let value = this.value;
                    if (!value.startsWith('.')) {
                        value = '.' + value;
                    }
                    extensionButton.textContent = value;
                    selectedExtension.value = value;
                });
            });
        </script>
    @endpush
</x-client-layout>
