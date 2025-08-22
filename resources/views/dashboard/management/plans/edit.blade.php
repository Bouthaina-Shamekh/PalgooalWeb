<x-dashboard-layout>
	<!-- [ breadcrumb ] start -->
	<div class="page-header">
		<div class="page-block">
			<ul class="breadcrumb">
				<li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">Home</a></li>
				<li class="breadcrumb-item"><a href="{{ route('dashboard.plans.index') }}">Plans</a></li>
				<li class="breadcrumb-item" aria-current="page">Edit Plan</li>
			</ul>
			<div class="page-header-title">
				<h2 class="mb-0">Edit Hosting Plan</h2>
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

					<form id="planForm" action="{{ route('dashboard.plans.update', $plan->id) }}" method="POST" class="grid grid-cols-12 gap-x-6 gap-y-4">
						@csrf
						@method('PUT')

						<!-- Name -->
						<div class="col-span-12 md:col-span-6">
							<label class="form-label">Name *</label>
							<input
								type="text"
								name="name"
								id="name"
								class="form-control"
								value="{{ old('name', $plan->name) }}"
								required>
							@error('name') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
						</div>

						<!-- Slug (optional) -->
						<div class="col-span-12 md:col-span-6">
							<label class="form-label">Slug (optional)</label>
							<input
								type="text"
								name="slug"
								id="slug"
								class="form-control"
								value="{{ old('slug', $plan->slug) }}"
								placeholder="auto-generated if empty">
							@error('slug') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
						</div>

						<!-- Billing Cycle -->
						<div class="col-span-12 md:col-span-6">
							<label class="form-label">Billing Cycle *</label>
							<div class="flex items-center gap-4 mt-2">
								<label class="inline-flex items-center gap-2">
									<input
										type="radio"
										name="billing_cycle"
										value="monthly"
										@checked(old('billing_cycle', $plan->billing_cycle)==='monthly')
										required>
									<span>Monthly</span>
								</label>
								<label class="inline-flex items-center gap-2">
									<input
										type="radio"
										name="billing_cycle"
										value="annually"
										@checked(old('billing_cycle', $plan->billing_cycle)==='annually')>
									<span>Annually</span>
								</label>
							</div>
							@error('billing_cycle') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
						</div>

						<!-- Price (UI in dollars) -->
						<div class="col-span-12 md:col-span-6">
							<label class="form-label">Price (USD) *</label>
							<div class="flex">
								<span class="inline-flex items-center px-3 rounded-s-xl border border-e-0 bg-gray-50">$</span>
								<input
									type="number" step="0.01" min="0"
									id="price_ui" name="price_ui"
									class="form-control rounded-s-none"
									value="{{ old('price_ui', number_format(($plan->price_cents ?? 0)/100, 2, '.', '')) }}" placeholder="0.00" required
								>
							</div>
							<input type="hidden" name="price_cents" id="price_cents" value="{{ old('price_cents', $plan->price_cents) }}">
							@error('price_cents') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
						</div>

						<!-- Features -->
						<div class="col-span-12">
							<label class="form-label">Features (press Enter to add)</label>
							<div class="flex gap-2">
								<input id="featureInput" type="text" class="form-control" placeholder="e.g. 10GB SSD, Free SSL, Email accounts">
								<button type="button" id="addFeatureBtn" class="btn btn-secondary">Add</button>
							</div>
							<div id="featuresChips" class="mt-3 flex flex-wrap gap-2">
								@php
									$oldFeatures = is_array(old('features')) ? old('features') : (is_array($plan->features) ? $plan->features : []);
								@endphp
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
								<input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))>
								<span>Active (available to sell)</span>
							</label>
							@error('is_active') <div class="text-red-600 text-sm mt-1">{{ $message }}</div> @enderror
						</div>

						<!-- Actions -->
						<div class="col-span-12 flex items-center justify-end gap-3 mt-4">
							<a href="{{ route('dashboard.plans.index') }}" class="btn btn-light">Cancel</a>
							<button type="submit" class="btn btn-primary">Update Plan</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	{{-- Minimal helpers --}}
<script>
	const priceUI = document.getElementById('price_ui');
	const priceCents = document.getElementById('price_cents');
	function syncCents(){
		const v = parseFloat(priceUI?.value || '0');
		priceCents.value = isNaN(v) ? '' : Math.round(v * 100);
	}
	priceUI?.addEventListener('input', syncCents);
	document.getElementById('planForm')?.addEventListener('submit', ()=> { syncCents(); });
	document.addEventListener('DOMContentLoaded', syncCents);
</script>

</x-dashboard-layout>