<x-dashboard-layout>
    @php
        $statusStyles = [
            'active' => 'bg-emerald-100 text-emerald-800',
            'pending' => 'bg-amber-100 text-amber-800',
            'expired' => 'bg-red-100 text-red-800',
        ];
    @endphp

    <div class="container mx-auto py-6">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <h1 class="text-2xl font-bold mb-2">Domain portfolio</h1>
                <p class="text-sm text-gray-500 mb-0">
                    This page manages the registrar-side domain records in the account. Live website routing, platform subdomains, and custom-domain readiness are still tracked from subscriptions.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard.subscriptions.index') }}" class="btn btn-light-primary">Open Subscriptions</a>
                @can('create', \App\Models\Domain::class)
                    <a href="{{ route('dashboard.domains.create') }}" class="btn btn-primary">Add Domains</a>
                @endcan
            </div>
        </div>

        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="table-responsive dt-responsive">
            <table class="table table-striped table-bordered nowrap">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Domain Name</th>
                        <th>Registrar</th>
                        <th>Registered At</th>
                        <th>Renewal Date</th>
                        <th>STATUS</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($domains as $domain)
                        @php
                            $statusKey = strtolower((string) $domain->status);
                            $statusClass = $statusStyles[$statusKey] ?? 'bg-slate-100 text-slate-700';
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $domain->client?->first_name ?? '-' }}</td>
                            <td>
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900">{{ $domain->domain_name }}</span>
                                    <span class="text-xs text-gray-500">Registrar asset</span>
                                </div>
                            </td>
                            <td>{{ $domain->registrar }}</td>
                            <td>{{ $domain->registration_date ? \Illuminate\Support\Carbon::parse($domain->registration_date)->format('d/m/Y') : '-' }}</td>
                            <td>{{ $domain->renewal_date ? \Illuminate\Support\Carbon::parse($domain->renewal_date)->format('d/m/Y') : '-' }}</td>
                            <td>
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ ucfirst($statusKey ?: 'unknown') }}
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center gap-2">
                                    @can('update', $domain)
                                        <a href="{{ route('dashboard.domains.register.edit', $domain->id) }}"
                                            class="inline-flex items-center justify-center px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                                            Register
                                        </a>
                                        <a href="{{ route('dashboard.domains.renew.edit', $domain->id) }}"
                                            class="inline-flex items-center justify-center px-3 py-1 text-sm font-medium text-white bg-emerald-600 rounded hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-400">
                                            Renew
                                        </a>
                                        <a href="{{ route('dashboard.domains.dns.edit', $domain->id) }}"
                                            class="inline-flex items-center justify-center px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                            Change DNS
                                        </a>
                                        <a href="{{ route('dashboard.domains.edit', $domain->id) }}"
                                            class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-edit text-xl leading-none"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $domain)
                                        <form action="{{ route('dashboard.domains.destroy', $domain->id) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" onclick="return confirm('Are you sure you want to delete this domain?')"
                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                                <i class="ti ti-trash text-xl leading-none"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-gray-500">No Domains found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $domains->links() }}
        </div>
    </div>
</x-dashboard-layout>
