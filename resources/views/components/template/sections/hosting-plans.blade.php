@props([
    'plans' => collect(),
    'title' => '',
    'subtitle' => '',
    'category' => null,
])

<form id="plansForm" class="group/tiers bg-background py-24 sm:py-32"  action="/subscribe" method="POST">
  @csrf

  <div class="mx-auto max-w-7xl px-6 lg:px-8">

    {{-- Header: show category title/description if provided, otherwise section title/subtitle --}}
    @php
      $titleText = trim((string) $title);
      $subtitleText = trim((string) $subtitle);

      $categoryLabel = '';
      $categoryDesc = '';

      if ($category) {
          $categoryLabel = $category->translation()?->title
                    ?? $category->translations->first()?->title
                    ?? ('Category #' . $category->id);
          $categoryDesc = $category->translation()?->description
                   ?? $category->translations->first()?->description
                   ?? null;
      }

      $categoryLabel = trim((string) $categoryLabel);
      $categoryDesc = trim((string) $categoryDesc);

      $fallbackTitle = __('Hosting Plans');
      $fallbackSubtitle = __('Choose the plan that fits your needs');

      $displayEyebrow = $categoryLabel !== '' ? $categoryLabel : '';
      $displayDescription = $subtitleText !== '' ? $subtitleText : $fallbackSubtitle;
      $displayTitle = $titleText !== '' ? $titleText : $fallbackTitle;

    @endphp

    <div class="mx-auto max-w-4xl text-center">
      @if ($displayEyebrow !== '')
        <h2 class="text-base/7 font-semibold text-secondary">{{ $displayEyebrow }}</h2>
      @endif
      <p class="text-title-h2 text-primary font-extrabold mb-4">{{ $displayTitle }}</p>
    </div>

    @if ($displayDescription !== '')
      <p class="mx-auto mt-6 max-w-2xl text-center text-base font-medium text-pretty text-gray-600 sm:text-xl/8">
        {{ $displayDescription }}
      </p>
    @endif

      <!-- Toggle Payment Type -->
      <div class="mt-16 flex justify-center">
        <fieldset aria-label="طريقة الدفع">
          <div
            class="grid grid-cols-2 gap-x-1 rounded-full p-1 text-center text-xs/5 font-semibold ring-1 ring-gray-200 ring-inset">
            <label class="group relative rounded-full px-3 py-1 has-checked:bg-primary">
              <input type="radio" name="frequency" value="monthly" checked
                class="absolute inset-0 appearance-none rounded-full" />
              <span class="text-gray-500 group-has-checked:text-white">{{ t('frontend.monthly', 'monthly') }}</span>
            </label>
            <label class="group relative rounded-full px-3 py-1 has-checked:bg-primary">
              <input type="radio" name="frequency" value="annually"
                class="absolute inset-0 appearance-none rounded-full" />
              <span class="text-gray-500 group-has-checked:text-white">{{ t('frontend.annually', 'annually') }}</span>
            </label>
          </div>
        </fieldset>
      </div>

    <!-- Plans Grid -->
    <div class="isolate mx-auto mt-10 grid max-w-md grid-cols-1 gap-8 lg:mx-0 lg:max-w-none lg:grid-cols-3">

      @forelse ($plans as $plan)
        @php
          $t = $plan->translation(); // translation for current locale or fallback
          $planTitle = $t?->title ?? $plan->slug;
          $planDesc = $t?->description ?? ($plan->name ?? '');
          $rawFeatures = is_array($t?->features) ? $t->features : [];
          $normalizeFeatures = function ($items) {
              return collect(is_array($items) ? $items : [])
                  ->map(function ($item) {
                      if (is_array($item)) {
                          $text = isset($item['text']) ? trim((string) $item['text']) : '';
                          $available = array_key_exists('available', $item)
                              ? filter_var($item['available'], FILTER_VALIDATE_BOOLEAN)
                              : true;
                      } else {
                          $text = trim((string) $item);
                          $available = true;
                      }

                      return [
                          'text' => $text,
                          'available' => (bool) $available,
                      ];
                  })
                  ->filter(fn ($feature) => trim((string) ($feature['text'] ?? '')) !== '')
                  ->values();
          };
          $hasBillingBuckets = is_array($rawFeatures) && (array_key_exists('monthly', $rawFeatures) || array_key_exists('annual', $rawFeatures));
          $featuresMonthly = $normalizeFeatures($hasBillingBuckets ? ($rawFeatures['monthly'] ?? []) : $rawFeatures);
          $featuresAnnual = $normalizeFeatures($hasBillingBuckets ? ($rawFeatures['annual'] ?? []) : ($hasBillingBuckets ? [] : $rawFeatures));
          if (!$hasBillingBuckets) {
              $featuresAnnual = $featuresMonthly;
          }
          $monthlyC = $plan->monthly_price_cents;
          $annualC = $plan->annual_price_cents;
          $featured = (bool) ($plan->is_featured ?? false);
          $featuredLabel = $t?->featured_label ?? $plan->featured_label ?? __('Most Popular');
        @endphp

        <div
          class="group/tier rounded-3xl p-8 ring-1 ring-primary xl:p-10 bg-white relative {{ $featured ? 'scale-105 ring-2 shadow-xl' : '' }}"
          data-plan-id="{{ $plan->id }}"
          data-plan-monthly="{{ $monthlyC !== null ? 'true' : 'false' }}"
          data-plan-annual="{{ $annualC !== null ? 'true' : 'false' }}"
        >
          @if ($featured)
            <div class="absolute -top-4 -start-4 flex items-center gap-1 rounded-full bg-primary text-white text-xs font-bold px-3 py-1 shadow">
              <i class="ti ti-star-filled text-sm"></i>
              {{ $featuredLabel }}
            </div>
          @endif

          <h3 id="tier-{{ $plan->id }}" class="text-lg/8 font-semibold {{ $featured ? 'text-primary' : 'text-gray-900' }}">{{ $planTitle }}</h3>
          <p class="mt-4 text-sm/6 text-gray-600">{{ $planDesc }}</p>

          <!-- Prices (toggled via JS) -->
          <p class="mt-6 flex items-baseline gap-x-1 price-monthly" data-cents="{{ $monthlyC }}">
            @if($monthlyC !== null)
              <span class="text-4xl font-semibold tracking-tight text-gray-900">{{ number_format($monthlyC / 100, 2) }}$</span>
              <span class="text-sm/6 font-semibold text-gray-600">/ month</span>
            @else
              <span class="text-gray-400">—</span>
            @endif
          </p>

          <p class="mt-6 flex items-baseline gap-x-1 price-annual hidden" data-cents="{{ $annualC }}">
            @if($annualC !== null)
              <span class="text-4xl font-semibold tracking-tight text-gray-900">{{ number_format($annualC / 100, 2) }}$</span>
              <span class="text-sm/6 font-semibold text-gray-600">/ year</span>
            @else
              <span class="text-gray-400">—</span>
            @endif
          </p>

          <button type="submit" name="plan_id" value="{{ $plan->id }}" aria-describedby="tier-{{ $plan->id }}"
            class="mt-6 block w-full rounded-md px-3 py-2 text-center text-sm/6 font-semibold text-white bg-primary/90 hover:bg-primary/90 shadow-md transition">
            {{ __("Choose Plan") }}
          </button>

          <ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-600 xl:mt-10 text-right" data-feature-frequency="monthly">
            @forelse ($featuresMonthly as $feat)
              <li class="flex items-center gap-x-3">
                <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $feat['available'] ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-500' }}">
                  <i class="ti {{ $feat['available'] ? 'ti-circle-check-filled' : 'ti-circle-x-filled' }}"></i>
                </span>
                <span class="{{ $feat['available'] ? 'text-gray-700' : 'text-gray-400 line-through' }}">
                  {{ $feat['text'] }}
                </span>
              </li>
            @empty
              <li class="text-gray-400">&mdash;</li>
            @endforelse
          </ul>

          <ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-600 xl:mt-10 text-right hidden" data-feature-frequency="annual">
            @forelse ($featuresAnnual as $feat)
              <li class="flex items-center gap-x-3">
                <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $feat['available'] ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-200 text-gray-500' }}">
                  <i class="ti {{ $feat['available'] ? 'ti-circle-check-filled' : 'ti-circle-x-filled' }}"></i>
                </span>
                <span class="{{ $feat['available'] ? 'text-gray-700' : 'text-gray-400 line-through' }}">
                  {{ $feat['text'] }}
                </span>
              </li>
            @empty
              <li class="text-gray-400">&mdash;</li>
            @endforelse
          </ul>
        </div>
      @empty
        <div class="col-span-3 text-center text-gray-500">
          {{ __("No plans are currently available.") }}
        </div>
      @endforelse

    </div>
  </div>
