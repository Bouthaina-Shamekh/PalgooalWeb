<x-dashboard-layout>
    @php
        /**
         * ------------------------------------------------------------------
         * Expected data:
         *  - $page     : \App\Models\Page (marketing page)
         *  - $sections : \Illuminate\Support\Collection|\App\Models\Section[]
         * ------------------------------------------------------------------
         */

        // Resolve page title for breadcrumb and header
        $pageTranslation = method_exists($page, 'translation')
            ? $page->translation()
            : null;

        $pageTitle = $pageTranslation?->title
            ?? $page->slug
            ?? ('#' . $page->id);
    @endphp

    {{-- [ breadcrumb ] start --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">
                        {{ t('dashboard.Home', 'Home') }}
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.pages.index') }}">
                        {{ t('dashboard.All_Pages', 'All Pages') }}
                    </a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                    {{ t('dashboard.Page_Sections', 'Page Sections') }} – {{ $pageTitle }}
                </li>
            </ul>

            <div class="page-header-title">
                <h2 class="mb-0">
                    {{ t('dashboard.Page_Sections', 'Page Sections') }}
                    <small class="text-sm text-muted d-block">
                        {{ $pageTitle }}
                    </small>
                </h2>
            </div>
        </div>
    </div>
    {{-- [ breadcrumb ] end --}}

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <div>
                            <h5 class="mb-1 mb-sm-0">
                                {{ t('dashboard.Page_Sections', 'Page Sections') }}
                            </h5>
                            <p class="text-xs text-slate-500 mt-1">
                                {{ __('Manage the content blocks (sections) that build this marketing page.') }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            {{-- Back to pages list --}}
                            <a href="{{ route('dashboard.pages.index') }}" class="btn btn-link-secondary">
                                {{ t('dashboard.Back_to_Pages', 'Back to Pages') }}
                            </a>

                            {{-- Add new section --}}
                            <a href="{{ route('dashboard.pages.sections.create', $page) }}"
                               class="btn btn-primary">
                                {{ t('dashboard.Add_Section', 'Add Section') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover" id="pc-dt-sections">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ t('dashboard.Type', 'Type') }}</th>
                                    <th>{{ t('dashboard.Variant', 'Variant') }}</th>
                                    <th>{{ t('dashboard.Title', 'Title') }}</th>
                                    <th>{{ t('dashboard.Locales', 'Locales') }}</th>
                                    <th>{{ t('dashboard.Order', 'Order') }}</th>
                                    <th>{{ t('dashboard.Status', 'Status') }}</th>
                                    <th>{{ t('dashboard.Action', 'Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sections as $section)
                                    @php
                                        /** @var \App\Models\Section $section */

                                        // Resolve a single translation for current app locale
                                        $translation = method_exists($section, 'translation')
                                            ? $section->translation()
                                            : null;

                                        // Title fallback: translation title OR type label
                                        $sectionTitle = $translation?->title
                                            ?? ucfirst(str_replace('_', ' ', $section->type));

                                        // Comma separated list of locales (e.g. "ar, en")
                                        $locales = $section->translations
                                            ? $section->translations->pluck('locale')->implode(', ')
                                            : '';
                                    @endphp

                                    <tr>
                                        {{-- Row index (1-based) --}}
                                        <td>{{ $loop->iteration }}</td>

                                        {{-- Type --}}
                                        <td>
                                            <span class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-800">
                                                {{ $section->type }}
                                            </span>
                                        </td>

                                        {{-- Variant --}}
                                        <td>
                                            @if ($section->variant)
                                                <span class="text-xs px-2 py-1 rounded bg-purple-100 text-purple-800">
                                                    {{ $section->variant }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>

                                        {{-- Title (from translation) --}}
                                        <td>
                                            {{ $sectionTitle }}
                                        </td>

                                        {{-- Locales --}}
                                        <td>
                                            @if ($locales)
                                                <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-800">
                                                    {{ $locales }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>

                                        {{-- Order --}}
                                        <td>
                                            <span class="text-xs px-2 py-1 rounded bg-slate-50 text-slate-700">
                                                {{ $section->order ?? '—' }}
                                            </span>
                                        </td>

                                        {{-- Status --}}
                                        <td>
                                            @if ($section->is_active)
                                                <span class="px-2 py-1 rounded text-xs bg-green-200 text-green-900">
                                                    {{ t('dashboard.Active', 'Active') }}
                                                </span>
                                            @else
                                                <span class="px-2 py-1 rounded text-xs bg-red-200 text-red-900">
                                                    {{ t('dashboard.Inactive', 'Inactive') }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- Actions --}}
                                        <td class="space-x-1 rtl:space-x-reverse">
                                            {{-- Edit --}}
                                            <a href="{{ route('dashboard.pages.sections.edit', [$page, $section]) }}"
                                               class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                               title="{{ t('dashboard.Edit', 'Edit') }}">
                                                <i class="ti ti-edit text-xl leading-none"></i>
                                            </a>

                                            {{-- Delete --}}
                                            <form action="{{ route('dashboard.pages.sections.destroy', [$page, $section]) }}"
                                                  method="POST"
                                                  class="inline"
                                                  onsubmit="return confirm('{{ __('Are you sure you want to delete this section? This action cannot be undone.') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                        title="{{ t('dashboard.Delete', 'Delete') }}">
                                                    <i class="ti ti-trash text-xl"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-slate-500 py-4">
                                            {{ __('No sections have been added for this page yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Optional: we are not paginating here (all sections for one page).
                         If you later add pagination, you can render links here. --}}
                    {{-- <div class="mt-4">
                        {{ $sections->links() }}
                    </div> --}}
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
