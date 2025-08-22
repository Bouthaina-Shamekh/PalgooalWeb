<x-dashboard-layout>
<div class="container">
    <h2 class="mb-4">تعديل الاشتراك</h2>
    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('dashboard.subscriptions.update', $subscription) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label>العميل</label>
            <select name="client_id" class="form-control" required>
                <option value="">اختر عميل</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" {{ $subscription->client_id == $client->id ? 'selected' : '' }}>{{ $client->first_name }} {{ $client->last_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>الخطة</label>
            <select name="plan_id" class="form-control" required>
                <option value="">اختر خطة</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" {{ $subscription->plan_id == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>السعر ($)</label>
            <input type="number" name="price" class="form-control" min="0" step="0.01" required value="{{ old('price', $subscription->price) }}">
            <small class="text-muted">أدخل السعر بالدولار (مثال: 15.00)</small>
        </div>
        <div class="mb-3">
            <label>الحالة</label>
            <select name="status" class="form-control" required>
                <option value="pending" {{ $subscription->status == 'pending' ? 'selected' : '' }}>معلق</option>
                <option value="active" {{ $subscription->status == 'active' ? 'selected' : '' }}>نشط</option>
                <option value="suspended" {{ $subscription->status == 'suspended' ? 'selected' : '' }}>موقوف</option>
                <option value="cancelled" {{ $subscription->status == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                <option value="expired" {{ $subscription->status == 'expired' ? 'selected' : '' }}>منتهي</option>
            </select>
        </div>
        <div class="mb-3">
            <label>تاريخ البداية</label>
            <input type="date" name="starts_at" class="form-control" required value="{{ old('starts_at', $subscription->starts_at) }}">
        </div>
        <div class="mb-3">
            <label>تاريخ النهاية</label>
            <input type="date" name="ends_at" class="form-control" value="{{ old('ends_at', $subscription->ends_at) }}">
        </div>
        <div class="mb-3">
            <label>نوع الدومين</label>
            <select name="domain_option" class="form-control" required>
                <option value="new" {{ $subscription->domain_option == 'new' ? 'selected' : '' }}>تسجيل دومين جديد</option>
                <option value="subdomain" {{ $subscription->domain_option == 'subdomain' ? 'selected' : '' }}>استخدام سب-دومين</option>
                <option value="existing" {{ $subscription->domain_option == 'existing' ? 'selected' : '' }}>دومين خاص بالعميل</option>
            </select>
        </div>
        <div class="mb-3">
            <label>اسم الدومين</label>
            <input type="text" name="domain_name" class="form-control" value="{{ old('domain_name', $subscription->domain_name) }}">
        </div>
        <button type="submit" class="btn btn-primary">تحديث</button>
        <a href="{{ route('dashboard.subscriptions.index') }}" class="btn btn-light">إلغاء</a>
    </form>
</div>
</x-dashboard-layout>
