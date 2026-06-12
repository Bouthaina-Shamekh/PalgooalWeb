<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Testimonials_List', 'Testimonials') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Testimonials_List', 'Testimonials') }}</h2>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('ok'))
        <div class="alert alert-success mb-4">{{ session('ok') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mb-4">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card table-card">

                {{-- Toolbar --}}
                <div class="card-header">
                    <form method="GET" action="{{ route('dashboard.testimonials.index') }}"
                          class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3">

                        {{-- Search --}}
                        <div class="relative flex-1 min-w-[200px]">
                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 pointer-events-none">
                                <i class="ti ti-search text-base"></i>
                            </span>
                            <input type="text" name="search"
                                   value="{{ $search ?? '' }}"
                                   placeholder="{{ t('dashboard.Search_Testimonials', 'Search by name or title…') }}"
                                   class="w-full border rounded-xl pr-9 pl-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                        </div>

                        {{-- Per page --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-sm text-gray-500 whitespace-nowrap">{{ t('dashboard.Per_Page', 'Per page') }}</span>
                            <select name="per_page" onchange="this.form.submit()"
                                    class="border rounded-xl px-2 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                                @foreach([10, 25, 50] as $n)
                                    <option value="{{ $n }}" {{ ($perPage ?? 10) == $n ? 'selected' : '' }}>{{ $n }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Search submit --}}
                        <button type="submit" class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                            <i class="ti ti-search text-base"></i>
                            {{ t('dashboard.Search', 'Search') }}
                        </button>

                        {{-- Clear --}}
                        @if(!empty($search))
                            <a href="{{ route('dashboard.testimonials.index') }}"
                               class="shrink-0 btn btn-light flex items-center gap-1 text-sm">
                                <i class="ti ti-x text-base"></i>
                                {{ t('dashboard.Clear_Search', 'Clear') }}
                            </a>
                        @endif

                        {{-- Add --}}
                        @can('create', 'App\\Models\\Testimonial')
                            <a href="{{ route('dashboard.testimonials.create') }}"
                               class="shrink-0 btn btn-primary flex items-center gap-2 whitespace-nowrap">
                                <i class="ti ti-plus text-base"></i>
                                {{ t('dashboard.Add_Testimonial', 'Add Testimonial') }}
                            </a>
                        @endcan

                    </form>
                </div>

                <div class="card-body pt-3">
                    <div class="table-responsive">
                        <table class="table table-hover w-full">
                            <thead>
                                <tr>
                                    <th class="text-right">#</th>
                                    <th class="text-right">{{ t('dashboard.Testimonial_Image', 'Image') }}</th>
                                    <th class="text-right">{{ t('dashboard.Testimonial_Name', 'Name') }}</th>
                                    <th class="text-right">{{ t('dashboard.Testimonial_Major', 'Title') }}</th>
                                    <th class="text-right">{{ t('dashboard.Testimonial_Stars', 'Stars') }}</th>
                                    <th class="text-right">{{ t('dashboard.Testimonial_Approval', 'Status') }}</th>
                                    <th class="text-right">{{ t('dashboard.Actions', 'Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($testimonials as $testimonial)
                                    @php
                                        $trans = $testimonial->translations->firstWhere('locale', app()->getLocale())
                                              ?? $testimonial->translations->first();
                                    @endphp
                                    <tr>
                                        {{-- رقم الصف --}}
                                        <td class="text-sm text-gray-500">
                                            {{ ($testimonials->firstItem() ?? 1) + $loop->index }}
                                        </td>

                                        {{-- الصورة --}}
                                        <td>
                                            @if ($testimonial->image?->url)
                                                <img src="{{ $testimonial->image->url }}"
                                                     class="w-10 h-10 rounded-full object-cover border border-gray-200"
                                                     alt="">
                                            @else
                                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 text-gray-300">
                                                    <i class="ti ti-user text-lg"></i>
                                                </span>
                                            @endif
                                        </td>

                                        {{-- الاسم --}}
                                        <td>
                                            <span class="text-sm font-medium text-gray-800">
                                                {{ $trans?->name ?? '—' }}
                                            </span>
                                        </td>

                                        {{-- المسمى الوظيفي --}}
                                        <td class="text-sm text-gray-500">
                                            {{ $trans?->major ?? '—' }}
                                        </td>

                                        {{-- النجوم --}}
                                        <td>
                                            @if ($testimonial->star)
                                                <span class="inline-flex items-center gap-0.5 text-amber-500">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <i class="ti ti-star{{ $i <= $testimonial->star ? '-filled' : '' }} text-sm"></i>
                                                    @endfor
                                                </span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>

                                        {{-- الحالة --}}
                                        <td>
                                            @if ($testimonial->is_approved)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                                    <i class="ti ti-circle-check text-sm ml-1"></i>
                                                    {{ t('dashboard.Testimonial_Approved', 'Approved') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">
                                                    <i class="ti ti-clock text-sm ml-1"></i>
                                                    {{ t('dashboard.Testimonial_Pending', 'Pending') }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- الإجراءات --}}
                                        <td class="whitespace-nowrap">
                                            <div class="flex items-center gap-0.5">

                                                @can('update', $testimonial)
                                                    <a href="{{ route('dashboard.testimonials.edit', $testimonial->id) }}"
                                                       title="{{ t('dashboard.Edit', 'Edit') }}"
                                                       class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary hover:bg-yellow-50 hover:text-yellow-600 transition-colors">
                                                        <i class="ti ti-edit text-base leading-none"></i>
                                                    </a>
                                                @endcan

                                                @can('delete', $testimonial)
                                                    <form method="POST"
                                                          action="{{ route('dashboard.testimonials.destroy', $testimonial->id) }}"
                                                          style="display:inline-block"
                                                          onsubmit="return confirm('{{ t('dashboard.Confirm_Delete_Testimonial', 'Are you sure you want to delete this testimonial?') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                title="{{ t('dashboard.Delete', 'Delete') }}"
                                                                class="w-8 h-8 rounded-xl inline-flex items-center justify-center btn-link-secondary hover:bg-red-50 hover:text-red-600 transition-colors">
                                                            <i class="ti ti-trash text-base leading-none"></i>
                                                        </button>
                                                    </form>
                                                @endcan

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="flex flex-col items-center justify-center py-16 text-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mb-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                                </svg>
                                                @if(!empty($search))
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Search_Results', 'No results found') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.Try_Different_Search', 'Try a different search term') }}
                                                    </p>
                                                    <a href="{{ route('dashboard.testimonials.index') }}" class="btn btn-light btn-sm">
                                                        {{ t('dashboard.Clear_Search', 'Clear search') }}
                                                    </a>
                                                @else
                                                    <p class="text-base font-semibold text-gray-700 mb-1">
                                                        {{ t('dashboard.No_Testimonials', 'No testimonials yet') }}
                                                    </p>
                                                    <p class="text-sm text-gray-400 mb-5">
                                                        {{ t('dashboard.No_Testimonials_Desc', 'Add your first testimonial to showcase client feedback') }}
                                                    </p>
                                                    @can('create', 'App\\Models\\Testimonial')
                                                        <a href="{{ route('dashboard.testimonials.create') }}"
                                                           class="btn btn-primary btn-sm flex items-center gap-2">
                                                            <i class="ti ti-plus text-base"></i>
                                                            {{ t('dashboard.Add_Testimonial', 'Add Testimonial') }}
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

                    @if ($testimonials->hasPages())
                        <div class="mt-4">
                            {{ $testimonials->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
