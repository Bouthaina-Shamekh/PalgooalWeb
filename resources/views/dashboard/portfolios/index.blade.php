<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.portfolios', 'Portfolios') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Portfolio_List', 'Portfolios') }}</h2>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('ok'))
        <div class="alert alert-success mb-4">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <div class="portfolio-index grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">

                {{-- Card toolbar --}}
                <div class="card-header">
                    <form method="GET" action="{{ route('dashboard.portfolios.index') }}"
                          class="portfolio-toolbar flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3">

                        {{-- Search --}}
                        <div class="relative flex-1 min-w-[200px]">
                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="ti ti-search text-base"></i>
                            </span>
                            <input type="text" name="search"
                                   aria-label="{{ t('dashboard.Search', 'Search') }}"
                                   value="{{ $search ?? '' }}"
                                   placeholder="{{ t('dashboard.Search_Portfolios', 'Search by title, type, client…') }}"
                                   class="portfolio-placeholder w-full border rounded-xl pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        {{-- Per page --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <label for="portfolio-per-page" class="text-sm text-gray-500 whitespace-nowrap">{{ t('dashboard.Per_Page', 'Per page') }}</label>
                            <select id="portfolio-per-page" name="per_page" onchange="this.form.submit()"
                                    class="border rounded-xl px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                                @foreach([10, 25, 50] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Search submit --}}
                        <button type="submit"
                                class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                            <i class="ti ti-search text-base"></i>
                            {{ t('dashboard.Search', 'Search') }}
                        </button>

                        {{-- Clear --}}
                        @if(!empty($search))
                            <a href="{{ route('dashboard.portfolios.index') }}"
                               class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                                <i class="ti ti-x text-base"></i>
                                {{ t('dashboard.Clear_Search', 'Clear') }}
                            </a>
                        @endif

                        {{-- Add --}}
                        @can('create', 'App\\Models\\Portfolio')
                            <a href="{{ route('dashboard.portfolios.create', $returnContext) }}"
                               class="shrink-0 btn btn-primary flex items-center gap-2 whitespace-nowrap">
                                <i class="ti ti-plus text-base"></i>
                                {{ t('dashboard.Add_Portfolio', 'Add Portfolio') }}
                            </a>
                        @endcan

                    </form>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="portfolio-table table table-hover w-full">
                            <thead>
                                <tr>
                                    <th class="text-right">#</th>
                                    <th class="text-right">{{ t('dashboard.Portfolio_Image', 'Image') }}</th>
                                    <th class="text-right">{{ t('dashboard.Portfolio_Title', 'Title') }}</th>
                                    <th class="text-right">{{ t('dashboard.Portfolio_Type', 'Type') }}</th>
                                    <th class="text-right">{{ t('dashboard.Portfolio_Status', 'Status') }}</th>
                                    <th class="text-right">{{ t('dashboard.Portfolio_Client', 'Client') }}</th>
                                    <th class="text-right">{{ t('dashboard.Portfolio_Order', 'Order') }}</th>
                                    <th class="text-right">{{ t('dashboard.Actions', 'Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($portfolios as $portfolio)
                                    @php
                                        $trans = $portfolio->translations->firstWhere('locale', app()->getLocale())
                                              ?? $portfolio->translations->first();
                                    @endphp
                                    <tr>
                                        {{-- Row number --}}
                                        <td class="portfolio-secondary text-sm text-gray-500">
                                            {{ ($portfolios->firstItem() ?? 1) + $loop->index }}
                                        </td>

                                        {{-- Image — ADR-005: prefer FK relation path, fall back to old column --}}
                                        <td>
                                            @php $portfolioImagePath = $portfolio->resolvedDefaultImagePath(); @endphp
                                            @if ($portfolioImagePath)
                                                <img src="{{ asset('storage/' . $portfolioImagePath) }}"
                                                     class="portfolio-thumbnail w-12 h-10 object-cover rounded-lg border border-gray-200"
                                                     @if (! $loop->first) loading="lazy" @endif
                                                     alt="" />
                                            @else
                                                <span class="portfolio-image-empty inline-flex items-center justify-center w-12 h-10 rounded-lg bg-gray-100 text-gray-300">
                                                    <i class="ti ti-photo text-lg"></i>
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Title --}}
                                        <td>
                                            <span class="portfolio-title text-sm font-medium text-gray-800">
                                                {{ $trans?->title ?? '—' }}
                                            </span>
                                        </td>

                                        {{-- Type --}}
                                        <td>
                                            @if ($trans?->type)
                                                <span class="portfolio-type-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                                    {{ $trans->type }}
                                                </span>
                                            @else
                                                <span class="portfolio-secondary text-gray-300">—</span>
                                            @endif
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            @if ($trans?->status)
                                                @php
                                                    $isCanonicalStatus = isset($statusStyles[$trans->status]);
                                                    $statusClass = $statusStyles[$trans->status] ?? 'portfolio-status-unknown bg-gray-100 text-gray-600 border border-gray-200';
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}"
                                                      @unless ($isCanonicalStatus) title="{{ t('dashboard.Portfolio_Legacy_Status', 'Legacy status') }}" @endunless>
                                                    {{ $trans->status }}
                                                </span>
                                            @else
                                                <span class="portfolio-secondary text-gray-300">—</span>
                                            @endif
                                        </td>

                                        {{-- Client --}}
                                        <td class="portfolio-client text-sm text-gray-600">
                                            {{ $portfolio->client ?: '—' }}
                                        </td>

                                        {{-- Order --}}
                                        <td>
                                            <span class="portfolio-order inline-flex items-center justify-center w-7 h-7 bg-gray-100 text-gray-600 rounded-full text-xs font-semibold">
                                                {{ $portfolio->order }}
                                            </span>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="whitespace-nowrap">
                                            <div class="portfolio-row-actions flex items-center gap-0.5">

                                                @can('update', $portfolio)
                                                    <a href="{{ route('dashboard.portfolios.edit', ['portfolio' => $portfolio->id] + $returnContext) }}"
                                                       title="{{ t('dashboard.Edit', 'Edit') }}"
                                                       aria-label="{{ t('dashboard.Edit', 'Edit') }}"
                                                       class="portfolio-edit-action w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary hover:bg-yellow-50 hover:text-yellow-600 transition-colors">
                                                        <i class="ti ti-edit text-base leading-none"></i>
                                                    </a>
                                                @endcan

                                                @can('delete', $portfolio)
                                                    <form method="POST"
                                                          action="{{ route('dashboard.portfolios.destroy', $portfolio->id) }}"
                                                          style="display:inline-block"
                                                          class="portfolio-delete-form"
                                                          data-confirm="{{ t('dashboard.Confirm_Delete_Portfolio', 'Are you sure you want to delete this portfolio?') }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                title="{{ t('dashboard.Delete', 'Delete') }}"
                                                                aria-label="{{ t('dashboard.Delete', 'Delete') }}"
                                                                class="portfolio-delete-action w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary hover:bg-red-50 hover:text-red-600 transition-colors">
                                                            <i class="ti ti-trash text-base leading-none"></i>
                                                        </button>
                                                    </form>
                                                @endcan

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="portfolio-empty-icon w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                @if(!empty($search))
                                                    <p class="portfolio-empty-title text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Search_Results', 'No results found') }}
                                                    </p>
                                                    <p class="portfolio-secondary text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.Try_Different_Search', 'Try a different search term') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.portfolios.index') }}" class="btn btn-light btn-sm">
                                                        {{ t('dashboard.Clear_Search', 'Clear search') }}
                                                    </a>
                                                @else
                                                    <p class="portfolio-empty-title text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Portfolios', 'No portfolios yet') }}
                                                    </p>
                                                    <p class="portfolio-secondary text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.No_Portfolios_Desc', 'Add your first portfolio to showcase your work') }}
                                                    </p>
                                                    @can('create', 'App\\Models\\Portfolio')
                                                        <a href="{{ route('dashboard.portfolios.create') }}"
                                                           class="btn btn-primary btn-sm flex items-center gap-2">
                                                            <i class="ti ti-plus text-base"></i>
                                                            {{ t('dashboard.Add_Portfolio', 'Add Portfolio') }}
                                                        </a>
                                                    @endcan
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($portfolios->hasPages())
                        <div class="portfolio-pagination mt-4">
                            {{ $portfolios->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

@push('scripts')
    <script>
        document.addEventListener('submit', function (event) {
            const form = event.target.closest('.portfolio-delete-form[data-confirm]');
            if (!form) return;
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    </script>
@endpush
</x-dashboard-layout>
