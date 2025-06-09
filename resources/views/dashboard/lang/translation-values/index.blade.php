<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Languages</a></li>
                <li class="breadcrumb-item" aria-current="page">Translation Values</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.translation_values') }}</h2>
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
                        <h5 class="mb-3 sm:mb-0">{{ t('dashboard.translation_values') }}</h5>
                        <div>
                            <a href="{{ route('dashboard.translation-values.create') }}" class="btn btn-primary">{{ t('dashboard.Add_New_Translation') }}</a>
                        </div>
                    </div>
                </div>
                @if(session('success'))
                <div class="alert alert-success mb-4">{{ session('success') }}</div>
                @endif
                <!-- Language Filter -->
                <div class="flex items-center justify-between mb-4 px-5 py-4">
  <form method="GET" action="{{ route('dashboard.translation-values.index') }}" class="flex items-center gap-3 flex-wrap">

    <!-- Language Filter -->
    <select name="locale" onchange="this.form.submit()" class="w-48 border px-2 py-1 rounded">
        <option value="">-- All Languages --</option>
        @foreach($languages as $lang)
            <option value="{{ $lang->code }}" {{ $localeFilter == $lang->code ? 'selected' : '' }}>
                {{ $lang->native }} ({{ $lang->code }})
            </option>
        @endforeach
    </select>

    <!-- Type Filter -->
    <select name="type" onchange="this.form.submit()" class="w-48 border px-2 py-1 rounded">
        <option value="">-- All Types --</option>
        <option value="dashboard" {{ $typeFilter == 'dashboard' ? 'selected' : '' }}>Dashboard</option>
        <option value="frontend" {{ $typeFilter == 'frontend' ? 'selected' : '' }}>Frontend</option>
        <option value="general" {{ $typeFilter == 'general' ? 'selected' : '' }}>General</option>
    </select>

    <!-- Search -->
    <input type="text" name="search" value="{{ $search }}" placeholder="Search keys..." class="border px-2 py-1 rounded w-64">

    <button type="submit" class="btn btn-primary">Search</button>

    <a href="{{ route('dashboard.translation-values.index') }}" class="btn btn-secondary">Reset</a>

</form>

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
                                    <th>Key</th>
                                    <th>Type</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($translations as $key => $items)
                                @php
                                $type = Str::startsWith($key, 'dashboard.') ? 'Dashboard' :
                                (Str::startsWith($key, 'frontend.') ? 'Frontend' : 'General');
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $key }}</td>
                                    <td>{{ $type }}</td>
                                    <td>
                                        <a href="{{ route('dashboard.translation-values.edit', ['key' => $key]) }}" class="btn btn-sm btn-primary">
                                            {{ t('dashboard.edit_translation') }}
                                        </a>
                                        <form action="{{ route('dashboard.translation-values.destroy', ['key' => $key]) }}" method="POST" class="inline-block" onsubmit="return confirm('{{ t('dashboard.confirm_delete') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">{{ t('dashboard.delete') }}</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="mt-4">
                    {{-- {{ $languages->links() }} --}}
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->

</x-dashboard-layout>
