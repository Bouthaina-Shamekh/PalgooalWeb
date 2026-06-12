<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Services_List', 'Services') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Services_List', 'Services') }}</h2>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session('ok'))
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card table-card">
        <div class="card-header">
            <form method="GET" action="{{ route('dashboard.services.index') }}"
                  class="flex flex-wrap items-center gap-3">

                {{-- Search --}}
                <div class="relative flex-1" style="min-width:200px;max-width:320px;">
                    <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                        <i class="ti ti-search text-base"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search }}"
                           class="form-control pe-9"
                           placeholder="{{ t('dashboard.Search_Services', 'Search services…') }}">
                </div>

                {{-- Per page --}}
                <div class="flex items-center gap-2 shrink-0">
                    <span class="text-sm text-gray-500 whitespace-nowrap">{{ t('dashboard.Per_Page', 'Per page') }}:</span>
                    <select name="per_page" class="form-select form-select-sm w-auto"
                            onchange="this.form.submit()">
                        @foreach ([10, 25, 50] as $n)
                            <option value="{{ $n }}" {{ $perPage == $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Clear --}}
                @if ($search)
                    <a href="{{ route('dashboard.services.index') }}" class="shrink-0 btn btn-light btn-sm flex items-center gap-1">
                        <i class="ti ti-x"></i>
                        {{ t('dashboard.Clear_Search', 'Clear') }}
                    </a>
                @endif

                {{-- Add button (inside form as <a>, does not submit) --}}
                @can('create', 'App\\Models\\Service')
                    <a href="{{ route('dashboard.services.create') }}" class="shrink-0 btn btn-primary flex items-center gap-1 whitespace-nowrap ms-auto">
                        <i class="ti ti-plus"></i>
                        {{ t('dashboard.Add_Service', 'Add Service') }}
                    </a>
                @endcan

            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>{{ t('dashboard.Service_Icon', 'Icon') }}</th>
                            <th>{{ t('dashboard.Service_Title', 'Title') }}</th>
                            <th>{{ t('dashboard.Service_Order', 'Order') }}</th>
                            <th>{{ t('dashboard.Actions', 'Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $service)
                            @php
                                $trans = $service->translations->firstWhere('locale', app()->getLocale())
                                       ?? $service->translations->first();
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @if ($service->icon)
                                        <img src="{{ asset('storage/' . ltrim($service->icon, '/')) }}"
                                             alt="{{ $trans?->title ?? '' }}"
                                             class="rounded-lg border"
                                             style="width:40px;height:40px;object-fit:cover;">
                                    @else
                                        <span class="text-muted">
                                            <i class="ti ti-image-off" style="font-size:1.5rem;"></i>
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $trans?->title ?? '—' }}</td>
                                <td>{{ $service->order }}</td>
                                <td>
                                    <div class="flex items-center gap-2 flex-nowrap">
                                        @can('update', $service)
                                            <a href="{{ route('dashboard.services.edit', $service->id) }}"
                                               class="text-warning p-1"
                                               title="{{ t('dashboard.Edit', 'Edit') }}">
                                                <i class="ti ti-edit" style="font-size:1.2rem;"></i>
                                            </a>
                                        @endcan
                                        @can('delete', $service)
                                            <form action="{{ route('dashboard.services.destroy', $service->id) }}"
                                                  method="POST" class="d-inline"
                                                  onsubmit="return confirm('{{ t('dashboard.Confirm_Delete_Service', 'Delete this service?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-danger p-1 border-0 bg-transparent"
                                                        title="{{ t('dashboard.Delete', 'Delete') }}">
                                                    <i class="ti ti-trash" style="font-size:1.2rem;"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-5 text-center">
                                    <div class="d-flex flex-column align-items-center gap-3 py-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width:64px;height:64px;"
                                             class="text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        @if ($search)
                                            <p class="text-muted mb-0 fs-5">
                                                {{ t('dashboard.No_Search_Results', 'No results found') }}
                                            </p>
                                            <a href="{{ route('dashboard.services.index') }}"
                                               class="btn btn-outline-secondary btn-sm">
                                                {{ t('dashboard.Clear_Search', 'Clear search') }}
                                            </a>
                                        @else
                                            <p class="text-muted mb-0 fs-5">
                                                {{ t('dashboard.No_Services', 'No services yet') }}
                                            </p>
                                            <p class="text-muted small mb-0">
                                                {{ t('dashboard.No_Services_Desc', 'Start by adding your first service.') }}
                                            </p>
                                            @can('create', 'App\\Models\\Service')
                                                <a href="{{ route('dashboard.services.create') }}"
                                                   class="btn btn-primary btn-sm mt-1">
                                                    <i class="ti ti-plus me-1"></i>
                                                    {{ t('dashboard.Add_Service', 'Add Service') }}
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
        </div>

        @if ($services->hasPages())
            <div class="card-footer">
                {{ $services->links() }}
            </div>
        @endif
    </div>
</x-dashboard-layout>
