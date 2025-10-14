<x-dashboard-layout>

    <div class="container mx-auto py-6">
        <h1 class="text-2xl font-bold mb-4">إدارة النطاقات</h1>

        @can('create', \App\Models\Domain::class)
            <a href="{{ route('dashboard.domains.create') }}" class="btn btn-primary mb-4">Add Domains</a>
        @endcan

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
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $domain->client?->first_name ?? '-' }}</td>
                            <td>{{ $domain->domain_name }}</td>
                            <td>{{ $domain->registrar }}</td>
                            <td>{{ $domain->registration_date ? \Illuminate\Support\Carbon::parse($domain->registration_date)->format('d/m/Y') : '-' }}</td>
                            <td>{{ $domain->renewal_date ? \Illuminate\Support\Carbon::parse($domain->renewal_date)->format('d/m/Y') : '-' }}</td>
                            <td>{{ $domain->status }}</td>
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
