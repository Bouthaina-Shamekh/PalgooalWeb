<x-client-layout>
    @php
        $templateName = $subscription->template?->translation()?->name ?? $subscription->template?->name ?? 'Template website';
        $homePage = $pages->firstWhere('is_home', true) ?? $pages->first();
        $homeTrans = $homePage?->translations->firstWhere('locale', $locale) ?? $homePage?->translations->first();
        $siteName = $homeTrans?->title ?: ($templateName ?: 'Your website');
        $domainName = trim((string) ($subscription->domain_name ?? ''));
        $siteUrl = $siteUrl ?? $subscription->activeSiteUrl();

        $pageCount = $pages->count();
        $sectionCount = $pages->sum(fn ($page) => $page->sections->count());
        $visiblePagesCount = $pages->where('is_active', true)->count();
        $otherPages = $pages->filter(fn ($page) => ! $page->is_home)->values();

        $statusBadge = match ((string) $subscription->status) {
            'active' => ['label' => 'Active subscription', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
            'pending' => ['label' => 'Pending activation', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
            default => ['label' => ucfirst((string) ($subscription->status ?? 'draft')), 'class' => 'border-slate-200 bg-slate-100 text-slate-700'],
        };

        $provisioningBadge = match ((string) $subscription->provisioning_status) {
            'active' => ['label' => 'Provisioned', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
            'provisioning' => ['label' => 'Provisioning', 'class' => 'border-sky-200 bg-sky-50 text-sky-700'],
            'failed' => ['label' => 'Needs attention', 'class' => 'border-red-200 bg-red-50 text-red-700'],
            default => ['label' => 'Queued', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
        };

        $siteDashboardUrl = route('client.subscriptions.site', $subscription);
        $contentUrl = route('client.subscriptions.content', $subscription);
        $pagesManagementUrl = route('client.subscriptions.pages', $subscription);
        $homepageEditorUrl = route('client.subscriptions.homepage-editor.index', $subscription);
        $subscriptionUrl = route('client.subscriptions.show', $subscription);
    @endphp

    <div class="mx-auto max-w-7xl space-y-8 px-1 pb-10">
        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('client.home') }}" class="transition hover:text-slate-900">Dashboard</a>
            <span>/</span>
            <a href="{{ route('client.subscriptions') }}" class="transition hover:text-slate-900">Subscriptions</a>
            <span>/</span>
            <a href="{{ $siteDashboardUrl }}" class="transition hover:text-slate-900">Site dashboard</a>
            <span>/</span>
            <span class="text-slate-900">Content</span>
        </div>

        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm">
            <div class="grid gap-8 px-6 py-8 lg:grid-cols-12 lg:px-8 lg:py-10">
                <div class="lg:col-span-8">
                    <span class="inline-flex rounded-full border border-sky-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-700 shadow-sm">
                        Content management
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 lg:text-4xl">
                        Manage your website content
                    </h1>
                    <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                        This area is dedicated to your website pages and sections. Start with the homepage, then review the rest of the site structure and the content blocks attached to each page.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ $homepageEditorUrl }}" data-open-homepage-editor
                            class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                            <span data-open-homepage-editor-label>Edit homepage</span>
                        </a>
                        @if ($siteUrl)
                            <a href="{{ $siteUrl }}" target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                View website
                            </a>
                        @endif
                        <a href="{{ $siteDashboardUrl }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-transparent px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-white/70 hover:text-slate-900">
                            Back to site dashboard
                        </a>
                        <a href="{{ $pagesManagementUrl }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Manage pages
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="rounded-[1.75rem] border border-white bg-white/90 p-6 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Content overview</p>
                        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Site</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $siteName }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $templateName }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pages</p>
                                <p class="mt-2 text-2xl font-bold text-slate-950">{{ $pageCount }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $visiblePagesCount }} visible to visitors</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sections</p>
                                <p class="mt-2 text-2xl font-bold text-slate-950">{{ $sectionCount }}</p>
                                <p class="mt-1 text-xs text-slate-500">Across all current pages</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $statusBadge['class'] }}">
                                        {{ $statusBadge['label'] }}
                                    </span>
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $provisioningBadge['class'] }}">
                                        {{ $provisioningBadge['label'] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="homepage-panel"
            class="scroll-mt-24 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm transition duration-300 lg:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Homepage</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-950">Start here first</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        Your homepage is usually the first thing visitors see. Use this section to understand its current title, visibility, URL, and the content blocks already attached to it.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ $homepageEditorUrl }}" data-open-homepage-editor
                        class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        <span data-open-homepage-editor-label>Edit homepage</span>
                    </a>
                    @if ($homePage && $siteUrl)
                        <a href="{{ $siteUrl }}" target="_blank" rel="noopener"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Preview homepage
                        </a>
                    @endif
                </div>
            </div>

            @if ($homePage)
                <div class="mt-6 grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Homepage info</p>
                        <div class="mt-4 space-y-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Title</p>
                                <p class="mt-2 text-base font-semibold text-slate-950">{{ $homeTrans?->title ?? 'Untitled homepage' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Slug</p>
                                <p class="mt-2 text-sm font-medium text-slate-700">{{ trim((string) ($homeTrans?->slug ?? $homePage->slug ?? '/')) ?: '/' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Visibility</p>
                                <span class="mt-2 inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $homePage->is_active ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-100 text-slate-600' }}">
                                    {{ $homePage->is_active ? 'Visible' : 'Hidden' }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sections</p>
                                <p class="mt-2 text-2xl font-bold text-slate-950">{{ $homePage->sections->count() }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Homepage sections</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse ($homePage->sections as $section)
                                @php
                                    $sectionTrans = $section->translations->firstWhere('locale', $locale) ?? $section->translations->first();
                                @endphp
                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600">
                                    {{ $sectionTrans?->title ?? \Illuminate\Support\Str::headline((string) ($section->type ?? 'section')) }}
                                </span>
                            @empty
                                <p class="text-sm text-slate-500">No active sections are attached to the homepage yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-6 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                    <h3 class="text-lg font-semibold text-slate-900">No homepage is available yet</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        This subscription does not currently have a canonical homepage attached.
                    </p>
                </div>
            @endif
        </section>

        <section id="pages-panel" class="scroll-mt-24 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Pages</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-950">Manage site pages</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        Review the structure of the rest of your site. This makes the content area feel less like a raw dump of data and more like a real page-management space.
                    </p>
                </div>
                <a href="{{ $pagesManagementUrl }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Open pages manager
                </a>
            </div>

            <div class="mt-6 grid gap-4 xl:grid-cols-2">
                @forelse ($pages as $page)
                    @php
                        $pageTrans = $page->translations->firstWhere('locale', $locale) ?? $page->translations->first();
                        $pageSlug = trim((string) ($pageTrans?->slug ?? $page->slug ?? ''));
                        $pageUrl = $siteUrl ? ($page->is_home || $pageSlug === '' ? $siteUrl : rtrim($siteUrl, '/') . '/' . ltrim($pageSlug, '/')) : null;
                        $pageEditorUrl = $page->is_home
                            ? $homepageEditorUrl
                            : route('client.subscriptions.pages.editor.index', [
                                'subscription' => $subscription,
                                'page' => $page,
                            ]);
                    @endphp
                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-semibold text-slate-950">{{ $pageTrans?->title ?? $page->slug ?? 'Untitled page' }}</h3>
                                    @if ($page->is_home)
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Homepage</span>
                                    @endif
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $page->is_active ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-100 text-slate-600' }}">
                                        {{ $page->is_active ? 'Visible' : 'Hidden' }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-slate-500">{{ $pageSlug !== '' ? $pageSlug : '/' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ $pageEditorUrl }}"
                                    class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    Edit page
                                </a>
                                @if ($pageUrl)
                                    <a href="{{ $pageUrl }}" target="_blank" rel="noopener"
                                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        View page
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center xl:col-span-2">
                        <h3 class="text-lg font-semibold text-slate-900">No pages are available yet</h3>
                        <p class="mt-2 text-sm text-slate-500">This subscription does not currently have canonical pages attached.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <section id="content-workspace" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Sections & content</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-950">Content blocks by page</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        This is the same safe backend-loaded content view as before, but reorganized into a clearer content area so users understand how sections are attached to each page.
                    </p>
                </div>
                <a href="{{ $siteDashboardUrl }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Back to site dashboard
                </a>
            </div>

            <div class="mt-6 space-y-5">
                @forelse ($pages as $page)
                    @php
                        $pageTrans = $page->translations->firstWhere('locale', $locale) ?? $page->translations->first();
                    @endphp
                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-xl font-semibold text-slate-950">{{ $pageTrans?->title ?? $page->slug ?? 'Untitled page' }}</h3>
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $page->is_active ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-100 text-slate-600' }}">
                                {{ $page->is_active ? 'Visible' : 'Hidden' }}
                            </span>
                        </div>

                        <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sections in this page</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($page->sections as $section)
                                    @php
                                        $sectionTrans = $section->translations->firstWhere('locale', $locale) ?? $section->translations->first();
                                    @endphp
                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-600">
                                        {{ $sectionTrans?->title ?? \Illuminate\Support\Str::headline((string) ($section->type ?? 'section')) }}
                                    </span>
                                @empty
                                    <p class="text-sm text-slate-500">No active sections are attached to this page yet.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                        <h3 class="text-lg font-semibold text-slate-900">No tenant pages are available yet</h3>
                        <p class="mt-2 text-sm text-slate-500">This subscription does not currently have canonical pages attached.</p>
                    </div>
                @endforelse
            </div>
        </section>

        <div id="content-editor-launch-overlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/15 px-4 backdrop-blur-sm" aria-hidden="true">
            <div class="flex items-center gap-3 rounded-[1.5rem] border border-slate-200 bg-white px-5 py-4 shadow-2xl">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                    <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z"></path>
                    </svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-950">Opening homepage editor</p>
                    <p class="text-xs text-slate-500">Jumping to the homepage content area now.</p>
                </div>
            </div>
        </div>
    </div>
</x-client-layout>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('content-editor-launch-overlay');

            const setLaunchOverlay = (active) => {
                if (!overlay) {
                    return;
                }

                overlay.classList.toggle('hidden', !active);
                overlay.classList.toggle('flex', active);
                overlay.setAttribute('aria-hidden', active ? 'false' : 'true');
            };

            document.querySelectorAll('[data-open-homepage-editor]').forEach((trigger) => {
                trigger.addEventListener('click', function (event) {
                    if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                        return;
                    }

                    const labels = Array.from(trigger.querySelectorAll('[data-open-homepage-editor-label]'));
                    labels.forEach((label) => {
                        label.textContent = 'Opening homepage editor...';
                    });
                    setLaunchOverlay(true);
                });
            });
        });
    </script>
@endpush
