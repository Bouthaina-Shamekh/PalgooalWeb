<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Languages</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_New_Translation') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_New_Translation') }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
        <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ t('dashboard.Edit_translation') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('dashboard.translation-values.store') }}" method="POST" class="grid grid-cols-12 gap-x-6">
                        @csrf
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <x-form.input
                                    label="{{ t('dashboard.Key') }}"
                                    name="key"
                                    placeholder="Taype Name"
                                />
                                {{-- @error('domain_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror --}}
                            </div>
                        </div>
                        @foreach($languages as $lang)
                        <div class="col-span-12 md:col-span-6">
                            <div class="mb-3">
                                <x-form.input
                                    label="{{ $lang->native }} ({{ $lang->code }})"
                                    name="values[{{ $lang->code }}]"
                                    placeholder="Taype Name"
                                />
                                {{-- @error('domain_name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror --}}
                            </div>
                        </div>
                        @endforeach

                        <div class="col-span-12 text-right">
                            <button type="button" wire:click="showIndex" class="btn btn-secondary">{{ t('dashboard.Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ t('dashboard.Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->




</x-dashboard-layout>
