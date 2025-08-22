<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                <li class="breadcrumb-item" aria-current="page">تعديل السيرفر</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">تعديل السيرفر</h2>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">بيانات السيرفر</h5>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if(session('connection_result'))
                        <div class="alert alert-info mb-4">{{ session('connection_result') }}</div>
                    @endif
                    <form action="{{ route('dashboard.servers.update', $server) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-1">اسم السيرفر</label>
                                <input type="text" name="name" class="form-control" required value="{{ old('name', $server->name) }}">
                            </div>
                            <div>
                                <label class="block mb-1">نوع اللوحة</label>
                                <select name="type" class="form-control" required>
                                    <option value="cpanel" {{ old('type', $server->type) == 'cpanel' ? 'selected' : '' }}>cPanel</option>
                                    <option value="directadmin" {{ old('type', $server->type) == 'directadmin' ? 'selected' : '' }}>DirectAdmin</option>
                                </select>
                            </div>
                            <div>
                                <label class="block mb-1">IP</label>
                                <input type="text" name="ip" class="form-control" value="{{ old('ip', $server->ip) }}">
                            </div>
                            <div>
                                <label class="block mb-1">Hostname</label>
                                <input type="text" name="hostname" class="form-control" value="{{ old('hostname', $server->hostname) }}">
                            </div>
                            <div>
                                <label class="block mb-1">اسم المستخدم</label>
                                <input type="text" name="username" class="form-control" value="{{ old('username', $server->username) }}">
                            </div>
                            <div>
                                <label class="block mb-1">كلمة المرور</label>
                                <input type="password" name="password" class="form-control" value="{{ old('password', $server->password) }}">
                            </div>
                            <div>
                                <label class="block mb-1">API Token</label>
                                <input type="password" name="api_token" class="form-control" value="">
                            </div>
                            <div>
                                <label class="block mb-1">الحالة</label>
                                <select name="is_active" class="form-control">
                                    <option value="1" {{ old('is_active', $server->is_active) == 1 ? 'selected' : '' }}>مفعل</option>
                                    <option value="0" {{ old('is_active', $server->is_active) == 0 ? 'selected' : '' }}>معطل</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-6 flex gap-2">
                            <button type="submit" class="btn btn-primary">تحديث</button>
                            <a href="{{ route('dashboard.servers.index') }}" class="btn btn-light">إلغاء</a>
                            <a href="{{ route('dashboard.servers.test-connection', $server) }}" class="btn btn-secondary ms-2">اختبار الاتصال بـ cPanel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
