<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.domain_providers.index') }}">مزودو الدومينات</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">تعديل مزود</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">تعديل مزود دومين</h2>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="bg-white shadow-sm rounded-lg overflow-visible p-6">
                <form method="POST" action="{{ route('dashboard.domain_providers.update', $domainProvider->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label class="form-label">وضع الاتصال</label>
                        <select name="mode" class="form-select" required>
                            <option value="live" @selected(old('mode', $domainProvider->mode) == 'live')>Live (فعلي)</option>
                            <option value="test" @selected(old('mode', $domainProvider->mode) == 'test')>Test (اختباري)</option>
                        </select>
                        @error('mode')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">اسم المزود</label>
                        <input type="text" name="name" class="form-control" required
                            value="{{ old('name', $domainProvider->name) }}">
                        @error('name')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">نوع المزود</label>
                        <select name="type" class="form-select" required>
                            <option value="">اختر النوع</option>
                            <option value="enom" @selected(old('type', $domainProvider->type) == 'enom')>Enom</option>
                            <option value="namecheap" @selected(old('type', $domainProvider->type) == 'namecheap')>Namecheap</option>
                            <!-- يمكن إضافة أنواع أخرى مستقبلاً -->
                        </select>
                        @error('type')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">رابط الـ API (endpoint)</label>
                        <input type="text" name="endpoint" class="form-control"
                            value="{{ old('endpoint', $domainProvider->endpoint) }}">
                        @error('endpoint')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" class="form-control"
                            value="{{ old('username', $domainProvider->username) }}">
                        @error('username')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="password" class="form-control"
                            value="{{ old('password', $domainProvider->password) }}">
                        @error('password')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">API Token</label>
                        <input type="text" name="api_token" class="form-control"
                            value="{{ old('api_token', $domainProvider->api_token) }}">
                        @error('api_token')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label">الحالة</label>
                        <select name="is_active" class="form-select">
                            <option value="1" @selected(old('is_active', $domainProvider->is_active) == 1)>مفعل</option>
                            <option value="0" @selected(old('is_active', $domainProvider->is_active) == 0)>معطل</option>
                        </select>
                        @error('is_active')
                            <span class="text-red-600 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="flex gap-2 mt-6">
                        <button type="submit" class="btn btn-primary">تحديث</button>
                        <a href="{{ route('dashboard.domain_providers.index') }}" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-dashboard-layout>
