<x-dashboard-layout>
    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'Home') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.coupons.index') }}">{{ t('dashboard.Coupons', 'الكوبونات') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Edit_Coupon', 'تعديل الكوبون') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">
                    {{ t('dashboard.Edit_Coupon', 'تعديل الكوبون') }}:
                    <span class="font-mono text-primary">{{ $coupon->code }}</span>
                </h2>
            </div>
        </div>
    </div>

    <form action="{{ route('dashboard.coupons.update', $coupon) }}" method="POST">
        @csrf
        @method('PUT')
        @php $isEdit = true; @endphp
        @include('dashboard.management.coupons._form', ['coupon' => $coupon, 'isEdit' => $isEdit])
    </form>
</x-dashboard-layout>
