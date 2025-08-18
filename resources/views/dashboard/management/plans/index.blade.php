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
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 sm:mb-0">Plans List</h5>
                        <div class="flex items-center gap-2">
                            <button type="button" wire:click="showAdd" class="btn btn-primary">
                                Add Plan
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between mb-4 px-4">
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        wire:input="updateSearch"
                        placeholder="Search plans..."
                        class="w-full sm:w-80 border rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/30"
                    />

                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500">Per page</label>
                        <select
                            wire:model="perPage"
                            wire:change="updatePerPage"
                            class="border rounded-xl px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/30"
                        >
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
                                    <th class="text-right">Billing</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-right">Features</th>
                                    <th class="text-right">Status</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($plans as $plan)
                                    @php
                                        // price formatting
                                        $price = '$' . number_format(($plan->price_cents ?? 0) / 100, 2);

                                        // features casted to array in the model; fallback to []
                                        $features = is_array($plan->features) ? $plan->features : [];
                                        $preview = array_slice($features, 0, 3);
                                        $rest = max(count($features) - 3, 0);

                                        // row index respecting pagination
                                        $rowIndex = ($plans->firstItem() ?? 1) + $loop->index;
                                    @endphp
                                    <tr>
                                        <td>{{ $rowIndex }}</td>
                                        <td class="font-semibold">
                                            {{ $plan->name }}
                                            <div class="text-xs text-gray-500">{{ $plan->slug }}</div>
                                        </td>

                                        <td class="text-sm">
                                            {{ $plan->billing_cycle === 'monthly' ? 'Monthly' : 'Annually' }}
                                        </td>

                                        <td>{{ $price }}</td>

                                        <td class="max-w-[320px]">
                                            @forelse ($preview as $feature)
                                                <span class="badge bg-success-500/10 text-success-600 rounded-full text-xs px-2 py-0.5">
                                                    {{ $feature }}
                                                </span>
                                            @empty
                                                <span class="text-gray-400 text-sm">—</span>
                                            @endforelse

                                            @if ($rest > 0)
                                                <span class="badge bg-success-500/10 text-success-600 rounded-full text-xs px-2 py-0.5">
                                                    +{{ $rest }}
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            @if($plan->is_active)
                                                <span class="badge bg-emerald-500/10 text-emerald-600 rounded-full text-xs px-2 py-0.5">Active</span>
                                            @else
                                                <span class="badge bg-gray-500/10 text-gray-600 rounded-full text-xs px-2 py-0.5">Inactive</span>
                                            @endif
                                        </td>

                                        <td class="whitespace-nowrap">
                                            <button
                                                type="button"
                                                wire:click="showEdit({{ $plan->id }})"
                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                title="Edit"
                                            >
                                                <i class="ti ti-edit text-xl leading-none"></i>
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="delete({{ $plan->id }})"
                                                onclick="confirm('Are you sure?') || event.stopImmediatePropagation()"
                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                title="Delete"
                                            >
                                                <i class="ti ti-trash text-xl leading-none"></i>
                                            </button>
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

                    <div class="mt-4">
                        {{ $plans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>