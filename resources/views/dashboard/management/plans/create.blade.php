<x-dashboard-layout>
  <!-- [ breadcrumb ] start -->
  <div class="page-header">
    <div class="page-block">
      <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('dashboard.plans.index') }}">Plans</a></li>
        <li class="breadcrumb-item" aria-current="page">Add Plan</li>
      </ul>
      <div class="page-header-title">
        <h2 class="mb-0">Add Hosting Plan</h2>
      </div>
    </div>
  </div>
  <!-- [ breadcrumb ] end -->

  <div class="grid grid-cols-12 gap-x-6">
    <div class="col-span-12">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Basic Information</h5>
        </div>

        <div class="card-body">
          {{-- Success --}}
          @if (session('ok'))
            <div class="alert alert-success">{{ session('ok') }}</div>
          @endif

          {{-- Errors --}}
          @if ($errors->any())
            <div class="alert alert-danger mb-4">
              <ul class="list-disc pr-5">
                @foreach ($errors->all() as $e)
                  <li>{{ $e }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form id="planForm" action="{{ route('dashboard.plans.store') }}" method="POST" class="grid grid-cols-12 gap-x-6 gap-y-4">
            @csrf


            <!-- Plan Name & Slug -->
            <div class="col-span-12 md:col-span-6">
              <label class="form-label">Plan Name *</label>
              <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
              @error('name') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-span-12 md:col-span-6">
              <label class="form-label">Plan Slug <span class="text-gray-400">(optional)</span></label>
              <input type="text" name="slug" id="slug" class="form-control" value="{{ old('slug') }}" placeholder="auto-generated if empty">
              @error('slug') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>


            <!-- Pricing -->
            <div class="col-span-12 md:col-span-6">
              <label class="form-label">Monthly Price (USD)</label>
              <div class="flex">
                <span class="inline-flex items-center px-3 rounded-s-xl border border-e-0 bg-gray-50">$</span>
                <input type="number" step="0.01" min="0" id="monthly_price_ui" name="monthly_price_ui" class="form-control rounded-s-none" value="{{ old('monthly_price_ui') ?? '' }}" placeholder="0.00">
              </div>
              <input type="hidden" name="monthly_price_cents" id="monthly_price_cents" value="{{ old('monthly_price_cents') }}">
              @error('monthly_price_cents') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="col-span-12 md:col-span-6">
              <label class="form-label">Annual Price (USD)</label>
              <div class="flex">
                <span class="inline-flex items-center px-3 rounded-s-xl border border-e-0 bg-gray-50">$</span>
                <input type="number" step="0.01" min="0" id="annual_price_ui" name="annual_price_ui" class="form-control rounded-s-none" value="{{ old('annual_price_ui') ?? '' }}" placeholder="0.00">
              </div>
              <input type="hidden" name="annual_price_cents" id="annual_price_cents" value="{{ old('annual_price_cents') }}">
              @error('annual_price_cents') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>





            <!-- Features -->
            <div class="col-span-12">
              <label class="form-label">Features (press Enter to add)</label>
              <div class="flex gap-2">
                <input id="featureInput" type="text" class="form-control" placeholder="e.g. 10GB SSD, Free SSL, Email accounts">
                <button type="button" id="addFeatureBtn" class="btn btn-secondary">Add</button>
              </div>
              <div id="featuresChips" class="mt-3 flex flex-wrap gap-2">
                @php $oldFeatures = is_array(old('features')) ? old('features') : []; @endphp
                @foreach($oldFeatures as $f)
                  <span class="badge bg-success-500/10 text-success-700 rounded-full px-3 py-1 flex items-center gap-2">
                    <span>{{ $f }}</span>
                    <button type="button" class="text-red-600 remove-chip" data-value="{{ $f }}">✕</button>
                    <input type="hidden" name="features[]" value="{{ $f }}">
                  </span>
                @endforeach
              </div>
              @error('features') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Active -->
            <div class="col-span-12 md:col-span-6">
              <label class="form-label">Status</label>
              <label class="inline-flex items-center gap-2 mt-2">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active',true))>
                <span>Active (available to sell)</span>
              </label>
              @error('is_active') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Actions -->
            <div class="col-span-12 flex items-center justify-end gap-3 mt-4">
              <a href="{{ route('dashboard.plans.index') }}" class="btn btn-light">Cancel</a>
              <button type="submit" class="btn btn-primary">Create Plan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- Minimal helpers --}}

<script>

  // السعر الشهري والسنوي
  const monthlyUI = document.getElementById('monthly_price_ui');
  const monthlyCents = document.getElementById('monthly_price_cents');
  const annualUI = document.getElementById('annual_price_ui');
  const annualCents = document.getElementById('annual_price_cents');
  function syncCents() {
    const m = parseFloat(monthlyUI?.value || '0');
    monthlyCents.value = isNaN(m) ? '' : Math.round(m * 100);
    const a = parseFloat(annualUI?.value || '0');
    annualCents.value = isNaN(a) ? '' : Math.round(a * 100);
  }
  monthlyUI?.addEventListener('input', syncCents);
  annualUI?.addEventListener('input', syncCents);
  document.getElementById('planForm')?.addEventListener('submit', ()=> { syncCents(); });
  document.addEventListener('DOMContentLoaded', syncCents);

  // الميزات
  const featureInput = document.getElementById('featureInput');
  const addFeatureBtn = document.getElementById('addFeatureBtn');
  const featuresChips = document.getElementById('featuresChips');

  function addFeature(value) {
    value = value.trim();
    if (!value) return;
    // تحقق من التكرار
    if ([...featuresChips.querySelectorAll('input[name="features[]"]')].some(i => i.value === value)) return;
    // عنصر الشيب
    const span = document.createElement('span');
    span.className = 'badge bg-success-500/10 text-success-700 rounded-full px-3 py-1 flex items-center gap-2';
    span.innerHTML = `<span>${value}</span>
      <button type="button" class="text-red-600 remove-chip" data-value="${value}">✕</button>
      <input type="hidden" name="features[]" value="${value}">`;
    featuresChips.appendChild(span);
    featureInput.value = '';
    featureInput.focus();
  }

  addFeatureBtn?.addEventListener('click', () => {
    addFeature(featureInput.value);
  });
  featureInput?.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addFeature(featureInput.value);
    }
  });
  featuresChips?.addEventListener('click', e => {
    if (e.target.classList.contains('remove-chip')) {
      e.target.closest('span').remove();
    }
  });
</script>

</x-dashboard-layout>
