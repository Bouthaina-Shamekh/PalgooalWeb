<x-client-layout>


<div class="container mx-auto py-6">
    <h1 class="text-2xl font-bold mb-4">إدارة النطاقات</h1>

    <a href="{{ route('client.domains.search') }}" class="btn btn-primary mb-4">Add Domains</a>

    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-4 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="table-responsive dt-responsive">
        <table class="table table-striped table-bordered nowrap">
            <thead>
                <tr>
                    <th>#</th>
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
                        <td>{{ $domain->domain_name }}</td>
                        <td>{{ $domain->registrar }}</td>
                        <td>{{ $domain->registration_date }}</td>
                        <td>{{ $domain->renewal_date }}</td>
                        <td>{{ $domain->status }}</td>
                        <td>{{ $domain->template?->name }}</td>
                        <td>
                            <a href="{{ route('client.domains.edit', $domain->id) }}" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                <i class="ti ti-edit text-xl leading-none"></i>
                            </a>
                            <form action="{{ route('client.domains.destroy', $domain->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                    <i class="ti ti-trash text-xl leading-none"></i>
                                </button>
                            </form>
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
</x-client-layout>
