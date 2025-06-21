<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'translation_values') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.languages.index') }}">{{ t('dashboard.Languages', 'Languages') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Edit_translation', 'Edit translation') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Edit_translation', 'Edit translation') }} - {{ $key }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ t('dashboard.Edit_translation', 'Edit translation') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('dashboard.translation-values.update', ['key' => $key]) }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-12 gap-x-6">
                        @csrf
                        @foreach($languages as $lang)
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <x-form.input
                                    label="{{ $lang->native }} ({{ $lang->code }})"
                                    name="values[{{ $lang->code }}]"
                                    type="text"
                                    value="{{ $translations[$lang->code]->value ?? '' }}"
                                    placeholder="{{ t('dashboard.Taype_Name', 'Taype Name') }}"
                                />
                                {{-- @error('domain_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror --}}
                            </div>
                        </div>
                        @endforeach

                        <div class="col-span-12 text-right">
                            <button type="button" wire:click="showIndex" class="btn btn-secondary">{{ t('dashboard.Cancel', 'Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ t('dashboard.Save', 'Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
</x-dashboard-layout>