</form>

<script>
  (function () {
    const form = document.getElementById('plansForm');
    if (!form) return;

    const freqRadios = Array.from(form.querySelectorAll('input[name="frequency"]'));
    const monthlyEls = Array.from(form.querySelectorAll('.price-monthly'));
    const annualEls = Array.from(form.querySelectorAll('.price-annual'));
    const featureMonthlyLists = Array.from(form.querySelectorAll('[data-feature-frequency="monthly"]'));
    const featureAnnualLists = Array.from(form.querySelectorAll('[data-feature-frequency="annual"]'));

    const getSelectedFrequency = () => form.querySelector('input[name="frequency"]:checked')?.value || 'monthly';

    function showMonthly() {
      monthlyEls.forEach(el => el.classList.remove('hidden'));
      annualEls.forEach(el => el.classList.add('hidden'));
      featureMonthlyLists.forEach(el => el.classList.remove('hidden'));
      featureAnnualLists.forEach(el => el.classList.add('hidden'));
    }
    function showAnnual() {
      monthlyEls.forEach(el => el.classList.add('hidden'));
      annualEls.forEach(el => el.classList.remove('hidden'));
      featureMonthlyLists.forEach(el => el.classList.add('hidden'));
      featureAnnualLists.forEach(el => el.classList.remove('hidden'));
    }

    // init based on checked
    const checked = getSelectedFrequency();
    if (checked === 'annually') showAnnual(); else showMonthly();

    // listen changes
    freqRadios.forEach(r => r.addEventListener('change', function () {
      if (this.value === 'annually') showAnnual(); else showMonthly();
    }));

    // pass the selected frequency when the user submits the plan
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const btn = e.submitter;
      const planId = btn?.value;
      const planCard = btn?.closest('[data-plan-id]');
      if (!planId) return;
      if (!planCard) return;

      const selectedFrequency = getSelectedFrequency();
      const hasMonthly = planCard.dataset.planMonthly === 'true';
      const hasAnnual = planCard.dataset.planAnnual === 'true';

      if ((selectedFrequency === 'monthly' && !hasMonthly) || (selectedFrequency === 'annually' && !hasAnnual)) {
        alert(selectedFrequency === 'annually'
          ? '{{ __("This plan does not offer annual billing.") }}'
          : '{{ __("This plan does not offer monthly billing.") }}');
        return;
      }

      window.location.href = '/checkout/cart?plan_id=' + planId + '&plan_sub_type=' + selectedFrequency;
    });
  })();
</script>
