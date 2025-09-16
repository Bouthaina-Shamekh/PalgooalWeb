<x-client-layout>
    {{-- resources/views/client/domains/buy.blade.php --}}

    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript: void(0)">Clients</a></li>
                <li class="breadcrumb-item" aria-current="page">Domain Buy</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Domain Buy</h2>
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
                    <form method="POST" action="{{ route('client.domains.purchase') }}"
                        class="grid grid-cols-12 gap-x-6">
                        @csrf
                        <input type="hidden" name="client_id" value="{{ $domain_data['client_id'] }}">

                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <label for="domain_name" class="form-label">Domain Name</label>
                                <input type="text" class="form-control @error('domain_name') is-invalid @enderror"
                                    id="domain_name" name="domain_name"
                                    value="{{ old('domain_name', $domain_data['domain_name']) }}" readonly>
                                @error('domain_name')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <label for="registrar" class="form-label">Registrar Domain</label>
                                <select id="registrar" name="registrar"
                                    class="form-select @error('registrar') is-invalid @enderror">
                                    <option value="" @selected(old('registrar', $domain_data['registrar']) == '')>-- Select Registrar Domain --
                                    </option>
                                    <option value="enom" @selected(old('registrar', $domain_data['registrar']) == 'enom')>enom</option>
                                    <option value="namecheap" @selected(old('registrar', $domain_data['registrar']) == 'namecheap')>namecheap</option>
                                    <option value="godaddy" @selected(old('registrar', $domain_data['registrar']) == 'godaddy')>godaddy</option>
                                </select>
                                @error('registrar')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <label for="registration_date" class="form-label">Registration Date</label>
                                <input type="date"
                                    class="form-control @error('registration_date') is-invalid @enderror"
                                    id="registration_date" name="registration_date"
                                    value="{{ old('registration_date', $domain_data['registration_date']) }}">
                                @error('registration_date')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <label for="renewal_date" class="form-label">Renewal Date</label>
                                <input type="date" class="form-control @error('renewal_date') is-invalid @enderror"
                                    id="renewal_date" name="renewal_date"
                                    value="{{ old('renewal_date', $domain_data['renewal_date']) }}">
                                @error('renewal_date')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status"
                                    class="form-select @error('status') is-invalid @enderror">
                                    <option value="">-- Select Status --</option>
                                    <option value="active" @selected(old('status', $domain_data['status']) == 'active')>active</option>
                                    <option value="expired" @selected(old('status', $domain_data['status']) == 'expired')>expired</option>
                                    <option value="pending" @selected(old('status', $domain_data['status']) == 'pending')>pending</option>
                                </select>
                                @error('status')
                                    <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="col-span-12 text-right">
                            <a href="{{ route('client.domains.search') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Buy</button>
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
                const form = document.getElementById('purchaseForm');
                const purchaseBtn = document.getElementById('purchaseBtn');
                const registrationDate = document.getElementById('registration_date');
                const renewalDate = document.getElementById('renewal_date');

                // Auto-calculate renewal date when registration date changes
                registrationDate.addEventListener('change', function() {
                    if (this.value) {
                        const regDate = new Date(this.value);
                        regDate.setFullYear(regDate.getFullYear() + 1);
                        renewalDate.value = regDate.toISOString().split('T')[0];
                    }
                });

                // Form submission handling
                form.addEventListener('submit', function(e) {
                    // Show loading state
                    purchaseBtn.innerHTML =
                        '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                    purchaseBtn.disabled = true;

                    // Re-enable after 10 seconds in case of issues
                    setTimeout(function() {
                        purchaseBtn.innerHTML =
                            '<i class="feather icon-shopping-cart me-2"></i>Complete Purchase';
                        purchaseBtn.disabled = false;
                    }, 10000);
                });
            });
        </script>
    @endpush

</x-client-layout>
