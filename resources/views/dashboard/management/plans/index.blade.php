<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Plans</a></li>
                <li class="breadcrumb-item" aria-current="page">Plans List</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Plans List</h2>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                @if (session('connection_result'))
                    <div class="alert alert-info mb-4">{{ session('connection_result') }}</div>
                @endif

                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 sm:mb-0">Plans List</h5>
                        <a href="{{ route('dashboard.plans.create') }}" class="btn btn-primary">Add Plan</a>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between mb-4 px-4">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search plans..."
                        class="w-full sm:w-80 border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30" />

                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500">Per page</label>
                        <select wire:model="perPage"
                            class="border rounded-xl px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                        </select>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th class="text-right">#</th>
                                    <th class="text-right">Name</th>
                                    <th class="text-right">Category</th>
                                    <th class="text-right">Server</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-right">Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($plans as $index => $plan)
                                    @php
                                        $rowIndex = ($plans->firstItem() ?? 1) + $index;
                                        $translation = $plan->translations
                                            ->where('locale', app()->getLocale())
                                            ->first();

                                        $monthly = $plan->monthly_price_cents
                                            ? '$' . number_format($plan->monthly_price_cents / 100, 2)
                                            : null;
                                        $annual = $plan->annual_price_cents
                                            ? '$' . number_format($plan->annual_price_cents / 100, 2)
                                            : null;
                                    @endphp
                                    <tr>
                                        <td>{{ $rowIndex }}</td>
                                        <td class="font-semibold">
                                            {{ $translation?->title ?? $plan->slug }}
                                            <div class="text-xs text-gray-500">{{ $plan->slug }}</div>
                                        </td>
                                        <td>{{ $plan->category?->translations->first()?->title ?? '-' }}</td>
                                        <td>{{ $plan->server?->name ?? '-' }}</td>
                                        <td>
                                            @if ($monthly || $annual)
                                                @if ($monthly)
                                                    <div><strong>Monthly:</strong> {{ $monthly }}</div>
                                                @endif
                                                @if ($annual)
                                                    <div><strong>Annual:</strong> {{ $annual }}</div>
                                                @endif
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        {{-- Features column removed --}}
                                        <td>
                                            <form action="{{ route('dashboard.plans.toggle', $plan->id) }}"
                                                method="POST" style="display:inline">
                                                @csrf
                                                <button type="submit" class="w-full text-left">
                                                    @if ($plan->is_active)
                                                        <span
                                                            class="badge bg-emerald-500/10 text-emerald-600 rounded-full text-xs px-2 py-0.5">Active</span>
                                                    @else
                                                        <span
                                                            class="badge bg-gray-500/10 text-gray-600 rounded-full text-xs px-2 py-0.5">Inactive</span>
                                                    @endif
                                                </button>
                                            </form>
                                        </td>
                                        <td class="whitespace-nowrap flex gap-1">
                                            <a href="{{ route('dashboard.plans.edit', $plan->id) }}"
                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                title="Edit">
                                                <i class="ti ti-edit text-xl leading-none"></i>
                                            </a>
                                            <form action="{{ route('dashboard.plans.destroy', $plan->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this plan?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                    title="Delete">
                                                    <i class="ti ti-trash text-xl leading-none"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-gray-500 py-8">No plans found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $plans->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
