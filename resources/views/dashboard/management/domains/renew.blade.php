<x-dashboard-layout>
    <div class="container mx-auto py-6 max-w-3xl space-y-6">
        <div>
            <a href="{{ route('dashboard.domains.index') }}"
                class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition">
                <i class="ti ti-arrow-left me-1"></i>
                {{ __('Back to domains') }}
            </a>
        </div>

        <div>
            <h1 class="text-2xl font-bold mb-2">{{ __('تجديد النطاق') }}: {{ $domain->domain_name }}</h1>
        </div>

        @if (session('success'))
            <div class="bg-green-100 text-green-800 p-4 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-4 rounded" role="alert" aria-live="polite">
                <ul class="ps-5 list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- TLD-3E.3A — Replace Admin Renew Placeholder with Trusted Renewal Invoice Flow:
             this is a confirmation-only summary. No editable renewal_date / status /
             payment_method / notes fields exist anymore, and no provider picker is offered
             here — the managed provider identity below comes only from Domain.provider_id. --}}
        <section class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-lg font-semibold">{{ __('تفاصيل التجديد الحالية') }}</h2>
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
                <div>
                    <dt class="text-gray-500">{{ __('Current renewal date') }}</dt>
                    <dd class="font-medium">{{ $currentRenewal ?? __('غير محدد') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">{{ __('المزوّد المُدار') }}</dt>
                    <dd class="font-medium">
                        @if ($provider)
                            {{ $provider->name ?: ucfirst($provider->type) }} — {{ ucfirst($provider->type) }} — {{ ucfirst($provider->mode) }}
                        @else
                            {{ __('غير مُدار (لا يوجد مزوّد مرتبط)') }}
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        @if ($provider)
            <form action="{{ route('dashboard.domains.renew.update', $domain->id) }}" method="POST"
                class="bg-white rounded-lg shadow p-6 space-y-4">
                @csrf
                @method('PUT')

                <p class="text-sm text-gray-600">
                    {{ __('سيتم إنشاء فاتورة تجديد أو استخدام الفاتورة المعلقة الحالية. لن يتم تنفيذ التجديد لدى المزوّد قبل تسوية الفاتورة.') }}
                </p>

                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-end">
                    <a href="{{ route('dashboard.domains.index') }}" class="btn btn-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ $hasPendingInvoice ? __('متابعة تجديد النطاق') : __('إنشاء فاتورة التجديد') }}
                    </button>
                </div>
            </form>
        @else
            {{-- TLD-3E.3A section 3 — external/unmanaged domain: the managed-renewal action is
                 removed entirely rather than disabled, and no provider selector is offered. --}}
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded text-sm">
                {{ __('لا يمكن تجديد هذا النطاق عبر المنصة لأنه غير مرتبط بمزوّد مُدار.') }}
            </div>
        @endif
    </div>
</x-dashboard-layout>
