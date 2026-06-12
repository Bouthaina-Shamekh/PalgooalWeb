<x-dashboard-layout>

    {{-- Page Header --}}
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.servers.index') }}">{{ t('dashboard.Servers', 'السيرفرات') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                    {{ t('dashboard.Server_Accounts', 'مواقع السيرفر') }}
                </li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">
                    {{ t('dashboard.Server_Accounts', 'مواقع السيرفر') }}:
                    <span class="text-primary">{{ $server->name }}</span>
                </h2>
            </div>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('ok'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            {{ session('ok') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($error))
        <div class="alert alert-danger mb-4">
            <i class="ti ti-alert-circle me-2"></i>{{ $error }}
        </div>
    @endif

    {{-- Stats Bar --}}
    @if(!isset($error) && count($accounts))
        <div class="grid grid-cols-12 gap-4 mb-4">
            <div class="col-span-12 md:col-span-4">
                <div class="card text-center py-3" style="border-top:3px solid #4f46e5;">
                    <div class="text-2xl font-bold text-indigo-600">{{ count($accounts) }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ t('dashboard.Total_Accounts', 'إجمالي الحسابات') }}</div>
                </div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="card text-center py-3" style="border-top:3px solid #16a34a;">
                    <div class="text-2xl font-bold text-green-600">{{ $linkedCount }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ t('dashboard.Linked_Accounts', 'مرتبط بالنظام') }}</div>
                </div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="card text-center py-3" style="border-top:3px solid #dc2626;">
                    <div class="text-2xl font-bold text-red-600">{{ $unlinkedCount }}</div>
                    <div class="text-sm text-gray-500 mt-1">{{ t('dashboard.Unlinked_Accounts', 'غير مرتبط') }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="card table-card">
        <div class="card-header flex items-center justify-between gap-3 flex-wrap">
            <h5 class="mb-0">{{ t('dashboard.Server_Accounts_List', 'قائمة الحسابات') }}</h5>
            <div class="flex gap-2 items-center flex-wrap">
                {{-- Filter --}}
                <select id="filter-status" class="form-control form-control-sm" style="width:auto;">
                    <option value="all">{{ t('dashboard.All', 'الكل') }}</option>
                    <option value="unlinked">{{ t('dashboard.Unlinked_Only', 'غير مرتبط فقط') }}</option>
                    <option value="linked">{{ t('dashboard.Linked_Only', 'مرتبط فقط') }}</option>
                </select>
                <input type="text" id="search-accounts" class="form-control form-control-sm"
                       placeholder="{{ t('dashboard.Search', 'بحث...') }}" style="width:180px;">
            </div>
        </div>

        <div class="card-body p-0">
            @if(count($accounts))
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="accounts-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ t('dashboard.Domain', 'الدومين') }}</th>
                                <th>{{ t('dashboard.Username', 'المستخدم') }}</th>
                                <th>{{ t('dashboard.Email', 'البريد') }}</th>
                                <th>{{ t('dashboard.Package', 'الباقة') }}</th>
                                <th>{{ t('dashboard.Status', 'الحالة') }}</th>
                                <th>{{ t('dashboard.Link_Status', 'الربط') }}</th>
                                <th>{{ t('dashboard.Actions', 'إجراءات') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $i => $acc)
                                <tr data-linked="{{ $acc['is_linked'] ? 'linked' : 'unlinked' }}"
                                    data-search="{{ strtolower(($acc['domain'] ?? '') . ' ' . ($acc['user'] ?? '') . ' ' . ($acc['email'] ?? '')) }}">
                                    <td class="text-muted small">{{ $i + 1 }}</td>
                                    <td>
                                        <span class="font-mono text-sm">{{ $acc['domain'] ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="font-mono text-sm text-gray-600">{{ $acc['user'] ?? '-' }}</span>
                                    </td>
                                    <td class="text-sm">{{ $acc['email'] ?? '-' }}</td>
                                    <td class="text-sm text-gray-500">{{ $acc['plan'] ?? '-' }}</td>
                                    <td>
                                        @if(!empty($acc['suspended']) && $acc['suspended'])
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-red-100 text-red-700">
                                                {{ t('dashboard.Suspended', 'موقوف') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-green-100 text-green-700">
                                                {{ t('dashboard.Active', 'نشط') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($acc['is_linked'])
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700">
                                                <i class="ti ti-link me-1"></i>
                                                {{ t('dashboard.Linked', 'مرتبط') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-gray-100 text-gray-500">
                                                <i class="ti ti-unlink me-1"></i>
                                                {{ t('dashboard.Not_Linked', 'غير مرتبط') }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$acc['is_linked'])
                                            <button type="button"
                                                class="btn btn-sm btn-primary btn-import"
                                                data-domain="{{ $acc['domain'] ?? '' }}"
                                                data-username="{{ $acc['user'] ?? '' }}"
                                                data-email="{{ $acc['email'] ?? '' }}"
                                                data-package="{{ $acc['plan'] ?? '' }}"
                                                data-startdate="{{ $acc['startdate'] ?? '' }}"
                                                data-bs-toggle="modal"
                                                data-bs-target="#importModal">
                                                <i class="ti ti-plus me-1"></i>
                                                {{ t('dashboard.Create_Subscription', 'إنشاء اشتراك') }}
                                            </button>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                    <p class="text-gray-500">{{ t('dashboard.No_Accounts', 'لا يوجد حسابات على هذا السيرفر') }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Import Modal --}}
    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ti ti-plus me-2 text-primary"></i>
                        {{ t('dashboard.Create_Subscription', 'إنشاء اشتراك') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-4 text-sm">
                        <i class="ti ti-info-circle me-1"></i>
                        {{ t('dashboard.Import_Account_Hint', 'سيتم إيجاد العميل بالإيميل أو إنشاؤه تلقائياً، ثم ربط الاشتراك بهذا الحساب.') }}
                    </div>
                    <div class="grid grid-cols-12 gap-4">
                        {{-- Domain --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">{{ t('dashboard.Domain', 'الدومين') }}</label>
                            <input type="text" id="imp_domain" class="form-control font-mono" dir="ltr" readonly>
                        </div>
                        {{-- Username --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">{{ t('dashboard.Cpanel_Username', 'اسم مستخدم cPanel') }}</label>
                            <input type="text" id="imp_username" class="form-control font-mono" dir="ltr" readonly>
                        </div>
                        {{-- Email --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">{{ t('dashboard.Client_Email', 'إيميل العميل') }} <span class="text-danger">*</span></label>
                            <input type="email" id="imp_email" class="form-control font-mono" dir="ltr"
                                   placeholder="client@example.com">
                            <p class="text-xs text-gray-400 mt-1">{{ t('dashboard.Import_Email_Hint', 'سيُستخدم للبحث عن عميل موجود أو إنشاء عميل جديد') }}</p>
                        </div>
                        {{-- Package --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">{{ t('dashboard.Server_Package_Label', 'باقة السيرفر') }}</label>
                            <input type="text" id="imp_package" class="form-control font-mono" dir="ltr" readonly>
                        </div>
                        {{-- Plan --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">{{ t('dashboard.Plan', 'الباقة في النظام') }} <span class="text-danger">*</span></label>
                            <select id="imp_plan" class="form-control">
                                <option value="">{{ t('dashboard.Select_Plan', 'اختر الباقة') }}</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        {{-- Billing Cycle --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">{{ t('dashboard.Billing_Cycle', 'دورة الفاتورة') }}</label>
                            <select id="imp_billing" class="form-control">
                                <option value="annually">{{ t('dashboard.Annual', 'سنوي') }}</option>
                                <option value="monthly">{{ t('dashboard.Monthly', 'شهري') }}</option>
                            </select>
                        </div>
                        {{-- Start Date --}}
                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label">{{ t('dashboard.Start_Date', 'تاريخ البدء') }}</label>
                            <input type="date" id="imp_startdate" class="form-control" dir="ltr">
                        </div>
                    </div>

                    {{-- Result message --}}
                    <div id="imp_result" class="mt-3 hidden"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        {{ t('dashboard.Cancel', 'إلغاء') }}
                    </button>
                    <button type="button" class="btn btn-primary" id="btn_import_submit">
                        <i class="ti ti-check me-1"></i>
                        {{ t('dashboard.Create_Subscription', 'إنشاء الاشتراك') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    (function () {
        const importUrl = '{{ route('dashboard.servers.import-account', $server) }}';
        const csrfToken = '{{ csrf_token() }}';

        // ── فلترة + بحث ──────────────────────────────────────────────
        const filterSel  = document.getElementById('filter-status');
        const searchInp  = document.getElementById('search-accounts');
        const tableBody  = document.querySelector('#accounts-table tbody');

        function applyFilter() {
            if (!tableBody) return;
            const filterVal  = filterSel?.value ?? 'all';
            const searchVal  = (searchInp?.value ?? '').toLowerCase().trim();
            tableBody.querySelectorAll('tr').forEach(tr => {
                const linked = tr.dataset.linked;
                const search = tr.dataset.search ?? '';
                let show = true;
                if (filterVal === 'linked'   && linked !== 'linked')   show = false;
                if (filterVal === 'unlinked' && linked !== 'unlinked') show = false;
                if (searchVal && !search.includes(searchVal))          show = false;
                tr.style.display = show ? '' : 'none';
            });
        }

        filterSel?.addEventListener('change', applyFilter);
        searchInp?.addEventListener('input',  applyFilter);

        // ── فتح Modal الاستيراد ───────────────────────────────────────
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-import');
            if (!btn) return;
            document.getElementById('imp_domain').value    = btn.dataset.domain    ?? '';
            document.getElementById('imp_username').value  = btn.dataset.username  ?? '';
            document.getElementById('imp_email').value     = btn.dataset.email     ?? '';
            document.getElementById('imp_package').value   = btn.dataset.package   ?? '';
            document.getElementById('imp_startdate').value = btn.dataset.startdate ?? '';
            const result = document.getElementById('imp_result');
            result.className = 'mt-3 hidden';
            result.innerHTML = '';
        });

        // ── تقديم الاستيراد ───────────────────────────────────────────
        document.getElementById('btn_import_submit')?.addEventListener('click', async function () {
            const btn      = this;
            const domain   = document.getElementById('imp_domain').value.trim();
            const username = document.getElementById('imp_username').value.trim();
            const email    = document.getElementById('imp_email').value.trim();
            const plan     = document.getElementById('imp_plan').value;
            const billing  = document.getElementById('imp_billing').value;
            const start    = document.getElementById('imp_startdate').value;
            const pkg      = document.getElementById('imp_package').value.trim();
            const result   = document.getElementById('imp_result');

            if (!email || !plan) {
                result.className = 'mt-3 alert alert-warning';
                result.innerHTML = '<i class="ti ti-alert-triangle me-1"></i>{{ t('dashboard.Fill_Required', 'يرجى تعبئة الحقول المطلوبة') }}';
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ t('dashboard.Processing', 'جارٍ المعالجة...') }}';

            try {
                const res = await fetch(importUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        domain_name:     domain,
                        cpanel_username: username,
                        email:           email,
                        plan_id:         plan,
                        billing_cycle:   billing,
                        starts_at:       start,
                        server_package:  pkg,
                    }),
                });

                const data = await res.json();

                if (res.ok && data.ok) {
                    result.className = 'mt-3 alert alert-success';
                    result.innerHTML = '<i class="ti ti-circle-check me-1"></i>' + data.message;

                    // تحديث badge الصف في الجدول
                    document.querySelectorAll('.btn-import').forEach(b => {
                        if (b.dataset.username === username) {
                            const tr = b.closest('tr');
                            tr.dataset.linked = 'linked';
                            // استبدال badge الربط
                            const tdLink = tr.querySelectorAll('td')[6];
                            if (tdLink) {
                                tdLink.innerHTML = `<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700"><i class="ti ti-link me-1"></i>{{ t('dashboard.Linked', 'مرتبط') }}</span>`;
                            }
                            // استبدال زر الإجراءات
                            const tdAction = tr.querySelectorAll('td')[7];
                            if (tdAction) {
                                tdAction.innerHTML = '<span class="text-muted small">—</span>';
                            }
                        }
                    });

                    // إغلاق المودال بعد ثانيتين
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('importModal'));
                        modal?.hide();
                    }, 2000);
                } else {
                    result.className = 'mt-3 alert alert-danger';
                    result.innerHTML = '<i class="ti ti-alert-circle me-1"></i>' +
                        (data.message ?? data.error ?? '{{ t('dashboard.Error_Occurred', 'حدث خطأ') }}');
                }
            } catch (err) {
                result.className = 'mt-3 alert alert-danger';
                result.innerHTML = '<i class="ti ti-alert-circle me-1"></i>' + err.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="ti ti-check me-1"></i>{{ t('dashboard.Create_Subscription', 'إنشاء الاشتراك') }}';
            }
        });
    })();
    </script>
    @endpush

</x-dashboard-layout>
