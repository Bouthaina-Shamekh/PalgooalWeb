<x-client-layout>
    @php
        $templateName = $subscription->template?->translation()?->name ?? $subscription->template?->name ?? 'Template website';
        $siteDashboardUrl = route('client.subscriptions.site', $subscription);
        $contentUrl = route('client.subscriptions.content', $subscription);
        $createPageUrl = route('client.subscriptions.pages.store', $subscription);
        $homepageEditorUrl = route('client.subscriptions.homepage-editor.index', $subscription);
        $pageCount = $pages->count();
        $activePagesCount = $pages->where('is_active', true)->count();
        $hiddenPagesCount = $pages->where('is_active', false)->count();
        $homePage = $pages->firstWhere('is_home', true);
        $createPageErrors = $errors->getBag('createPage');
        $pageSettingsErrors = $errors->getBag('pageSettings');
        $domainName = trim((string) ($subscription->domain_name ?? ''));
        $siteUrl = $domainName !== ''
            ? (\Illuminate\Support\Str::startsWith($domainName, ['http://', 'https://'])
                ? rtrim($domainName, '/')
                : rtrim((request()->secure() ? 'https://' : 'http://') . ltrim($domainName, '/'), '/'))
            : null;
    @endphp

    <div class="mx-auto max-w-6xl space-y-6 px-1 pb-10">
        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('client.home') }}" class="transition hover:text-slate-900">Dashboard</a>
            <span>/</span>
            <a href="{{ route('client.subscriptions') }}" class="transition hover:text-slate-900">Subscriptions</a>
            <span>/</span>
            <a href="{{ $siteDashboardUrl }}" class="transition hover:text-slate-900">Site dashboard</a>
            <span>/</span>
            <span class="text-slate-900">Pages</span>
        </div>

        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm">
            <div class="grid gap-8 px-6 py-8 lg:grid-cols-12 lg:px-8 lg:py-10">
                <div class="lg:col-span-8">
                    <span class="inline-flex rounded-full border border-sky-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-700 shadow-sm">
                        Page management
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 lg:text-4xl">
                        Manage your site pages
                    </h1>
                    <p class="mt-3 max-w-3xl text-base leading-7 text-slate-600">
                        Review every tenant page created for this site, open the homepage shortcut when you need a fast start, or jump directly into the shared editor for any page.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="#create-page"
                            class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                            Add new page
                        </a>
                        <a href="{{ $homepageEditorUrl }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Edit homepage
                        </a>
                        <a href="{{ $contentUrl }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Content overview
                        </a>
                        <a href="{{ $siteDashboardUrl }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-transparent px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-white/70 hover:text-slate-900">
                            Back to site dashboard
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="rounded-[1.75rem] border border-white bg-white/90 p-6 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Pages summary</p>
                        <div class="mt-5 grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Template</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $templateName }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Pages</p>
                                <p class="mt-2 text-2xl font-bold text-slate-950">{{ $pageCount }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $activePagesCount }} visible, {{ $hiddenPagesCount }} hidden</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Homepage</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">
                                    {{ $homePage?->translations->firstWhere('locale', $locale)?->title ?? $homePage?->translations->first()?->title ?? 'Not available yet' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="create-page" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Create page</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-950">Add a new page to your site</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        Start with a page title and an optional slug. If you leave the slug empty, the system will generate one safely and take you straight into the editor.
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ $createPageUrl }}" class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_auto] lg:items-end">
                @csrf

                <div>
                    <label for="new-page-title" class="mb-2 block text-sm font-semibold text-slate-700">Page title</label>
                    <input
                        id="new-page-title"
                        name="title"
                        type="text"
                        value="{{ $createPageErrors->any() ? old('title') : '' }}"
                        placeholder="About us"
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400"
                        required
                    >
                    @if ($createPageErrors->has('title'))
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $createPageErrors->first('title') }}</p>
                    @endif
                </div>

                <div>
                    <label for="new-page-slug" class="mb-2 block text-sm font-semibold text-slate-700">Slug <span class="font-normal text-slate-400">(optional)</span></label>
                    <input
                        id="new-page-slug"
                        name="slug"
                        type="text"
                        value="{{ $createPageErrors->any() ? old('slug') : '' }}"
                        placeholder="about-us"
                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400"
                    >
                    @if ($createPageErrors->has('slug'))
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $createPageErrors->first('slug') }}</p>
                    @endif
                </div>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                >
                    Create and edit
                </button>
            </form>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Pages</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-950">Choose a page to edit</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                        Homepage editing stays one click away, but any other tenant page can now open the same shared page sections workspace safely under your subscription.
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 xl:grid-cols-2">
                @forelse ($pages as $page)
                    @php
                        $pageTrans = $page->translations->firstWhere('locale', $locale) ?? $page->translations->first();
                        $pageTitle = $pageTrans?->title ?? $page->slug ?? 'Untitled page';
                        $pageSlug = trim((string) ($pageTrans?->slug ?? $page->slug ?? ''));
                        $pageUrl = $siteUrl ? ($page->is_home || $pageSlug === '' ? $siteUrl : $siteUrl . '/' . ltrim($pageSlug, '/')) : null;
                        $pageEditorUrl = route('client.subscriptions.pages.editor.index', [
                            'subscription' => $subscription,
                            'page' => $page,
                        ]);
                        $pageUpdateUrl = route('client.subscriptions.pages.update', [
                            'subscription' => $subscription,
                            'page' => $page,
                        ]);
                        $pageSetHomeUrl = route('client.subscriptions.pages.set-home', [
                            'subscription' => $subscription,
                            'page' => $page,
                        ]);
                        $pageDeleteUrl = route('client.subscriptions.pages.destroy', [
                            'subscription' => $subscription,
                            'page' => $page,
                        ]);
                        $settingsFormHasErrors = $pageSettingsErrors->any() && (string) old('page_id') === (string) $page->id;
                        $settingsTitleValue = $settingsFormHasErrors ? old('title', $pageTitle) : $pageTitle;
                        $settingsSlugValue = $settingsFormHasErrors ? old('slug', $pageSlug) : $pageSlug;
                        $canDeletePage = $pageCount > 1;
                    @endphp

                    <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-semibold text-slate-950">{{ $pageTitle }}</h3>
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

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Sections</p>
                                <p class="mt-2 text-2xl font-bold text-slate-950">{{ $page->sections->count() }}</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Editing mode</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $page->is_home ? 'Homepage shortcut available' : 'Standard page editor' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 rounded-[1.25rem] border border-slate-200 bg-white p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Page settings</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Update the title and slug, promote this page to homepage, or remove it safely from your site.
                                    </p>
                                </div>
                                @if ($page->is_home)
                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        Current homepage
                                    </span>
                                @endif
                            </div>

                            <form method="POST" action="{{ $pageUpdateUrl }}" class="mt-4 grid gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="page_id" value="{{ $page->id }}">

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Page title</label>
                                    <input
                                        type="text"
                                        name="title"
                                        value="{{ $settingsTitleValue }}"
                                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400"
                                        required
                                    >
                                    @if ($settingsFormHasErrors && $pageSettingsErrors->has('title'))
                                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $pageSettingsErrors->first('title') }}</p>
                                    @endif
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-slate-700">Slug</label>
                                    <input
                                        type="text"
                                        name="slug"
                                        value="{{ $settingsSlugValue }}"
                                        class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-slate-400"
                                        placeholder="about-us"
                                    >
                                    @if ($settingsFormHasErrors && $pageSettingsErrors->has('slug'))
                                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $pageSettingsErrors->first('slug') }}</p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-3 lg:col-span-2">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                    >
                                        Save settings
                                    </button>
                                </div>
                            </form>

                            <div class="mt-4 flex flex-wrap gap-3 border-t border-slate-200 pt-4">
                                @if (! $page->is_home)
                                    <form method="POST" action="{{ $pageSetHomeUrl }}">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-xl border border-sky-300 bg-sky-50 px-4 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-100"
                                        >
                                            Set as homepage
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ $pageDeleteUrl }}" onsubmit="return confirm('{{ $page->is_home ? __('Delete this homepage and promote another page automatically?') : __('Delete this page?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:border-slate-200 disabled:bg-slate-100 disabled:text-slate-400"
                                        @disabled(! $canDeletePage)
                                    >
                                        Delete page
                                    </button>
                                </form>

                                @if (! $canDeletePage)
                                    <p class="self-center text-xs text-slate-500">At least one page must remain on your site.</p>
                                @elseif ($page->is_home)
                                    <p class="self-center text-xs text-slate-500">If you delete the homepage, another page will be promoted automatically.</p>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-10 text-center xl:col-span-2">
                        <h3 class="text-lg font-semibold text-slate-900">No pages are available yet</h3>
                        <p class="mt-2 text-sm text-slate-500">This subscription does not currently have canonical pages attached.</p>
                        <a href="#create-page"
                            class="mt-5 inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                            Add your first page
                        </a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-client-layout>
