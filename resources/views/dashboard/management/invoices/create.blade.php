<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.invoices.index') }}">الفواتير</a></li>
                <li class="breadcrumb-item" aria-current="page">إنشاء فاتورة</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">إنشاء فاتورة جديدة</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">بيانات الفاتورة</h5>
                </div>
                <div class="card-body">
                    @if(session('ok'))
                        <div class="alert alert-success" role="alert">
                            {{ session('ok') }}
                        </div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('dashboard.invoices.store') }}" method="POST" class="grid grid-cols-12 gap-x-6">
                        @csrf
                        @include('dashboard.management.invoices._form')
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
</x-dashboard-layout>
