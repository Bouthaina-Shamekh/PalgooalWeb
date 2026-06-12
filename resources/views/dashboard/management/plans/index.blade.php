<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.plans', 'Plans') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.plans', 'Plans') }}</h2>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('ok'))
        <div class="alert alert-success mb-4 flex items-center gap-2">
            <i class="ti ti-circle-check text-xl"></i>
            <span>{{ session('ok') }}</span>
        </div>
    @endif
    @if(session('connection_result'))
        <div class="alert alert-info mb-4 flex items-center gap-2">
            <i class="ti ti-info-circle text-xl"></i>
            <span>{{ session('connection_result') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">

                {{-- Card toolbar --}}
                <div class="card-header">
                    <form method="GET" action="{{ route('dashboard.plans.index') }}"
                          class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

                        {{-- Search --}}
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="ti ti-search text-base"></i>
                            </span>
                            <input
                                type="text"
                                name="search"
                                value="{{ $search ?? '' }}"
                                placeholder="{{ t('dashboard.Search_Plans', 'Search plans…') }}"
                                class="w-full border rounded-xl pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                            />
                        </div>

                        {{-- Per-page --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-gray-500 whitespace-nowrap">{{ t('dashboard.Per_Page', 'Per page') }}</span>
                            <select name="per_page" onchange="this.form.submit()"
                                    class="border rounded-xl px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                                @foreach([5, 10, 25] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Clear --}}
                        @if($search)
                            <a href="{{ route('dashboard.plans.index') }}"
                               class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                                <i class="ti ti-x text-base"></i>
                                {{ t('dashboard.Clear_Search', 'Clear') }}
                            </a>
                        @endif

                        {{-- Add plan --}}
                        <a href="{{ route('dashboard.plans.create') }}"
                           class="shrink-0 btn btn-primary flex items-center gap-2 whitespace-nowrap">
                            <i class="ti ti-plus text-base"></i>
                            {{ t('dashboard.Add_Plan', 'Add plan') }}
                        </a>

                    </form>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th class="text-right w-10">#</th>
                                    <th class="text-right">{{ t('dashboard.Plan_Name', 'Name') }}</th>
                                    <th class="text-right">{{ t('dashboard.Plan_Category', 'Category') }}</th>
                                    <th class="text-right">{{ t('dashboard.Plan_Server', 'Server') }}</th>
                                    <th class="text-right">{{ t('dashboard.Plan_Price', 'Price') }}</th>
                                    <th class="text-right">{{ t('dashboard.Plan_Featured', 'Featured') }}</th>
                                    <th class="text-right">{{ t('dashboard.Status', 'Status') }}</th>
                                    <th class="text-right">{{ t('dashboard.Actions', 'Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($plans as $index => $plan)
                                    @php
                                        $rowIndex       = ($plans->firstItem() ?? 1) + $index;
                                        $translation    = $plan->translations->where('locale', app()->getLocale())->first()
                                                          ?? $plan->translations->first();
                                        $catTranslation = $plan->category?->translations->firstWhere('locale', app()->getLocale())
                                                          ?? $plan->category?->translations->first();
                                        $monthly = $plan->monthly_price_cents
                                            ? '$' . number_format($plan->monthly_price_cents / 100, 2)
                                            : null;
                                        $annual  = $plan->annual_price_cents
                                            ? '$' . number_format($plan->annual_price_cents / 100, 2)
                                            : null;
                                    @endphp
                                    <tr>
                                        <td class="text-gray-400 text-sm">{{ $rowIndex }}</td>

                                        {{-- Name + slug + featured badge --}}
                                        <td>
                                            <span class="font-semibold text-gray-800">
                                                {{ $translation?->title ?? $plan->slug }}
                                            </span>
                                            <div class="text-xs text-gray-400 font-mono">{{ $plan->slug }}</div>
                                            @if($plan->is_featured)
                                                <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-primary/10 text-primary text-[11px] px-2 py-0.5">
                                                    <i class="ti ti-star-filled text-xs"></i>
                                                    {{ $plan->featured_label ?? t('dashboard.Most_Popular', 'Most Popular') }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Category --}}
                                        <td class="text-sm text-gray-600">
                                            {{ $catTranslation?->title ?? '—' }}
                                        </td>

                                        {{-- Server --}}
                                        <td class="text-sm text-gray-600">
                                            {{ $plan->server?->name ?? '—' }}
                                        </td>

                                        {{-- Price --}}
                                        <td class="text-sm">
                                            @if($monthly || $annual)
                                                <div class="space-y-0.5">
                                                    @if($monthly)
                                                        <div class="flex items-center gap-1">
                                                            <span class="text-xs text-gray-400">{{ t('dashboard.Monthly', 'Monthly') }}</span>
                                                            <span class="font-medium text-gray-800">{{ $monthly }}</span>
                                                        </div>
                                                    @endif
                                                    @if($annual)
                                                        <div class="flex items-center gap-1">
                                                            <span class="text-xs text-gray-400">{{ t('dashboard.Annual', 'Annual') }}</span>
                                                            <span class="font-medium text-gray-800">{{ $annual }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>

                                        {{-- Featured --}}
                                        <td>
                                            @if($plan->is_featured)
                                                <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-600">
                                                    <i class="ti ti-star-filled text-xs"></i>
                                                    {{ $plan->featured_label ?? t('dashboard.Most_Popular', 'Most Popular') }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>

                                        {{-- Status toggle --}}
                                        <td>
                                            <form action="{{ route('dashboard.plans.toggle', $plan->id) }}" method="POST" style="display:inline">
                                                @csrf
                                                <button type="submit"
                                                        title="{{ t('dashboard.Toggle_Status', 'Toggle status') }}"
                                                        class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-0.5 rounded-full transition
                                                            {{ $plan->is_active
                                                                ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'
                                                                : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                                    <span class="w-1.5 h-1.5 rounded-full {{ $plan->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                                    {{ $plan->is_active ? t('dashboard.Active', 'Active') : t('dashboard.Inactive', 'Inactive') }}
                                                </button>
                                            </form>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="whitespace-nowrap">
                                            <div class="flex items-center gap-0.5">
                                                <a href="{{ route('dashboard.plans.edit', $plan->id) }}"
                                                   class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                   title="{{ t('dashboard.Edit', 'Edit') }}">
                                                    <i class="ti ti-edit text-lg leading-none"></i>
                                                </a>
                                                <form action="{{ route('dashboard.plans.destroy', $plan->id) }}"
                                                      method="POST"
                                                      style="display:inline-block"
                                                      onsubmit="return confirm('{{ t('dashboard.Confirm_Delete', 'Are you sure you want to delete this?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="w-8 h-8 rounded-xl inline-flex items-center justify-center text-red-400 hover:text-red-600 hover:bg-red-50 transition"
                                                            title="{{ t('dashboard.Delete', 'Delete') }}">
                                                        <i class="ti ti-trash text-lg leading-none"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                                </svg>
                                                @if($search)
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Search_Results', 'No results found') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.Try_Different_Search', 'Try a different search term') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.plans.index') }}" class="btn btn-light btn-sm">
                                                        {{ t('dashboard.Clear_Search', 'Clear search') }}
                                                    </a>
                                                @else
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Plans', 'No plans yet') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.No_Plans_Desc', 'Create your first plan to start offering subscriptions') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.plans.create') }}" class="btn btn-primary btn-sm flex items-center gap-2">
                                                        <i class="ti ti-plus text-base"></i>
                                                        {{ t('dashboard.Add_Plan', 'Add plan') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($plans->hasPages())
                        <div class="mt-4">
                            {{ $plans->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-dashboard-layout>
