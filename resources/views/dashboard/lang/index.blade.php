<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Languages</a></li>
                <li class="breadcrumb-item" aria-current="page">Languages List</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Languages List</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">
                <div class="card-header">
                    <div class="sm:flex items-center justify-between">
                        <h5 class="mb-3 sm:mb-0">Languages List</h5>
                        <div>
                            <a href="{{ route('dashboard.languages.create') }}" class="btn btn-primary">Add Languages</a>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between mb-4 px-5 py-4">
                    {{-- <x-form.input type="text" wire:model="search" wire:input="updateSearch" placeholder="Search clients..." /> --}}
                    {{-- <x-form.select
                        wire:model="perPage"
                        wire:change="updatePerPage"
                        name="perPage"
                        :options="[
                           '5' => '5 per page',
                           '10' => '10 per page',
                           '25' => '25 per page',
                        ]"
                        /> --}}
                </div>
                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Native Name</th>
                                    <th>Code</th>
                                    <th>Flag</th>
                                    <th>RTL</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($languages as $lang)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $lang->name }}</td>
                                    <td>{{ $lang->native }}</td>
                                    <td>{{ strtoupper($lang->code) }}</td>
                                    <td>
                                        @if($lang->flag)
                                            <img src="{{ asset($lang->flag) }}" alt="flag" class="w-6 h-4 inline-block">
                                        @endif
                                    </td>
                                    <td>
                                        <div class="form-check form-switch switch-lg">
                                            <input type="checkbox" {{ $lang->is_rtl ? 'checked' : '' }} class="form-check-input checked:!bg-success-500 checked:!border-success-500 text-lg" onclick="toggleRtl({{ $lang->id }}, this.checked)" >
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch switch-lg">
                                            <input type="checkbox" {{ $lang->is_active ? 'checked' : '' }} class="form-check-input checked:!bg-success-500 checked:!border-success-500 text-lg" onclick="toggleStatus({{ $lang->id }}, this.checked)" >
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('dashboard.translation-values.index', ['locale' => $lang->code]) }}" class="btn btn-sm btn-info">
                                            تعديل الترجمات لهذه اللغة
                                        </a>
                                        <a href="{{ route('dashboard.languages.edit', $lang->id) }}" class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-edit text-xl leading-none"></i>
                                        </a>
                                        <a href="#" onclick="deleteLanguage({{ $lang->id }})"  class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary">
                                            <i class="ti ti-trash text-xl leading-none"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">
                    {{ $languages->links() }}
                </div>
            </div>
        </div>
    </div>

<!-- ✅ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function toggleRtl(langId, isChecked) {
        fetch('admin/languages/' + langId + '/toggle-rtl', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                is_rtl: isChecked ? 1 : 0
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                showSuccessToast('✅ RTL updated successfully');
            } else {
                showErrorToast('❌ Error updating RTL');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorToast('❌ Error occurred');
        });
    }

    function toggleStatus(langId, isChecked) {
        fetch('admin/languages/' + langId + '/toggle-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                is_active: isChecked ? 1 : 0
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success){
                showSuccessToast('✅ Status updated successfully');
            } else {
                showErrorToast('❌ Error updating Status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorToast('❌ Error occurred');
        });
    }

    // ✅ Toast functions:
    function showSuccessToast(message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: message,
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true
        });
    }

    function showErrorToast(message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: message,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
        });
    }
</script>
<script>
    function deleteLanguage(langId) {
        if (confirm('Are you sure you want to delete this language?')) {
            fetch('admin/languages/' + langId + '/delete', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                if(data.success){
                    showSuccessToast('✅ Language deleted successfully');
                    // Optionally, reload the page:
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showErrorToast('❌ Error deleting language');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorToast('❌ Error occurred');
            });
        }
    }
</script>


</x-dashboard-layout>    