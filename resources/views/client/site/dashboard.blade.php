@php
    $templateName = $subscription->template?->translation()?->name ?? $subscription->template?->name ?? 'Template website';
    $pagesUrl = route('client.subscriptions.pages', $subscription);
    $editorUrl = route('client.subscriptions.homepage-editor.index', $subscription);
    $subscriptionUrl = route('client.subscriptions.show', $subscription);
    $domainsUrl = route('client.domains.index');
    $previewUrl = $siteUrl ?: $subscriptionUrl;

    $verificationStatus = (string) ($domainVerification['status'] ?? 'pending');
    $needsVerification = (bool) ($domainVerification['needs_verification'] ?? false);
    $activeHost = $domainVerification['active_host'] ?? $domainName;
    $fallbackHost = $domainVerification['fallback_host'] ?? null;
    $displayHost = $activeHost ?: $fallbackHost ?: ($siteUrl ? parse_url($siteUrl, PHP_URL_HOST) : null);

    $heroStatus = [
        'label' => $siteUrl ? 'Live' : 'Draft',
        'tone' => $siteUrl
            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
            : 'border-amber-200 bg-amber-50 text-amber-700',
    ];

    $provisioningText = $subscription->provisioning_status === 'active'
        ? 'Provisioned'
        : ucfirst((string) ($subscription->provisioning_status ?? 'Pending'));

    $domainLabel = $needsVerification
        ? ($domainVerification['label'] ?? 'Verification pending (DNS not detected yet)')
        : 'Platform subdomain active';

    $domainTone = match (true) {
        ! $needsVerification => 'border-sky-200 bg-sky-50 text-sky-700',
        $verificationStatus === 'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
        $verificationStatus === 'ssl_pending' => 'border-violet-200 bg-violet-50 text-violet-700',
        $verificationStatus === 'failed' => 'border-rose-200 bg-rose-50 text-rose-700',
        default => 'border-amber-200 bg-amber-50 text-amber-700',
    };

    $domainSummary = match (true) {
        ! $needsVerification => 'Your platform subdomain is already live. You can connect your own domain whenever you are ready.',
        $verificationStatus === 'active' => 'Your custom domain is active now. The platform subdomain still exists as a safe fallback.',
        $verificationStatus === 'ssl_pending' => 'We found the domain in DNS, but HTTPS is still finishing. Keep sharing the platform subdomain for now.',
        $verificationStatus === 'failed' => 'We could not verify the custom domain yet. The platform subdomain stays live while you update the domain settings.',
        default => 'Your custom domain is being checked. The platform subdomain stays live until everything is ready.',
    };

    $onboardingSteps = collect($siteOnboarding['steps'] ?? []);
    $completedSteps = (int) ($siteOnboarding['completed_steps'] ?? $onboardingSteps->where('completed', true)->count());
    $totalSteps = (int) ($siteOnboarding['total_steps'] ?? max(1, $onboardingSteps->count()));
    $progressPercent = (int) ($siteOnboarding['progress_percent'] ?? 0);
    $currentStepKey = (string) ($siteOnboarding['current_step'] ?? 'edit_homepage');
    $currentStepPosition = (int) ($siteOnboarding['current_step_position'] ?? 1);
    $activeStep = $onboardingSteps->firstWhere('active', true);
    $currentStepUrl = data_get($activeStep, 'url', $previewUrl);

    $heroPrimaryLabel = match ($currentStepKey) {
        'manage_pages' => __('Manage your pages'),
        'connect_domain' => __('Connect your domain'),
        'complete' => __('View website'),
        default => __('Start editing your site'),
    };

    $currentStepTitle = match ($currentStepKey) {
        'manage_pages' => __('Review your pages'),
        'connect_domain' => __('Connect your domain'),
        'complete' => __('Your setup is complete'),
        default => __('Start with your homepage'),
    };

    $currentStepHelper = match ($currentStepKey) {
        'manage_pages' => __('Review the pages that came with your site and add or refine anything new.'),
        'connect_domain' => __('Your site is already live on the platform subdomain. Connect a branded domain when you are ready.'),
        'complete' => __('Homepage, pages, and domain are all in place. You can keep refining your site any time.'),
        default => __('Start with your homepage. This is the fastest way to make your site feel like yours.'),
    };

    $currentStepButtonLabel = match ($currentStepKey) {
        'manage_pages' => __('Manage pages'),
        'connect_domain' => __('Open domain settings'),
        'complete' => __('View website'),
        default => __('Continue editing'),
    };

    $currentStepHint = match ($currentStepKey) {
        'manage_pages' => __("Next: You'll connect your domain"),
        'connect_domain' => __('Next: Your onboarding will be complete'),
        'complete' => __('Everything is connected and ready to share'),
        default => __("Next: You'll review your pages"),
    };

    $currentCardTone = $currentStepKey === 'complete'
        ? 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-white'
        : 'border-[#240B36]/10 bg-gradient-to-br from-[#240B36]/[0.04] to-white';

    $currentStepBadgeLabel = $currentStepKey === 'complete' ? __('Completed') : __('Current step');
    $currentStepButtonTone = $currentStepKey === 'complete'
        ? 'bg-emerald-600 hover:bg-emerald-700'
        : 'bg-[#240B36] hover:bg-[#34104d]';

    $stepCompletionMessages = [
        'edit_homepage' => __('Homepage completed'),
        'manage_pages' => __('Pages step completed'),
        'connect_domain' => __('Domain connected'),
    ];

    $completedMilestones = $onboardingSteps
        ->filter(fn (array $step) => $step['completed'])
        ->map(fn (array $step) => [
            'key' => $step['key'],
            'message' => $stepCompletionMessages[$step['key']] ?? __($step['label']),
        ])
        ->values();

    $latestCompletedMilestone = $completedMilestones->last();

    $currentStepMomentum = match ($currentStepKey) {
        'manage_pages' => $latestCompletedMilestone
            ? __('Nice progress. :message. Next, review the rest of your pages so visitors can explore more of your site.', ['message' => $latestCompletedMilestone['message']])
            : __('Nice progress. Next, review the rest of your pages so visitors can explore more of your site.'),
        'connect_domain' => $latestCompletedMilestone
            ? __('Nice progress. :message. Next, connect your domain when you are ready to share your branded address.', ['message' => $latestCompletedMilestone['message']])
            : __('Nice progress. Next, connect your domain when you are ready to share your branded address.'),
        'complete' => __('Homepage completed, pages reviewed, and domain connected. Your site is ready to share.'),
        default => __('Once your homepage feels right, the next step is reviewing the rest of your pages.'),
    };
