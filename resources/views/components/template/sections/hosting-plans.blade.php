@props(['plans' => collect(), 'title' => '', 'subtitle' => '', 'category' => null])

<form id="plansForm" class="group/tiers bg-background py-24 sm:py-32" dir="rtl" action="/subscribe" method="POST">
  @csrf

  <div class="mx-auto max-w-7xl px-6 lg:px-8">

    {{-- Header: show category title/description if provided, otherwise section title/subtitle --}}
    @php
      $headerTitle = $title ?: 'خططنا';
      $headerSubtitle = $subtitle ?: 'التسعير الذي ينمو معك';
      if ($category) {
          $catLabel = $category->translation()?->title
                    ?? $category->translations->first()?->title
                    ?? ('Category #' . $category->id);
          $catDesc = $category->translation()?->description
                   ?? $category->translations->first()?->description
                   ?? null;
      }
    @endphp

    <div class="mx-auto max-w-4xl text-center">
      @if(isset($category) && $category)
        <h2 class="text-base/7 font-semibold text-secondary">{{ $catLabel }}</h2>
        @if($catDesc)
          <p class="text-title-h2 text-primary font-extrabold mb-4">{{ $catDesc }}</p>
        @else
          <p class="text-title-h2 text-primary font-extrabold mb-4">{{ $headerSubtitle }}</p>
        @endif
      @else
        <h2 class="text-base/7 font-semibold text-secondary">{{ $headerTitle }}</h2>
        <p class="text-title-h2 text-primary font-extrabold mb-4">{{ $headerSubtitle }}</p>
      @endif
    </div>

    {{-- optional explanation line (only when no category description and no explicit subtitle) --}}
    @if(!$category && $subtitle)
      <p class="mx-auto mt-6 max-w-2xl text-center text-base font-medium text-pretty text-gray-600 sm:text-xl/8">
        {{ $subtitle }}
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
              <span class="text-gray-500 group-has-checked:text-white">شهريًا</span>
            </label>
            <label class="group relative rounded-full px-3 py-1 has-checked:bg-primary">
              <input type="radio" name="frequency" value="annually"
                class="absolute inset-0 appearance-none rounded-full" />
              <span class="text-gray-500 group-has-checked:text-white">سنويًا</span>
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
          $features = is_array($t?->features) ? $t->features : [];
          $monthlyC = $plan->monthly_price_cents;
          $annualC = $plan->annual_price_cents;
          // featured flag (customize per your model)
          $featured = $plan->is_featured ?? false;
        @endphp

        <div class="group/tier rounded-3xl p-8 ring-1 ring-primary xl:p-10 bg-white relative {{ $featured ? 'scale-105 ring-2 shadow-xl' : '' }}" data-plan-id="{{ $plan->id }}">
          @if ($featured)
            <div class="absolute -top-4 -start-4 bg-primary text-white text-xs font-bold px-3 py-1 rounded-full shadow">الأكثر شهرة</div>
          @endif

          <h3 id="tier-{{ $plan->id }}" class="text-lg/8 font-semibold {{ $featured ? 'text-primary' : 'text-gray-900' }}">{{ $planTitle }}</h3>
          <p class="mt-4 text-sm/6 text-gray-600">{{ $planDesc }}</p>

          <!-- Prices (toggled via JS) -->
          <p class="mt-6 flex items-baseline gap-x-1 price-monthly" data-cents="{{ $monthlyC }}">
            @if($monthlyC !== null)
              <span class="text-4xl font-semibold tracking-tight text-gray-900">{{ number_format($monthlyC / 100, 2) }}$</span>
              <span class="text-sm/6 font-semibold text-gray-600">/ شهريًا</span>
            @else
              <span class="text-gray-400">—</span>
            @endif
          </p>

          <p class="mt-6 flex items-baseline gap-x-1 price-annual hidden" data-cents="{{ $annualC }}">
            @if($annualC !== null)
              <span class="text-4xl font-semibold tracking-tight text-gray-900">{{ number_format($annualC / 100, 2) }}$</span>
              <span class="text-sm/6 font-semibold text-gray-600">/ سنويًا</span>
            @else
              <span class="text-gray-400">—</span>
            @endif
          </p>

          <button type="submit" name="plan_id" value="{{ $plan->id }}" aria-describedby="tier-{{ $plan->id }}"
            class="mt-6 block w-full rounded-md px-3 py-2 text-center text-sm/6 font-semibold text-white bg-primary/90 hover:bg-primary/90 shadow-md transition">
            اشترك الآن
          </button>

          <ul role="list" class="mt-8 space-y-3 text-sm/6 text-gray-600 xl:mt-10 text-right">
            @forelse ($features as $feat)
              <li class="flex items-center gap-x-3">✅ {{ $feat }}</li>
            @empty
              <li class="text-gray-400">—</li>
            @endforelse
          </ul>
        </div>
      @empty
        <div class="col-span-3 text-center text-gray-500">
          لا توجد خطط متاحة حالياً.
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

    function showMonthly() {
      monthlyEls.forEach(el => el.classList.remove('hidden'));
      annualEls.forEach(el => el.classList.add('hidden'));
    }
    function showAnnual() {
      monthlyEls.forEach(el => el.classList.add('hidden'));
      annualEls.forEach(el => el.classList.remove('hidden'));
    }

    // init based on checked
    const checked = form.querySelector('input[name="frequency"]:checked')?.value || 'monthly';
    if (checked === 'annually') showAnnual(); else showMonthly();

    // listen changes
    freqRadios.forEach(r => r.addEventListener('change', function () {
      if (this.value === 'annually') showAnnual(); else showMonthly();
    }));

    // optional: intercept submit to include frequency explicitly (already included by radio)
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      const btn = e.submitter;
      const planId = btn?.value;
      const planCard = btn?.closest('[data-plan-id]');
      if (planCard) {
        const isActive = true; // if you store active flag per plan card, you can check it here
        if (!isActive) {
          e.preventDefault();
          alert('هذه الخطة غير متاحة حالياً.');
        }
        window.location.href = '/checkout/cart?plan_id=' + planId;
      }
    });
  })();
</script>
