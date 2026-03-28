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
        $verifyDomainUrl = route('client.subscriptions.verify-domain', $subscription);
        $domainsIndexUrl = route('client.domains.index');
        $domainSearchUrl = route('client.domains.search');
        $viewUrl = $siteUrl ?: $subscriptionUrl;
        $activeHost = $domainVerification['active_host'] ?? $domainName;
        $verificationStatus = (string) ($domainVerification['status'] ?? 'pending');
        $customDomain = $domainVerification['custom_domain'] ?? null;
        $fallbackHost = $domainVerification['fallback_host'] ?? null;
        $targetHost = $domainVerification['instructions']['target_host'] ?? $fallbackHost;
        $platformIps = array_values(array_filter($domainVerification['instructions']['platform_ips'] ?? []));
        $statusLabel = $domainVerification['needs_verification']
            ? ($domainVerification['label'] ?? 'Verification pending (DNS not detected yet)')
            : 'Platform subdomain active';
        $verificationTone = match ((string) ($domainVerification['tone'] ?? 'amber')) {
            'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'sky' => 'border-sky-200 bg-sky-50 text-sky-700',
            'red' => 'border-red-200 bg-red-50 text-red-700',
            default => 'border-amber-200 bg-amber-50 text-amber-700',
        };
        $domainSummary = match (true) {
            ! $domainVerification['needs_verification'] => [
                'title' => 'Starter domain is active',
                'summary' => 'Your platform subdomain is already live and safe to share. You can keep using it as long as you want.',
                'next' => 'If you later connect a branded domain, this starter address stays active until the custom domain is fully ready.',
            ],
            $verificationStatus === 'active' => [
                'title' => 'Custom domain is live',
                'summary' => 'Your branded domain is now serving the website. Visitors can use it immediately.',
                'next' => 'Your platform subdomain still exists as a safe fallback address if you ever need it.',
            ],
            $verificationStatus === 'ssl_pending' => [
                'title' => 'DNS looks good. HTTPS is still finishing',
                'summary' => 'We detected the domain pointing in the right direction, but SSL/HTTPS is not ready yet.',
                'next' => 'Keep sharing the platform subdomain for now. The custom domain will switch over automatically once HTTPS is ready.',
            ],
            $verificationStatus === 'failed' => [
                'title' => 'Verification needs another pass',
                'summary' => 'We could not confirm that the custom domain is ready to serve the website yet.',
                'next' => 'Review the latest result below, adjust the DNS if needed, then retry verification.',
            ],
            default => [
                'title' => 'Finish connecting your custom domain',
                'summary' => 'We are still waiting to detect the DNS settings for your custom domain.',
                'next' => 'Until the badge becomes "Custom domain active", keep sharing the platform subdomain shown as the live address.',
            ],
        };
        $domainChecklist = $domainVerification['needs_verification']
            ? [
                'Copy the DNS target exactly as shown below.',
                $verificationStatus === 'ssl_pending'
                    ? 'Wait for HTTPS/SSL to finish provisioning, then retry verification if needed.'
                    : 'Update your DNS provider, wait for propagation, then retry verification.',
                'Keep sharing the current live address until the custom domain becomes active.',
            ]
            : [
                'Keep using the platform subdomain if you want a fast starter address.',
                'Search for or buy a branded domain whenever you are ready.',
                'When you connect that domain later, the platform subdomain remains live until verification finishes.',
            ];
        $copyButtonClass = 'inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900';
        $domainStepBadge = $domainVerification['needs_verification']
            ? $statusLabel
            : ($activeHost ? 'Starter domain active' : 'Subdomain pending');
        $domainStepTone = $domainVerification['needs_verification']
            ? $verificationTone
            : ($activeHost ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-amber-200 bg-amber-50 text-amber-700');

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
                'href' => $domainsIndexUrl,
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
                        <p class="mt-2 break-all text-sm font-semibold text-slate-900">{{ $activeHost ?: 'Automatic subdomain' }}</p>
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
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-400">Domain setup</p>
                    <h2 class="mt-2 text-2xl font-bold text-slate-950">{{ $domainSummary['title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $domainSummary['summary'] }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex rounded-full border px-3 py-1.5 text-xs font-semibold {{ $verificationTone }}">
                        {{ $statusLabel }}
                    </span>
                    @if ($domainVerification['needs_verification'])
                        <form action="{{ $verifyDomainUrl }}" method="POST">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                Retry verification
                            </button>
                        </form>
                        <a href="{{ $domainsIndexUrl }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Open My Domains
                        </a>
                    @else
                        <a href="{{ $domainSearchUrl }}"
                            class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Search a domain
                        </a>
                        <a href="{{ $domainsIndexUrl }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            My Domains
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-6 rounded-[1.5rem] border border-sky-200 bg-sky-50/70 p-5">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-3xl">
                        <p class="text-sm font-semibold text-sky-900">What this means right now</p>
                        <p class="mt-2 text-sm leading-6 text-sky-800">
                            {{ $domainSummary['next'] }}
                        </p>
                    </div>
                    @if ($siteUrl)
                        <a href="{{ $siteUrl }}" target="_blank" rel="noopener"
                            class="inline-flex items-center justify-center rounded-2xl border border-sky-300 bg-white px-4 py-2.5 text-sm font-semibold text-sky-800 transition hover:bg-sky-100">
                            Open live website
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-3">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Current live address</p>
                    <p class="mt-2 break-all text-base font-semibold text-slate-950">{{ $activeHost ?: 'Not available yet' }}</p>
                    <p class="mt-3 text-sm leading-6 text-slate-500">
                        {{ $domainVerification['needs_verification'] && $verificationStatus !== 'active'
                            ? 'Keep sharing this address until the custom domain becomes active.'
                            : 'This is the address currently serving your website.' }}
                    </p>
                    @if ($activeHost)
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" data-copy-value="{{ $activeHost }}" class="{{ $copyButtonClass }}">
                                <i class="ti ti-copy text-sm leading-none"></i>
                                <span data-copy-label>Copy address</span>
                            </button>
                            @if ($siteUrl)
                                <a href="{{ $siteUrl }}" target="_blank" rel="noopener" class="{{ $copyButtonClass }}">
                                    <i class="ti ti-external-link text-sm leading-none"></i>
                                    <span>Open</span>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ $domainVerification['needs_verification'] ? 'Custom domain' : 'Add a branded domain later' }}
                    </p>
                    <p class="mt-2 break-all text-base font-semibold text-slate-950">
                        {{ $domainVerification['needs_verification'] ? ($customDomain ?: 'Not assigned yet') : 'Use your own domain when you are ready' }}
                    </p>
                    <p class="mt-3 text-sm leading-6 text-slate-500">
                        {{ $domainVerification['needs_verification']
                            ? 'This is the domain you want visitors to use once verification completes.'
                            : 'Buying or adding a custom domain does not interrupt your site. The platform subdomain keeps working until the branded domain is fully verified.' }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($domainVerification['needs_verification'] && $customDomain)
                            <button type="button" data-copy-value="{{ $customDomain }}" class="{{ $copyButtonClass }}">
                                <i class="ti ti-copy text-sm leading-none"></i>
                                <span data-copy-label>Copy domain</span>
                            </button>
                        @endif
                        <a href="{{ $domainSearchUrl }}" class="{{ $copyButtonClass }}">
                            <i class="ti ti-search text-sm leading-none"></i>
                            <span>{{ $domainVerification['needs_verification'] ? 'Find another domain' : 'Search domains' }}</span>
                        </a>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ $domainVerification['needs_verification'] ? 'DNS target' : 'Fallback behavior' }}
                    </p>
                    <p class="mt-2 break-all text-base font-semibold text-slate-950">
                        {{ $domainVerification['needs_verification'] ? ($targetHost ?: 'Not assigned yet') : ($activeHost ?: 'Platform subdomain will be assigned') }}
                    </p>
                    <p class="mt-3 text-sm leading-6 text-slate-500">
                        {{ $domainVerification['needs_verification']
                            ? 'Use this target in your DNS provider. In most cases, copying this host exactly is the safest path.'
                            : 'When you connect a custom domain later, this starter address remains live until DNS and HTTPS are ready.' }}
                    </p>
                    @if ($domainVerification['needs_verification'] && $targetHost)
                        <div class="mt-4 flex flex-wrap gap-2">
                            <button type="button" data-copy-value="{{ $targetHost }}" class="{{ $copyButtonClass }}">
                                <i class="ti ti-copy text-sm leading-none"></i>
                                <span data-copy-label>Copy target</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Next steps</p>
                    <ol class="mt-4 space-y-3">
                        @foreach ($domainChecklist as $index => $item)
                            <li class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-white text-xs font-semibold text-slate-700 shadow-sm">
                                    {{ $index + 1 }}
                                </span>
                                <p class="text-sm leading-6 text-slate-600">{{ $item }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Helpful details</p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        {{ $domainVerification['instructions']['summary'] ?? 'Point the custom domain to the platform fallback host, wait for propagation, then retry verification.' }}
                    </p>

                    @if (! empty($domainVerification['last_checked_at']))
                        <p class="mt-5 text-xs font-semibold uppercase tracking-wide text-slate-400">Last checked</p>
                        <p class="mt-2 text-sm text-slate-600">{{ $domainVerification['last_checked_at']->diffForHumans() }}</p>
                    @endif

                    @if (! empty($domainVerification['error']))
                        <p class="mt-5 text-xs font-semibold uppercase tracking-wide text-red-500">Latest result</p>
                        <p class="mt-2 text-sm leading-6 text-red-600">{{ $domainVerification['error'] }}</p>
                    @endif

                    @if (! empty($platformIps))
                        <p class="mt-5 text-xs font-semibold uppercase tracking-wide text-slate-400">Advanced: platform IPs</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Only use these if your DNS provider cannot point the domain to the target host directly.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($platformIps as $ip)
                                <button type="button" data-copy-value="{{ $ip }}"
                                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-white">
                                    <span>{{ $ip }}</span>
                                    <i class="ti ti-copy text-sm leading-none"></i>
                                    <span data-copy-label>Copy</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
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

            document.querySelectorAll('[data-copy-value]').forEach((button) => {
                button.addEventListener('click', async function () {
                    const value = button.getAttribute('data-copy-value');
                    const label = button.querySelector('[data-copy-label]') || button;
                    const originalLabel = label.textContent;

                    if (!value) {
                        return;
                    }

                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(value);
                        } else {
                            const textarea = document.createElement('textarea');
                            textarea.value = value;
                            textarea.setAttribute('readonly', 'readonly');
                            textarea.style.position = 'absolute';
                            textarea.style.left = '-9999px';
                            document.body.appendChild(textarea);
                            textarea.select();
                            document.execCommand('copy');
                            textarea.remove();
                        }

                        label.textContent = 'Copied';
                    } catch (error) {
                        label.textContent = 'Copy failed';
                    }

                    window.setTimeout(() => {
                        label.textContent = originalLabel;
                    }, 1600);
                });
            });

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
