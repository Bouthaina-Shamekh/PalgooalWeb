<x-dashboard-layout>

    <div class="container mx-auto py-6">
        <h1 class="text-2xl font-bold mb-4">إدارة النطاقات</h1>

        @can('create', 'App\\Models\\Domain')
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
                        <th>Template</th>
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
                            <td>{{ $domain->registration_date }}</td>
                            <td>{{ $domain->renewal_date }}</td>
                            <td>{{ $domain->template?->name ?? '-' }}</td>
                            <td>{{ $domain->status }}</td>
                            <td>
                                @can('edit', 'App\\Models\\Domain')
                                    <a href="{{ route('dashboard.domains.edit', $domain->id) }}"
                                        class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                        <i class="ti ti-edit text-xl leading-none"></i>
                                    </a>
                                @endcan
                                @can('delete', 'App\\Models\\Domain')
                                    <form action="{{ route('dashboard.domains.destroy', $domain->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-trash text-xl leading-none"></i>
                                        </button>
                                    </form>
                                @endcan
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
