<x-client-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('client.home') }}">{{ __('Dashboard') }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('client.subscriptions') }}">{{ __('Subscriptions') }}</a></li>
                <li class="breadcrumb-item" aria-current="page">#{{ $subscription->id }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ __('Subscription Details') }}</h2>
            </div>
        </div>
    </div>

    @if (session('ok'))
        <div class="alert alert-success mb-4">{{ session('ok') }}</div>
    @endif

    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12 lg:col-span-4">
            <div class="card p-6 space-y-4">
                <div>
                    <p class="text-sm text-gray-500">{{ __('Template') }}</p>
                    <p class="font-semibold text-lg">{{ $subscription->template?->translation()?->name ?? $subscription->template?->name ?? '-' }}</p>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                    <div>
                        <p class="text-gray-400 text-xs">{{ __('Plan') }}</p>
                        <p class="font-medium">{{ $subscription->plan?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">{{ __('Plan Type') }}</p>
                        <p class="font-medium">
                            {{ $subscription->plan?->isHosting() ? __('Hosting / WordPress') : __('Palgoals SaaS') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">{{ __('Domain') }}</p>
                        <p class="font-medium break-words">{{ $subscription->domain_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-xs">{{ __('Provisioning') }}</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                            @if ($subscription->provisioning_status === 'active') bg-emerald-100 text-emerald-800
                            @elseif($subscription->provisioning_status === 'failed') bg-red-100 text-red-800
                            @else bg-yellow-100 text-yellow-800 @endif">
                            {{ __($subscription->provisioning_status) }}
                        </span>
                    </div>
                </div>
                <div class="text-xs text-gray-400">
                    <p>{{ __('Provisioned at') }}: {{ optional($subscription->provisioned_at)->format('Y-m-d H:i') ?? '-' }}</p>
                    <p>{{ __('Last sync') }}: {{ optional($subscription->last_synced_at)->diffForHumans() ?? '-' }}</p>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8">
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">{{ __('Tenant Pages') }}</h3>
                        <p class="text-sm text-gray-500">{{ __('This subscription now uses the canonical Page + Section content system.') }}</p>
                    </div>
                    <a href="{{ route('client.subscriptions') }}" class="btn btn-outline-primary text-sm">{{ __('Back') }}</a>
                </div>

                @forelse ($pages as $page)
                    @php
                        $pageTrans = $page->translations->firstWhere('locale', $locale)
                            ?? $page->translations->first();
                    @endphp
                    <div class="border rounded-xl mb-4">
                        <div class="px-4 py-3 bg-gray-50 rounded-t-xl flex items-center justify-between gap-3">
                            <div>
                                <h4 class="font-semibold text-gray-800">
                                    {{ $pageTrans->title ?? $page->slug }}
                                    @if ($page->is_home)
                                        <span class="text-xs text-primary ms-2">{{ __('Home Page') }}</span>
                                    @endif
                                </h4>
                                <p class="text-xs text-gray-500">
                                    slug: {{ $pageTrans->slug ?? $page->slug }}
                                </p>
                            </div>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $page->is_active ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-600' }}">
                                {{ $page->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </div>
                        <div class="p-4 space-y-4">
                            @forelse ($page->sections as $section)
                                @php
                                    $sectionTrans = $section->translations->firstWhere('locale', $locale)
                                        ?? $section->translations->first();
                                    $content = $sectionTrans?->content;
                                    $contentPreview = is_array($content)
                                        ? json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                        : (is_string($content) ? $content : '');
                                @endphp
                                <div class="border border-dashed rounded-lg p-4 space-y-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-xs uppercase tracking-wide text-gray-400">
                                            {{ $section->type ?? 'section' }} / {{ __('Order') }} {{ $section->order }}
                                        </p>
                                        <span class="text-xs px-2 py-0.5 rounded-full {{ $section->is_active ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-600' }}">
                                            {{ $section->is_active ? __('Visible') : __('Hidden') }}
                                        </span>
                                    </div>
                                    <h5 class="text-base font-semibold">{{ $sectionTrans?->title ?? __('Untitled section') }}</h5>
                                    @if ($contentPreview !== '')
                                        <pre class="overflow-x-auto rounded-lg bg-gray-50 p-3 text-xs text-gray-600">{{ \Illuminate\Support\Str::limit($contentPreview, 800) }}</pre>
                                    @else
                                        <p class="text-sm text-gray-500">{{ __('No translated content is stored for this section yet.') }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-sm text-gray-500">{{ __('No sections are attached to this page yet.') }}</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="text-center text-gray-500 py-12">
                        {{ __('No canonical tenant pages are available for this subscription yet.') }}
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-client-layout>