@endphp

<x-client-layout title="Site dashboard">
    @component('client.site.layouts.site-layout', [
        'subscription' => $subscription,
        'siteName' => $siteName,
        'siteUrl' => $siteUrl,
        'showTopbar' => false,
    ])
        <div class="space-y-10">
            <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-[#f5eef9] p-7 shadow-sm sm:p-10">
                <div class="max-w-3xl space-y-6 text-start">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] {{ $heroStatus['tone'] }}">
                            {{ __($heroStatus['label']) }}
                        </span>

                        @if ($displayHost)
                            <span class="text-sm text-slate-500">{{ $displayHost }}</span>
                        @endif
                    </div>

                    <div class="space-y-3">
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Site dashboard') }}</p>
                        <h1 class="text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl">
                            {{ $siteName }}
                        </h1>
                        <p class="max-w-2xl text-base leading-7 text-slate-600">
                            {{ __('Your website is ready. Start with your homepage to make it feel like your business, then keep refining from there.') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ $currentStepUrl }}"
                            class="inline-flex items-center justify-center rounded-2xl bg-[#240B36] px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#34104d]">
                            {{ $heroPrimaryLabel }}
                        </a>
                        <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            {{ __('Preview') }}
                        </a>
                    </div>

                    <div class="flex flex-wrap gap-6 text-sm text-slate-500">
                        <span>{{ __('Template') }}: <span class="font-medium text-slate-700">{{ $templateName }}</span></span>
                        <span>{{ __('Status') }}: <span class="font-medium text-slate-700">{{ $provisioningText }}</span></span>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(18rem,0.65fr)]">
                <section class="rounded-[2rem] border p-7 shadow-sm sm:p-8 {{ $currentCardTone }}">
                    <div class="max-w-2xl space-y-5 text-start">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500 rtl:flex-row-reverse">
                                <span>
                                    {{ $currentStepKey === 'complete'
                                        ? __(':completed of :total completed', ['completed' => $completedSteps, 'total' => $totalSteps])
                                        : __('Step :step of :total', ['step' => $currentStepPosition, 'total' => $totalSteps]) }}
                                </span>
                                <span>{{ $progressPercent }}%</span>
                                <div class="flex items-center gap-1.5 rtl:flex-row-reverse" aria-hidden="true">
                                    @foreach ($onboardingSteps as $step)
                                        <span
                                            class="h-2 w-2 rounded-full {{ $step['completed'] ? 'bg-emerald-500' : ($step['active'] ? 'bg-[#240B36]' : 'bg-slate-300') }}">
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs font-medium rtl:flex-row-reverse">
                                @foreach ($onboardingSteps as $step)
                                    <span
                                        class="inline-flex items-center rounded-full border px-3 py-1 {{ $step['completed']
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : ($step['active']
                                                ? 'border-[#240B36]/10 bg-white text-[#240B36]'
                                                : 'border-slate-200 bg-white text-slate-500') }}">
                                        {{ __(':step. :label', ['step' => $loop->iteration, 'label' => __($step['label'])]) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        @if ($completedMilestones->isNotEmpty())
                            <div class="rounded-2xl border border-emerald-200/80 bg-white/80 p-4 shadow-sm">
                                <div class="flex flex-wrap items-center gap-2 text-start rtl:flex-row-reverse">
                                    <span class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">
                                        {{ __('Completed so far') }}
                                    </span>

                                    @foreach ($completedMilestones as $milestone)
                                        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 rtl:flex-row-reverse">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.72a.75.75 0 0 0-1.06-1.06L9.25 10.69 7.78 9.22a.75.75 0 0 0-1.06 1.06l2 2a.75.75 0 0 0 1.06 0l4-4Z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ $milestone['message'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">{{ $currentStepBadgeLabel }}</p>
                        <div class="space-y-3">
                            <h2 class="text-2xl font-semibold text-slate-950">{{ $currentStepTitle }}</h2>
                            <p class="text-sm font-medium leading-6 text-slate-700">
                                {{ $currentStepMomentum }}
                            </p>
                            <p class="text-sm leading-7 text-slate-600">
                                {{ $currentStepHelper }}
                            </p>
                        </div>

                        <div class="pt-2">
                            <a href="{{ $currentStepUrl }}"
                                class="inline-flex items-center justify-center rounded-2xl px-5 py-3 text-sm font-semibold text-white shadow-sm transition {{ $currentStepButtonTone }}">
                                {{ $currentStepButtonLabel }}
                            </a>
                            <p class="mt-3 text-xs text-slate-500">
                                {{ $currentStepHint }}
                            </p>
                        </div>
                    </div>
                </section>

                <aside class="rounded-[2rem] border border-slate-200 bg-slate-50 p-6 shadow-sm">
                    <div class="space-y-4 text-start">
                        <div class="space-y-2">
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-400">{{ __('Domain') }}</p>
                            <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $domainTone }}">
                                {{ $domainLabel }}
                            </span>
                        </div>

                        <div class="space-y-2">
                            <h3 class="text-lg font-semibold text-slate-950">{{ __('Your site is live') }}</h3>
                            <p class="text-sm leading-6 text-slate-600">{{ $domainSummary }}</p>
                            @if ($displayHost)
                                <p class="text-sm font-medium text-slate-700">{{ $displayHost }}</p>
                            @endif
                        </div>

                        <div class="pt-1">
                            <a href="{{ $domainsUrl }}"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                {{ __('Use your own domain') }}
                            </a>
                        </div>
                    </div>
                </aside>
            </div>

            <section class="border-t border-slate-200 pt-6">
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ $pagesUrl }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        {{ __('Manage pages') }}
                    </a>
                    <a href="{{ $domainsUrl }}"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        {{ __('Connect domain') }}
                    </a>
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                        {{ __('View website') }}
                    </a>
                </div>
            </section>
        </div>
    @endcomponent
</x-client-layout>
