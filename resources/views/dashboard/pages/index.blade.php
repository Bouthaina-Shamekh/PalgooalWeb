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
                            <a href="{{ route('dashboard.pages.create') }}" class="btn btn-primary">
                                {{ t('dashboard.Add_Page', 'Add Page') }}
                            </a>
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
                                                <form action="{{ route('dashboard.pages.set-home', $p) }}"
                                                    method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        {{ t('dashboard.Make_Homepage', 'Make Homepage') }}
                                                    </button>
                                                </form>
                                            @endif
                                        </td>

                                        {{-- Status (toggle) --}}
                                        <td>
                                            <form action="{{ route('dashboard.pages.toggle-active', $p) }}"
                                                method="POST" class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="px-2 py-1 rounded text-xs
                                                        {{ $p->is_active ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900' }}">
                                                    {{ $p->is_active ? __('منشورة') : __('مسودة') }}
                                                </button>
                                            </form>
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
                                            <a href="{{ route('dashboard.pages.edit', $p) }}"
                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                                <i class="ti ti-edit text-xl leading-none"></i>
                                            </a>

                                            {{-- Delete (SweetAlert confirm) --}}
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
                                            {{-- Page Builder --}}
                                            <a href="{{ route('dashboard.pages.builder', $p) }}"
                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                title="{{ __('Page Builder') }}">
                                                <i class="ti ti-vector text-xl leading-none"></i>
                                            </a>

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-slate-500 py-4">
                                            {{ __('لا توجد صفحات حتى الآن.') }}
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
                '{{ __('هل أنت متأكد من حذف الصفحة؟ لن تتمكن من التراجع عن هذا الإجراء.') }}'
            );
            if (ok) {
                const form = document.getElementById('delete-page-' + pageId);
                if (form) form.submit();
            }
            return;
        }

        Swal.fire({
            title: '{{ __('هل أنت متأكد من حذف هذه الصفحة؟') }}',
            text: '{{ __('لن تتمكن من التراجع عن هذا الإجراء!') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('نعم، احذف الصفحة') }}',
            cancelButtonText: '{{ __('إلغاء') }}',
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
