<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">Home</a></li>
                <li class="breadcrumb-item" aria-current="page">Plan Categories</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Plan Categories</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header flex items-center justify-between">
                    <h5 class="mb-0">Categories List</h5>
                    <a href="{{ route('dashboard.plan_categories.create') }}" class="btn btn-primary">Add Category</a>
                </div>
                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Slug</th>
                                    <th>Active</th>
                                    <th>Title ({{ app()->getLocale() }})</th>
                                    <th>Description ({{ app()->getLocale() }})</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $cat)
                                    @php
                                        $trans = $cat->translation();
                                        $activeClass = $cat->is_active
                                            ? 'bg-green-500 text-white border border-green-400'
                                            : 'bg-red-500 text-white border border-red-400';
                                        $activeText = $cat->is_active ? 'تعطيل' : 'تفعيل';
                                    @endphp
                                    <tr>
                                        <td>{{ $cat->id }}</td>
                                        <td>{{ $trans?->slug ?? '-' }}</td>
                                        <td>
                                            <form action="{{ route('dashboard.plan_categories.toggle', $cat->id) }}" method="POST" style="display:inline-block">
                                                @csrf
                                                <button type="submit" class="px-2 py-1 rounded text-xs font-bold focus:outline-none transition-all {{ $activeClass }}">
                                                    {{ $activeText }}
                                                </button>
                                            </form>
                                        </td>
                                        <td>{{ $trans?->title ?? '-' }}</td>
                                        <td>{{ $trans?->description ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('dashboard.plan_categories.edit', $cat->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                                            <form action="{{ route('dashboard.plan_categories.destroy', $cat->id) }}" method="POST" style="display:inline-block" onsubmit="return confirm('Delete?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-gray-500 py-8">No categories found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $categories->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
