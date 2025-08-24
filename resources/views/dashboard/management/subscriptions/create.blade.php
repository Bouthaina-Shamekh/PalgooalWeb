@php
    $plansArray = [];
    foreach ($plans as $plan) {
        $plansArray[$plan->id] = $plan->price_cents;
    }
@endphp
<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.subscriptions.index') }}">الاشتراكات</a></li>
                <li class="breadcrumb-item" aria-current="page">إضافة اشتراك</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">إضافة اشتراك جديد</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">المعلومات الأساسية</h5>
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
                    <form action="{{ route('dashboard.subscriptions.store') }}" method="POST" class="grid grid-cols-12 gap-x-6">
                        @csrf
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">السعر ($)</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01" required value="{{ old('price') }}">
                            <small class="text-muted">أدخل السعر بالدولار (مثال: 15.00)</small>
                            @error('price')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">اسم المستخدم (Username)</label>
                            <input type="text" name="username" class="form-control" value="{{ old('username') }}">
                            @error('username') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">السيرفر</label>
                            <select name="server_id" class="form-select" required>
                                <option value="">-- اختر سيرفر --</option>
                                @foreach($servers as $server)
                                    <option value="{{ $server->id }}" {{ old('server_id') == $server->id ? 'selected' : '' }}>{{ $server->name }} ({{ $server->ip ?? $server->hostname }})</option>
                                @endforeach
                            </select>
                            @error('server_id') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">تاريخ الاستحقاق القادم</label>
                            <input type="date" name="next_due_date" class="form-control" value="{{ old('next_due_date') }}">
                            @error('next_due_date') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">العميل</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">-- اختر عميل --</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>{{ $client->first_name }} {{ $client->last_name }}</option>
                                @endforeach
                            </select>
                            @error('client_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">الخطة</label>
                            <select name="plan_id" class="form-select" required>
                                <option value="">-- اختر خطة --</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                                @endforeach
                            </select>
                            @error('plan_id') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">نوع الدومين</label>
                            <select name="domain_option" class="form-select" required>
                                <option value="new" {{ old('domain_option') == 'new' ? 'selected' : '' }}>تسجيل دومين جديد</option>
                                <option value="subdomain" {{ old('domain_option') == 'subdomain' ? 'selected' : '' }}>استخدام سب-دومين</option>
                                <option value="existing" {{ old('domain_option') == 'existing' ? 'selected' : '' }}>دومين خاص بالعميل</option>
                            </select>
                            @error('domain_option') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">اسم الدومين</label>
                            <input type="text" name="domain_name" class="form-control" value="{{ old('domain_name') }}" placeholder="مثال: example.com أو client.palgoals.com">
                            @error('domain_name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">تاريخ البداية</label>
                            <input type="date" name="starts_at" class="form-control" required value="{{ old('starts_at') }}">
                            @error('starts_at') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">تاريخ النهاية</label>
                            <input type="date" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                            @error('ends_at') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>معلق</option>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>نشط</option>
                                <option value="suspended" {{ old('status') == 'suspended' ? 'selected' : '' }}>موقوف</option>
                                <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                                <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>منتهي</option>
                            </select>
                            @error('status') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 text-right mt-4">
                            <button type="submit" class="btn btn-primary">حفظ</button>
                            <a href="{{ route('dashboard.subscriptions.index') }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
</x-dashboard-layout>
@php
    $plansArray = [];
    foreach ($plans as $plan) {
        $plansArray[$plan->id] = $plan->price ?? 0;
    }
@endphp
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const planSelect = document.querySelector('select[name="plan_id"]');
        const priceInput = document.querySelector('input[name="price"]');
        const plans = @json($plansArray);
        if(planSelect && priceInput) {
            planSelect.addEventListener('change', function() {
                const selected = this.value;
                if (plans[selected]) {
                    priceInput.value = plans[selected];
                }
            });
        }
    });
</script>
