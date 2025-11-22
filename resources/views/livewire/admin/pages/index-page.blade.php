<div>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item"><a
                        href="{{ route('dashboard.languages.index') }}">{{ t('dashboard.All_Pages', 'ALL Pages') }}</a>
                </li>
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
                        @can('create', 'App\\Models\\Page')
                            <div>
                                <button wire:click="goToAddPage"
                                    class="btn btn-primary">{{ t('dashboard.Add_Page', 'Add Page') }}</button>
                            </div>
                        @endcan
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
                                                <span
                                                    class="btn btn-success">{{ t('dashboard.Current_Homepage', 'Current Homepage') }}</span>
                                            @else
                                                <button wire:click="setAsHome({{ $p->id }})"
                                                    class="btn btn-primary">
                                                    {{ t('dashboard.Make_Homepage', 'Make Homepage') }}
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            <span
                                                class="px-2 py-1 rounded text-xs {{ $p->is_active ? 'bg-green-200 text-green-900' : 'bg-red-200 text-red-900' }}">
                                                {{ $p->is_active ? __("\u{0645}\u{0646}\u{0634}\u{0648}\u{0631}\u{0629}") : __("\u{0645}\u{0633}\u{0648}\u{062F}\u{0629}") }}
                                            </span>
                                        </td>
                                        <td>{{ $p->created_at->translatedFormat('Y-m-d h:i A') }}</td>
                                        <td>
                                            @can('view', 'App\\Models\\Page')
                                                <a href="{{ url($p->slug ?: '/') }}"
                                                    class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary"
                                                    target="_blank">
                                                    <i class="ti ti-eye text-xl leading-none"></i>
                                                </a>
                                            @endcan
                                            @can('edit', 'App\\Models\\Page')
                                                <button wire:click="edit({{ $p->id }})"
                                                    class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                                    <i class="ti ti-edit text-xl leading-none"></i>
                                                </button>
                                            @endcan
                                            @can('delete', 'App\\Models\\Page')
                                                <button onclick="confirmDeletePage({{ $p->id }})"
                                                    class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                                    <i class="ti ti-trash text-xl"></i>
                                                </button>
                                            @endcan
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

@push('scripts')
    <script>
        function confirmDeletePage(pageId) {
            Swal.fire({
                title: '{{ __("\u{0647}\u{0644}\u{0020}\u{0623}\u{0646}\u{062A}\u{0020}\u{0645}\u{062A}\u{0623}\u{0643}\u{062F}\u{0020}\u{0645}\u{0646}\u{0020}\u{062D}\u{0630}\u{0641}\u{0020}\u{0627}\u{0644}\u{0635}\u{0641}\u{062D}\u{0629}\u{061F}") }}',
                text: '{{ __("\u{0644}\u{0646}\u{0020}\u{062A}\u{062A}\u{0645}\u{0643}\u{0646}\u{0020}\u{0645}\u{0646}\u{0020}\u{0627}\u{0644}\u{062A}\u{0631}\u{0627}\u{062C}\u{0639}\u{0020}\u{0639}\u{0646}\u{0020}\u{0647}\u{0630}\u{0627}\u{0020}\u{0627}\u{0644}\u{0625}\u{062C}\u{0631}\u{0627}\u{0621}\u{0021}") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __("\u{0646}\u{0639}\u{0645}\u{060C}\u{0020}\u{0627}\u{062D}\u{0630}\u{0641}\u{0020}\u{0627}\u{0644}\u{0635}\u{0641}\u{062D}\u{0629}") }}',
                cancelButtonText: '{{ __("\u{0625}\u{0644}\u{063A}\u{0627}\u{0621}") }}',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('deleteConfirmed', {
                        id: pageId
                    });
                }
            });
        }

        window.addEventListener('page-deleted-success', () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '{{ __("\u{062A}\u{0645}\u{0020}\u{062D}\u{0630}\u{0641}\u{0020}\u{0627}\u{0644}\u{0635}\u{0641}\u{062D}\u{0629}\u{0020}\u{0628}\u{0646}\u{062C}\u{0627}\u{062D}\u{002E}") }}',
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
                title: '{{ __("\u{062A}\u{0639}\u{0630}\u{0631}\u{0020}\u{062D}\u{0630}\u{0641}\u{0020}\u{0627}\u{0644}\u{0635}\u{0641}\u{062D}\u{0629}\u{002E}") }}',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        });
    </script>

    <script>
        function confirmDeleteSection(sectionId) {
            Swal.fire({
                title: '{{ __("\u{0647}\u{0644}\u{0020}\u{0623}\u{0646}\u{062A}\u{0020}\u{0645}\u{062A}\u{0623}\u{0643}\u{062F}\u{0020}\u{0645}\u{0646}\u{0020}\u{062D}\u{0630}\u{0641}\u{0020}\u{0647}\u{0630}\u{0627}\u{0020}\u{0627}\u{0644}\u{0642}\u{0633}\u{0645}\u{061F}") }}',
                text: '{{ __("\u{0644}\u{0646}\u{0020}\u{062A}\u{062A}\u{0645}\u{0643}\u{0646}\u{0020}\u{0645}\u{0646}\u{0020}\u{0627}\u{0644}\u{062A}\u{0631}\u{0627}\u{062C}\u{0639}\u{0020}\u{0639}\u{0646}\u{0020}\u{0647}\u{0630}\u{0627}\u{0020}\u{0627}\u{0644}\u{0625}\u{062C}\u{0631}\u{0627}\u{0621}\u{0021}") }}',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __("\u{0646}\u{0639}\u{0645}\u{060C}\u{0020}\u{0627}\u{062D}\u{0630}\u{0641}\u{0020}\u{0627}\u{0644}\u{0642}\u{0633}\u{0645}") }}',
                cancelButtonText: '{{ __("\u{0625}\u{0644}\u{063A}\u{0627}\u{0621}") }}',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('deleteSection', {
                        id: sectionId
                    });
                }
            });
        }

        window.addEventListener('section-deleted-success', () => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '{{ __("\u{062A}\u{0645}\u{0020}\u{062D}\u{0630}\u{0641}\u{0020}\u{0627}\u{0644}\u{0642}\u{0633}\u{0645}\u{0020}\u{0628}\u{0646}\u{062C}\u{0627}\u{062D}\u{002E}") }}',
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
                title: '{{ __("\u{062A}\u{0639}\u{0630}\u{0631}\u{0020}\u{062D}\u{0630}\u{0641}\u{0020}\u{0627}\u{0644}\u{0642}\u{0633}\u{0645}\u{002E}") }}',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true
            });
        });
    </script>
@endpush
