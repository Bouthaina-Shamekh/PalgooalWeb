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
                <li class="breadcrumb-item" aria-current="page">{{ t('dashboard.Add_Coupon', 'إضافة كوبون') }}</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ t('dashboard.Add_New_Coupon', 'إضافة كوبون جديد') }}</h2>
            </div>
        </div>
    </div>

    <form action="{{ route('dashboard.coupons.store') }}" method="POST">
        @csrf
        @php $coupon = new \App\Models\Coupon(); $isEdit = false; @endphp
        @include('dashboard.management.coupons._form', ['coupon' => $coupon, 'isEdit' => $isEdit])
    </form>
</x-dashboard-layout>
