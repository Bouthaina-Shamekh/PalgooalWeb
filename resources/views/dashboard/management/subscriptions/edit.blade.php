@php
    $plansArray = [];
    foreach ($plans as $plan) {
        $plansArray[$plan->id] = $plan->price ?? 0;
    }
    $clientsArray = [];
    foreach ($clients as $c) {
        $clientsArray[$c->id] = [
            'email' => $c->email ?? '',
            'first_name' => $c->first_name ?? '',
            'last_name' => $c->last_name ?? '',
        ];
    }
@endphp
<x-dashboard-layout>
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.subscriptions.index') }}">الاشتراكات</a></li>
                <li class="breadcrumb-item" aria-current="page">تعديل اشتراك</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">تعديل الاشتراك</h2>
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
                    @if (session('ok'))
                        <div class="alert alert-success" role="alert">
                            {{ session('ok') }}
                        </div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('dashboard.subscriptions.update', $subscription) }}" method="POST"
                        class="grid grid-cols-12 gap-x-6">
                        @csrf
                        @method('PUT')
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">السعر ($)</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01"
                                required value="{{ old('price', $subscription->price) }}">
                            <small class="text-muted">أدخل السعر بالدولار (مثال: 15.00)</small>
                            @error('price')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">اسم المستخدم (Username)</label>
                            <div class="flex items-center gap-2">
                                <input type="text" name="username" class="form-control"
                                    value="{{ old('username', $subscription->username) }}">
                                <button type="button" id="suggestUsernameBtn"
                                    class="btn btn-outline-secondary">اقتراح</button>
                            </div>
                            @error('username')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">السيرفر</label>
                            <select name="server_id" class="form-select" required>
                                <option value="">-- اختر سيرفر --</option>
                                @foreach ($servers as $server)
                                    <option value="{{ $server->id }}"
                                        {{ old('server_id', $subscription->server_id) == $server->id ? 'selected' : '' }}>
                                        {{ $server->name }} ({{ $server->ip ?? $server->hostname }})</option>
                                @endforeach
                            </select>
                            @error('server_id')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">تاريخ الاستحقاق القادم</label>
                            <input type="date" name="next_due_date" class="form-control"
                                value="{{ old('next_due_date', $subscription->next_due_date) }}">
                            @error('next_due_date')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">العميل</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">-- اختر عميل --</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}"
                                        {{ old('client_id', $subscription->client_id) == $client->id ? 'selected' : '' }}>
                                        {{ $client->first_name }} {{ $client->last_name }}</option>
                                @endforeach
                            </select>
                            @error('client_id')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">الخطة</label>
                            <select name="plan_id" class="form-select" required>
                                <option value="">-- اختر خطة --</option>
                                @foreach ($plans as $plan)
                                    <option value="{{ $plan->id }}"
                                        {{ old('plan_id', $subscription->plan_id) == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }}</option>
                                @endforeach
                            </select>
                            @error('plan_id')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">نوع الدومين</label>
                            <select name="domain_option" class="form-select" required>
                                <option value="new"
                                    {{ old('domain_option', $subscription->domain_option) == 'new' ? 'selected' : '' }}>
                                    تسجيل دومين جديد</option>
                                <option value="subdomain"
                                    {{ old('domain_option', $subscription->domain_option) == 'subdomain' ? 'selected' : '' }}>
                                    استخدام سب-دومين</option>
                                <option value="existing"
                                    {{ old('domain_option', $subscription->domain_option) == 'existing' ? 'selected' : '' }}>
                                    دومين خاص بالعميل</option>
                            </select>
                            @error('domain_option')
                                <span class="text-red-600 text-sm">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">اسم الدومين</label>
                            <input type="text" name="domain_name" class="form-control"
                                value="{{ old('domain_name', $subscription->domain_name) }}"
                                placeholder="مثال: example.com أو client.palgoals.com">
                            @error('domain_name')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">آخر نتيجة مزامنة</label>
                            <textarea class="form-control" rows="3" readonly>{{ old('last_sync_message', $subscription->last_sync_message) }}</textarea>
                            <small class="text-muted">الرسالة الأخيرة من مزامنة الاشتراك مع المزود (إن وجدت)</small>
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">تاريخ البداية</label>
                            <input type="date" name="starts_at" class="form-control" required
                                value="{{ old('starts_at', $subscription->starts_at) }}">
                            @error('starts_at')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">تاريخ النهاية</label>
                            <input type="date" name="ends_at" class="form-control"
                                value="{{ old('ends_at', $subscription->ends_at) }}">
                            @error('ends_at')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select" required>
                                <option value="pending"
                                    {{ old('status', $subscription->status) == 'pending' ? 'selected' : '' }}>معلق
                                </option>
                                <option value="active"
                                    {{ old('status', $subscription->status) == 'active' ? 'selected' : '' }}>نشط
                                </option>
                                <option value="suspended"
                                    {{ old('status', $subscription->status) == 'suspended' ? 'selected' : '' }}>موقوف
                                </option>
                                <option value="cancelled"
                                    {{ old('status', $subscription->status) == 'cancelled' ? 'selected' : '' }}>ملغي
                                </option>
                                <option value="expired"
                                    {{ old('status', $subscription->status) == 'expired' ? 'selected' : '' }}>منتهي
                                </option>
                            </select>
                            @error('status')
                                <span class="text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="col-span-12 text-right mt-4">
                            <button type="submit" class="btn btn-primary">تحديث</button>
                            <a href="{{ route('dashboard.subscriptions.index') }}" class="btn btn-light">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
