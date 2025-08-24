<x-dashboard-layout>
<div class="container">
    <h2 class="mb-4">تعديل الفاتورة</h2>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('dashboard.invoices.update', $invoice->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('dashboard.management.invoices._form')
    </form>
</div>
</x-dashboard-layout>
