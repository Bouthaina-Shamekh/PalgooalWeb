<?php
// resources/views/dashboard/management/servers/accounts.blade.php
?>
<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.servers.index') }}">السيرفرات</a></li>
                <li class="breadcrumb-item" aria-current="page">مواقع السيرفر</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">مواقع السيرفر: {{ $server->name }}</h2>
            </div>
        </div>
    </div>
    <div class="card mt-6">
        <div class="card-header">
            <h5 class="mb-0">قائمة المواقع (الحسابات)</h5>
        </div>
        <div class="card-body">
            @if(isset($error))
                <div class="alert alert-danger mb-4">{{ $error }}</div>
            @endif
            @if(isset($accounts) && count($accounts))
                <div class="table-responsive">
                    <table class="table table-hover w-full">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الدومين</th>
                                <th>المستخدم</th>
                                <th>البريد</th>
                                <th>الحالة</th>
                                <th>إنشاء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $i => $acc)
                                <tr>
                                    <td>{{ $i+1 }}</td>
                                    <td>{{ $acc['domain'] ?? '-' }}</td>
                                    <td>{{ $acc['user'] ?? '-' }}</td>
                                    <td>{{ $acc['email'] ?? '-' }}</td>
                                    <td>{{ $acc['suspended'] ? 'موقوف' : 'نشط' }}</td>
                                    <td>{{ $acc['startdate'] ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-gray-500 py-8">لا يوجد مواقع أو لم يتم جلب البيانات.</div>
            @endif
        </div>
    </div>
</x-dashboard-layout>
