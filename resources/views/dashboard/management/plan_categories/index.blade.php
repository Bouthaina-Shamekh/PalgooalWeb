<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.plan-categories', 'Plan Categories') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Plan_Categories_List', 'Plan Categories') }}</h2>
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
    @if(session('error'))
        <div class="alert alert-danger mb-4 flex items-center gap-2">
            <i class="ti ti-alert-circle text-xl"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">

                {{-- Card toolbar --}}
                <div class="card-header">
                    <form method="GET" action="{{ route('dashboard.plan_categories.index') }}"
                          class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3">

                        {{-- Search --}}
                        <div class="relative flex-1 min-w-[180px]">
                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="ti ti-search text-base"></i>
                            </span>
                            <input type="text" name="search"
                                value="{{ $search ?? '' }}"
                                placeholder="{{ t('dashboard.Search_Categories', 'Search categories…') }}"
                                class="w-full border rounded-xl pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        {{-- Per page --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-gray-500 whitespace-nowrap">{{ t('dashboard.Per_Page', 'Per page') }}</span>
                            <select name="per_page" onchange="this.form.submit()"
                                    class="border rounded-xl px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                                @foreach([10, 20, 50] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 20) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Search submit --}}
                        <button type="submit" class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                            <i class="ti ti-search text-base"></i>
                            {{ t('dashboard.Search', 'Search') }}
                        </button>

                        {{-- Clear --}}
                        @if($search ?? '')
                            <a href="{{ route('dashboard.plan_categories.index') }}"
                               class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                                <i class="ti ti-x text-base"></i>
                                {{ t('dashboard.Clear_Search', 'Clear') }}
                            </a>
                        @endif

                        {{-- Add button --}}
                        <a href="{{ route('dashboard.plan_categories.create') }}"
                           class="shrink-0 btn btn-primary flex items-center gap-2 whitespace-nowrap">
                            <i class="ti ti-plus text-base"></i>
                            {{ t('dashboard.Add_Category', 'Add Category') }}
                        </a>

                    </form>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th class="text-right">#</th>
                                    <th class="text-right">{{ t('dashboard.Category_Title', 'Title') }}</th>
                                    <th class="text-right">{{ t('dashboard.Category_Slug', 'Slug') }}</th>
                                    <th class="text-right">{{ t('dashboard.Status', 'Status') }}</th>
                                    <th class="text-right">{{ t('dashboard.Actions', 'Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $cat)
                                    @php
                                        $trans = $cat->translation();
                                    @endphp
                                    <tr>
                                        <td class="text-sm text-gray-500">{{ $cat->id }}</td>

                                        {{-- Title --}}
                                        <td class="font-medium text-sm text-gray-800">
                                            {{ $trans?->title ?? '—' }}
                                        </td>

                                        {{-- Slug --}}
                                        <td class="text-sm font-mono text-gray-500" dir="ltr">
                                            {{ $trans?->slug ?? '—' }}
                                        </td>

                                        {{-- Toggle status --}}
                                        <td>
                                            <form action="{{ route('dashboard.plan_categories.toggle', $cat->id) }}"
                                                  method="POST" style="display:inline-block">
                                                @csrf
                                                @if($cat->is_active)
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                        {{ t('dashboard.Active', 'Active') }}
                                                    </button>
                                                @else
                                                    <button type="submit"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500 border border-gray-200 hover:bg-gray-200 transition">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                                        {{ t('dashboard.Inactive', 'Inactive') }}
                                                    </button>
                                                @endif
                                            </form>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="whitespace-nowrap">
                                            <a href="{{ route('dashboard.plan_categories.edit', $cat->id) }}"
                                               title="{{ t('dashboard.Edit', 'Edit') }}"
                                               class="btn btn-sm btn-light inline-flex items-center gap-1">
                                                <i class="ti ti-edit text-sm"></i>
                                                {{ t('dashboard.Edit', 'Edit') }}
                                            </a>
                                            <form action="{{ route('dashboard.plan_categories.destroy', $cat->id) }}"
                                                  method="POST" style="display:inline-block"
                                                  onsubmit="return confirm('{{ t('dashboard.Confirm_Delete', 'Are you sure?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-light inline-flex items-center gap-1 text-red-500 hover:text-red-700 hover:border-red-200">
                                                    <i class="ti ti-trash text-sm"></i>
                                                    {{ t('dashboard.Delete', 'Delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">
                                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                                </svg>
                                                @if($search ?? '')
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Search_Results', 'No results found') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.Try_Different_Search', 'Try a different search term') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.plan_categories.index') }}" class="btn btn-light btn-sm">
                                                        {{ t('dashboard.Clear_Search', 'Clear search') }}
                                                    </a>
                                                @else
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Categories', 'No categories yet') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.No_Categories_Desc', 'Create your first plan category to organise your hosting plans') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.plan_categories.create') }}"
                                                       class="btn btn-primary btn-sm flex items-center gap-2">
                                                        <i class="ti ti-plus text-base"></i>
                                                        {{ t('dashboard.Add_Category', 'Add Category') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($categories->hasPages())
                        <div class="mt-4">
                            {{ $categories->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
