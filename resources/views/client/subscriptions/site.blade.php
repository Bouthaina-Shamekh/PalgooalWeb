<x-client-layout>
    @php
        $templateName = $subscription->template?->translation()?->name ?? $subscription->template?->name ?? 'Template website';
        $subscriptionStatus = match ((string) $subscription->status) {
            'active' => ['label' => 'Active subscription', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
            'pending' => ['label' => 'Pending activation', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'border-red-200 bg-red-50 text-red-700'],
            default => ['label' => ucfirst((string) ($subscription->status ?? 'draft')), 'class' => 'border-slate-200 bg-slate-100 text-slate-700'],
        };
        $provisioningStatus = match ((string) $subscription->provisioning_status) {
            'active' => ['label' => 'Provisioned', 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-700'],
            'provisioning' => ['label' => 'Provisioning', 'class' => 'border-sky-200 bg-sky-50 text-sky-700'],
            'failed' => ['label' => 'Needs attention', 'class' => 'border-red-200 bg-red-50 text-red-700'],
            default => ['label' => 'Queued', 'class' => 'border-amber-200 bg-amber-50 text-amber-700'],
        };
        $contentUrl = route('client.subscriptions.content', $subscription);
        $homepageEditorUrl = route('client.subscriptions.homepage-editor.index', $subscription);
        $pagesManagementUrl = route('client.subscriptions.pages', $subscription);
        $subscriptionUrl = route('client.subscriptions.show', $subscription);
        $viewUrl = $siteUrl ?: $subscriptionUrl;
        $platformHosted = $domainName !== '' && (\Illuminate\Support\Str::endsWith(\Illuminate\Support\Str::lower($domainName), '.wpgoals.com') || \Illuminate\Support\Str::endsWith(\Illuminate\Support\Str::lower($domainName), '.palgoals.com'));
        $domainStepBadge = $domainName === '' ? 'Subdomain pending' : ($platformHosted ? 'Starter domain active' : 'Custom domain connected');
        $domainStepTone = $domainName === '' ? 'border-amber-200 bg-amber-50 text-amber-700' : ($platformHosted ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700');

        $steps = [
            [
                'title' => 'Edit homepage',
                'description' => 'Start with the main headline, hero section, and your first call to action.',
                'href' => $homepageEditorUrl,
                'icon' => 'ti ti-layout-dashboard',
                'badge' => 'Recommended first step',
                'tone' => 'border-sky-200 bg-sky-50 text-sky-700',
                'launch' => true,
            ],
            [
                'title' => 'Manage pages',
                'description' => 'Review the pages that came with your template and decide what to keep or refine.',
                'href' => $pagesManagementUrl,
                'icon' => 'ti ti-files',
                'badge' => 'Structure and content',
                'tone' => 'border-slate-200 bg-slate-100 text-slate-700',
                'launch' => false,
            ],
            [
                'title' => 'Connect domain',
                'description' => 'Move from the starter address to your branded domain when you are ready.',
                'href' => route('client.domains.index'),
                'icon' => 'ti ti-world-www',
                'badge' => $domainStepBadge,
                'tone' => $domainStepTone,
                'launch' => false,
            ],
        ];
    @endphp

    <div class="mx-auto max-w-5xl space-y-6 px-1 pb-10">
        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('client.home') }}" class="transition hover:text-slate-900">Dashboard</a>
            <span>/</span>
            <a href="{{ route('client.subscriptions') }}" class="transition hover:text-slate-900">Subscriptions</a>
            <span>/</span>
            <span class="text-slate-900">Site dashboard</span>
        </div>

        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-emerald-50 shadow-sm">
            <div class="px-6 py-8 lg:px-8 lg:py-10">
                <div class="max-w-3xl">
                    <span class="inline-flex rounded-full border border-emerald-200 bg-white px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700 shadow-sm">
                        First visit
                    </span>
                    <h1 class="mt-4 text-3xl font-bold tracking-tight text-slate-950 lg:text-4xl">
                        Your website is ready
                    </h1>
                    <p class="mt-3 text-base leading-7 text-slate-600">
                        Your template site has been provisioned successfully. Start with a few guided actions to make it feel like your business, then review it live before sharing it.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ $homepageEditorUrl }}" data-site-editor-launch
                            class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                            <span data-site-editor-label>Edit your website</span>
                        </a>
                        <a href="{{ $viewUrl }}" @if ($siteUrl) target="_blank" rel="noopener" @endif
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            View website
                        </a>
                    </div>
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-white bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Site name</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $siteName }}</p>
                    </div>
                    <div class="rounded-2xl border border-white bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Site URL</p>
                        <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ $domainName !== '' ? $domainName : 'Automatic subdomain' }}</p>
                    </div>
                    <div class="rounded-2xl border border-white bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Template</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $templateName }}</p>
                    </div>
                    <div class="rounded-2xl border border-white bg-white/90 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $subscriptionStatus['class'] }}">
                                {{ $subscriptionStatus['label'] }}
                            </span>
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $provisioningStatus['class'] }}">
                                {{ $provisioningStatus['label'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm lg:p-8">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Next steps</p>
                <h2 class="mt-2 text-2xl font-bold text-slate-950">Start with these three actions</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">
                    This page is intentionally lightweight. Use it as your entry point, take the next step that matters most, then continue managing the site from there.
                </p>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-3">
                @foreach ($steps as $step)
                    <a href="{{ $step['href'] }}"
                        @if ($step['launch']) data-site-editor-launch @endif
                        class="group rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5 transition hover:-translate-y-0.5 hover:border-slate-300 hover:bg-white hover:shadow-sm">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-700 shadow-sm">
                            <i class="{{ $step['icon'] }} text-xl leading-none"></i>
                        </span>
                        <div class="mt-4">
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $step['tone'] }}">
                                {{ $step['badge'] }}
                            </span>
                            <h3 class="mt-3 text-lg font-semibold text-slate-950">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ $step['description'] }}</p>
                        </div>
                        <div class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-slate-700 transition group-hover:text-slate-950">
                            <span @if ($step['launch']) data-site-editor-label @endif>Continue</span>
                            <i class="ti ti-arrow-up-left text-base leading-none rtl:rotate-180"></i>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-6">
                <a href="{{ $subscriptionUrl }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Manage subscription
                </a>
                <a href="{{ route('client.subscriptions') }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-transparent px-5 py-3 text-sm font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-slate-800">
                    Back to subscriptions
                </a>
            </div>
        </section>

        <div id="site-editor-launch-overlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/20 px-4 backdrop-blur-sm" aria-hidden="true">
            <div class="flex items-center gap-3 rounded-[1.5rem] border border-slate-200 bg-white px-5 py-4 shadow-2xl">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-sky-50 text-sky-700">
                    <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z"></path>
                    </svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-950">Opening homepage editor</p>
                    <p class="text-xs text-slate-500">Taking you directly to the homepage content area.</p>
                </div>
            </div>
        </div>
    </div>
</x-client-layout>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const overlay = document.getElementById('site-editor-launch-overlay');

            document.querySelectorAll('[data-site-editor-launch]').forEach((link) => {
                link.addEventListener('click', function (event) {
                    if (event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                        return;
                    }

                    overlay?.classList.remove('hidden');
                    overlay?.classList.add('flex');
                    overlay?.setAttribute('aria-hidden', 'false');

                    link.classList.add('pointer-events-none', 'opacity-90');

                    link.querySelectorAll('[data-site-editor-label]').forEach((label) => {
                        label.textContent = 'Opening homepage editor...';
                    });
                });
            });
        });
    </script>
@endpush