</x-dashboard-layout>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const planSelect = document.querySelector('select[name="plan_id"]');
        const priceInput = document.querySelector('input[name="price"]');
        const plans = @json($plansArray);
        if (planSelect && priceInput) {
            planSelect.addEventListener('change', function() {
                const selected = this.value;
                if (plans[selected]) {
                    priceInput.value = plans[selected];
                }
            });
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const clientSelect = document.querySelector('select[name="client_id"]');
        const usernameInput = document.querySelector('input[name="username"]');
        const clients = @json($clientsArray);

        function sanitizeBase(s) {
            if (!s) return '';
            return String(s).toLowerCase().replace(/[^a-z0-9]/g, '').slice(0, 12);
        }

        function suggestUsernameForClient(id) {
            if (!id) return '';
            const c = clients[id] || {};
            let base = '';
            if (c.email && c.email.indexOf('@') !== -1) {
                base = c.email.split('@')[0];
            } else {
                base = (c.first_name || '') + (c.last_name || '');
            }
            base = sanitizeBase(base) || 'user';
            return base + id;
        }

        // عند تغيير العميل، اقترح username لو كان الحقل فارغاً
        clientSelect?.addEventListener('change', function() {
            if (!usernameInput) return;
            if (usernameInput.value && String(usernameInput.value).trim() !== '') return;
            const id = this.value;
            // استخدم الدومين إن كان مُدخلًا
            const domainField = document.querySelector('input[name="domain_name"]');
            if (domainField && domainField.value && domainField.value.trim() !== '') {
                const base = sanitizeBase(domainField.value.replace(/\./g, '')) || 'user';
                usernameInput.value = base;
                return;
            }
            usernameInput.value = suggestUsernameForClient(id);
        });

        // عند تحميل الصفحة، إن كان الحقل فارغاً اقترح قيمة
        if (usernameInput && (!usernameInput.value || String(usernameInput.value).trim() === '')) {
            const selectedClient = document.querySelector('select[name="client_id"]').value;
            if (selectedClient) {
                usernameInput.value = suggestUsernameForClient(selectedClient);
            }
        }
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('suggestUsernameBtn');
        const usernameInput = document.querySelector('input[name="username"]');
        const domainField = document.querySelector('input[name="domain_name"]');
        const clientSelect = document.querySelector('select[name="client_id"]');

        btn?.addEventListener('click', function() {
            const payload = {
                domain_name: domainField?.value || null,
                client_id: clientSelect?.value || null,
                preferred_username: usernameInput?.value || null,
                _token: '{{ csrf_token() }}'
            };
            btn.disabled = true;
            fetch('{{ route('dashboard.subscriptions.username-suggest') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(data => {
                if (data && data.username) {
                    usernameInput.value = data.username;
                }
            }).catch(err => console.error(err)).finally(() => btn.disabled = false);
        });
    });
</script>
