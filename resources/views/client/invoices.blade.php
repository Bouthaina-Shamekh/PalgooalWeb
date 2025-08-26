<x-client-layout>
<div class="page-header">
    <div class="page-block">
        <ul class="breadcrumb">
            <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
            <li class="breadcrumb-item" aria-current="page">الفواتير</li>
        </ul>
        <div class="page-header-title">
            <h2 class="mb-0">قائمة فواتيرك</h2>
        </div>
    </div>
</div>
<div class="grid grid-cols-12 gap-x-6">
    <div class="col-span-12">
        <div class="card table-card">
            @if(session('ok'))
                <div class="alert alert-success mb-4">{{ session('ok') }}</div>
            @endif
            @if(session('connection_result'))
                <div class="alert alert-info mb-4">{!! session('connection_result') !!}</div>
            @endif
            <div class="card-header">
                <div class="sm:flex items-center justify-between">
                    <h5 class="mb-3 sm:mb-0">قائمة الفواتير</h5>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="table-responsive">
                    <table class="table table-hover w-full">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم</th>
                                <th>المبلغ</th>
                                <th>الحالة</th>
                                <th>تاريخ الاستحقاق</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td>{{ $invoice->id }}</td>
                                    <td>{{ $invoice->number }}</td>
                                    <td>{{ number_format($invoice->total_cents / 100, 2) }} {{ $invoice->currency }}</td>
                                    <td>{{ $invoice->status }}</td>
                                    <td>{{ $invoice->due_date?->format('Y-m-d') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="pagination mt-4">
                    {{ $invoices->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</x-client-layout>
