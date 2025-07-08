<div>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.languages.index') }}">{{ t('dashboard.All_Pages', 'ALL Pages') }}</a></li>
                {{-- <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.All_Pages', 'ALL Pages') }}</li> --}}
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.All_Pages', 'ALL Pages') }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->

    @if (session()->has('success'))
        <div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif
    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 mb-sm-0">{{ t('dashboard.All_Pages', 'All Pages') }}</h5>
                        <div>
                            <button wire:click="goToAddPage" class="btn btn-primary">{{ t('dashboard.Add_Page', 'Add Page') }}</button>
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
                                    <th>{{ t('dashboard.Homepage', 'Homepage') }}</th>
                                    <th>{{ t('dashboard.Status', 'Status') }}</th>
                                    <th>{{ t('dashboard.Date', 'Date') }}</th>
                                    <th>{{ t('dashboard.Action', 'Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pages as $p)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $p->translation()?->title ?: $p->slug }}</td>
                                    <td>
                                        @if ($p->is_home)
                                            <span class="btn btn-success">{{ t('dashboard.Current_Homepage', 'Current Homepage')}}</span>
                                            @else
                                                <button wire:click="setAsHome({{ $p->id }})" class="btn btn-primary">
                                                    {{ t('dashboard.Make_Homepage', 'Make Homepage') }}
                                                </button>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="px-2 py-1 rounded text-xs {{ $p->is_active ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900' }}">
                                            {{ $p->is_active ? 'منشور' : 'غير منشور' }}
                                        </span>
                                    </td>
                                    <td>{{ $p->created_at->translatedFormat('Y-m-d h:i A') }}</td>
                                    <td>
                                        <a href="{{ url($p->slug) }}" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary" target="_blank">
                                            <i class="ti ti-eye text-xl leading-none"></i>
                                        </a>
                                        <button wire:click="edit({{ $p->id }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-edit text-xl leading-none"></i>
                                        </button>
                                        <button onclick="confirmDeletePage({{ $p->id }})" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-trash text-xl"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
</div>
{{-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmDeletePage(pageId) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'لن تتمكن من التراجع بعد الحذف!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteConfirmed', { id: pageId });
            }
        });
    }

    window.addEventListener('page-deleted-success', () => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '✅ تم حذف الصفحة بنجاح',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });

    window.addEventListener('page-delete-failed', () => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: '❌ فشل حذف الصفحة',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });
</script> --}}
   @push('scripts')
   <script>
    function confirmDeletePage(pageId) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'لن تتمكن من التراجع بعد الحذف!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteConfirmed', { id: pageId });
            }
        });
    }

    window.addEventListener('page-deleted-success', () => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '✅ تم حذف الصفحة بنجاح',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });

    window.addEventListener('page-delete-failed', () => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: '❌ فشل حذف الصفحة',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });
</script>
    
   <script>
    function confirmDeleteSection(sectionId) {
        Swal.fire({
            title: 'هل أنت متأكد؟',
            text: 'لن تتمكن من التراجع بعد الحذف!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6'
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteSection', { id: sectionId });
            }
        });
    }

    window.addEventListener('section-deleted-success', () => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: '✅ تم حذف السكشن بنجاح',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });

    window.addEventListener('section-delete-failed', () => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: '❌ فشل حذف السكشن',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    });
</script>

@endpush

