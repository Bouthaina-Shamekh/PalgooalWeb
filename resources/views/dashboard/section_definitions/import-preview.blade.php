<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">{{ __('Home') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.section_definitions.index') }}">{{ __('Section Definitions') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">{{ __('Import Preview') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ __('Import Preview') }}</h2>
            </div>
        </div>
    </div>

    @php
        $summary = $preview['summary'] ?? [];
        $items = collect($preview['items'] ?? []);
        $hasInvalid = (int) ($summary['invalid'] ?? 0) > 0;
    @endphp

    <div class="mb-4 grid grid-cols-12 gap-4">
        <div class="col-span-6 md:col-span-3">
            <div class="rounded bg-white p-4 shadow-sm">
                <div class="text-xs text-slate-500">{{ __('New') }}</div>
                <div class="text-2xl font-bold text-green-700">{{ $summary['new'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-span-6 md:col-span-3">
            <div class="rounded bg-white p-4 shadow-sm">
                <div class="text-xs text-slate-500">{{ __('Existing') }}</div>
                <div class="text-2xl font-bold text-blue-700">{{ $summary['updates'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-span-6 md:col-span-3">
            <div class="rounded bg-white p-4 shadow-sm">
                <div class="text-xs text-slate-500">{{ __('Invalid') }}</div>
                <div class="text-2xl font-bold text-red-700">{{ $summary['invalid'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-span-6 md:col-span-3">
            <div class="rounded bg-white p-4 shadow-sm">
                <div class="text-xs text-slate-500">{{ __('Total') }}</div>
                <div class="text-2xl font-bold text-slate-800">{{ $summary['total'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    @if ($hasInvalid)
        <div class="mb-4 rounded bg-yellow-100 px-4 py-3 text-yellow-900">
            {{ __('Invalid definitions will not be saved. Review the errors below before applying the import.') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="sm:flex items-center justify-between gap-4">
                <div>
                    <h5 class="mb-1">{{ __('Definitions') }}</h5>
                    <p class="mb-0 text-sm text-slate-500">
                        {{ __('Preview only. No database changes have been made yet.') }}
                    </p>
                </div>
                <form method="POST" action="{{ route('dashboard.section_definitions.import.apply') }}" class="mt-3 flex flex-wrap items-end gap-3 sm:mt-0">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">
                    <div>
                        <label for="strategy" class="form-label">{{ __('Apply Strategy') }}</label>
                        <select id="strategy" name="strategy" class="form-control">
                            @foreach ($strategies as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" @disabled(($summary['total'] ?? 0) <= ($summary['invalid'] ?? 0))>
                        {{ __('Apply Import') }}
                    </button>
                    <a href="{{ route('dashboard.section_definitions.import') }}" class="btn btn-light">
                        {{ __('Upload Another File') }}
                    </a>
                </form>
            </div>
        </div>

        <div class="card-body pt-3">
            <div class="table-responsive">
                <table class="table table-hover w-full">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('Definition') }}</th>
                            <th>{{ __('Template Key') }}</th>
                            <th>{{ __('Fields') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>{{ ((int) $item['index']) + 1 }}</td>
                                <td>
                                    <div class="font-medium text-slate-900">{{ $item['label'] ?: '-' }}</div>
                                    <code class="mt-1 inline-block rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">
                                        {{ $item['section_key'] ?: '-' }}
                                    </code>
                                </td>
                                <td>{{ $item['template_key'] ?: '-' }}</td>
                                <td>{{ $item['fields_count'] }}</td>
                                <td>
                                    <span @class([
                                        'rounded px-2 py-1 text-xs',
                                        'bg-green-100 text-green-800' => $item['status'] === 'new',
                                        'bg-blue-100 text-blue-800' => $item['status'] === 'update',
                                        'bg-red-100 text-red-800' => $item['status'] === 'invalid',
                                    ])>
                                        {{ ucfirst((string) $item['status']) }}
                                    </span>
                                </td>
                                <td>
                                    @if (! empty($item['errors']))
                                        <ul class="mb-0 list-disc ps-5 text-sm text-red-700">
                                            @foreach ($item['errors'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    @elseif ($item['existing_id'])
                                        <span class="text-sm text-slate-500">
                                            {{ __('Matches existing definition #:id', ['id' => $item['existing_id']]) }}
                                        </span>
                                    @else
                                        <span class="text-sm text-slate-500">{{ __('Ready to create.') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500">
                                    {{ __('No definitions found in the uploaded JSON.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-dashboard-layout>
