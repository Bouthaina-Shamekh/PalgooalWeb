{{-- resources/views/dashboard/pages/index.blade.php --}}
<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                    {{ t('dashboard.All_Pages', 'All Pages') }}
                </li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.All_Pages', 'All Pages') }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->

    @if (session('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 mb-sm-0">
                            {{ t('dashboard.All_Pages', 'All Pages') }}
                        </h5>
                        <div>
                            @can('create', \App\Models\Page::class)
                            <a href="{{ route('dashboard.pages.create') }}" class="btn btn-primary">
                                {{ t('dashboard.Add_Page', 'Add Page') }}
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover" id="pc-dt-simple">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ t('dashboard.Title', 'Title') }}</th>
                                    <th>{{ t('dashboard.Slug', 'Slug') }}</th>
                                    <th>{{ t('dashboard.Homepage', 'Homepage') }}</th>
                                    <th>{{ t('dashboard.Status', 'Status') }}</th>
                                    <th>{{ t('dashboard.Date', 'Date') }}</th>
                                    <th>{{ t('dashboard.Action', 'Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pages as $p)
                                    @php
                                        $translation = $p->translation();
                                        $title = $translation?->title ?? ($p->slug ?? '#' . $p->id);
                                        $slug = $translation?->slug ?? ($p->slug ?? '');
                                        $frontUrl = $p->is_home ? url('/') : ($slug ? url($slug) : url('/'));
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>

                                        <td>{{ $title }}</td>

                                        <td>
                                            @if ($p->is_home)
                                                <span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-800">
                                                    /
                                                </span>
                                            @elseif ($slug)
                                                <span class="text-xs px-2 py-1 rounded bg-slate-100 text-slate-800">
                                                    /{{ $slug }}
                                                </span>
                                            @else
                                                <span class="text-xs text-slate-400">—</span>
                                            @endif
                                        </td>

                                        {{-- Homepage --}}
                                        <td>
                                            @if ($p->is_home)
                                                <span class="btn btn-success btn-sm">
                                                    {{ t('dashboard.Current_Homepage', 'Current Homepage') }}
                                                </span>
                                            @else
                                                @can('setHome', $p)
                                                <form action="{{ route('dashboard.pages.set-home', $p) }}"
                                                    method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        {{ t('dashboard.Make_Homepage', 'Make Homepage') }}
                                                    </button>
                                                </form>
                                                @endcan
                                            @endif
                                        </td>

                                        {{-- Status (toggle) --}}
                                        <td>
                                            @can('toggleActive', $p)
                                            <form action="{{ route('dashboard.pages.toggle-active', $p) }}"
                                                method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="px-2 py-1 rounded text-xs
                                                        {{ $p->is_active ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900' }}">
                                                    {{ $p->is_active ? t('dashboard.Published', 'Published') : t('dashboard.Draft', 'Draft') }}
                                                </button>
                                            </form>
                                            @else
                                                <span class="px-2 py-1 rounded text-xs {{ $p->is_active ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900' }}">
                                                    {{ $p->is_active ? t('dashboard.Published', 'Published') : t('dashboard.Draft', 'Draft') }}
                                                </span>
                                            @endcan
                                        </td>

                                        {{-- Date --}}
                                        <td>
                                            {{ optional($p->created_at)->translatedFormat('Y-m-d h:i A') }}
                                        </td>

                                        {{-- Actions --}}
                                        <td class="space-x-1 rtl:space-x-reverse">
                                            {{-- View --}}
                                            <a href="{{ $frontUrl }}"
                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                target="_blank">
                                                <i class="ti ti-eye text-xl leading-none"></i>
                                            </a>

                                            {{-- Edit --}}
                                            @can('update', $p)
                                            <a href="{{ route('dashboard.pages.edit', $p) }}"
                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                                <i class="ti ti-edit text-xl leading-none"></i>
                                            </a>
                                            @endcan

                                            {{-- Delete (SweetAlert confirm) --}}
                                            @can('delete', $p)
                                            <button type="button"
                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary text-danger"
                                                onclick="confirmDeletePage({{ $p->id }})">
                                                <i class="ti ti-trash text-xl"></i>
                                            </button>

                                            {{-- Hidden delete form --}}
                                            <form id="delete-page-{{ $p->id }}"
                                                action="{{ route('dashboard.pages.destroy', $p) }}" method="POST"
                                                class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            @endcan

                                            {{-- Sections Builder --}}
                                            <a href="{{ route('dashboard.pages.sections.index', $p) }}"
                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                title="{{ t('dashboard.Sections_Builder', 'Sections Builder') }}">
                                                <i class="ti ti-layout-list text-xl leading-none"></i>
                                            </a>

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-slate-500 py-4">
                                            {{ t('dashboard.No_Pages_Yet', 'No pages yet.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $pages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>


<script>
    /**
     * Confirm page deletion using SweetAlert.
     *
     * - Called directly from the delete button onclick (user interaction),
     *   so browser will not suppress the dialog.
     * - If SweetAlert (Swal) is not loaded, falls back to window.confirm().
     */
    function confirmDeletePage(pageId) {
        // Fallback if SweetAlert is not available
        if (typeof Swal === 'undefined') {
            const ok = window.confirm(
                '{{ t('dashboard.Confirm_Delete_Page_Text', 'Are you sure you want to delete this page? This action cannot be undone.') }}'
            );
            if (ok) {
                const form = document.getElementById('delete-page-' + pageId);
                if (form) form.submit();
            }
            return;
        }

        Swal.fire({
            title: '{{ t('dashboard.Confirm_Delete_Page_Title', 'Are you sure you want to delete this page?') }}',
            text: '{{ t('dashboard.Action_Cannot_Be_Undone', 'This action cannot be undone!') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ t('dashboard.Yes_Delete_Page', 'Yes, delete the page') }}',
            cancelButtonText: '{{ t('common.Cancel', 'Cancel') }}',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('delete-page-' + pageId);
                if (form) {
                    form.submit();
                }
            }
        });
    }
</script>
