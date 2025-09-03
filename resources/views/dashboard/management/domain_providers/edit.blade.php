<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">الرئيسية</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.domain_providers.index') }}">مزودو الدومينات</a></li>
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

                    {{-- الاسم --}}
                    <div class="mb-4">
                        <label class="form-label">اسم المزود</label>
                        <input type="text" name="name" class="form-control" required
                               value="{{ old('name', $domainProvider->name) }}">
                        @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- النوع --}}
                    <div class="mb-4">
                        <label class="form-label">نوع المزود</label>
                        <select name="type" id="provider-type" class="form-select" required>
                            <option value="">اختر النوع</option>
                            <option value="enom"       @selected(old('type', $domainProvider->type) === 'enom')>Enom</option>
                            <option value="namecheap"  @selected(old('type', $domainProvider->type) === 'namecheap')>Namecheap</option>
                            <option value="cloudflare" @selected(old('type', $domainProvider->type) === 'cloudflare')>Cloudflare</option>
                        </select>
                        @error('type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- Endpoint Select --}}
                    <div class="mb-4">
                        <label class="form-label">رابط الـ API (Endpoint)</label>
                        <select name="endpoint" id="endpoint" class="form-select" required></select>
                        <small class="text-muted">اختر البيئة المناسبة (فعلي/اختباري) للمزوّد.</small>
                        @error('endpoint') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- (اختياري) وضع الاتصال للعرض فقط إن كنت تُبقيه في الـ Request --}}
                    <div class="mb-4">
                        <label class="form-label">وضع الاتصال</label>
                        <select name="mode" class="form-select">
                            <option value="live" @selected(old('mode', $domainProvider->mode) === 'live')>Live (فعلي)</option>
                            <option value="test" @selected(old('mode', $domainProvider->mode) === 'test')>Test (اختباري)</option>
                        </select>
                        @error('mode') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- حالة التفعيل --}}
                    <div class="mb-4">
                        <label class="form-label">الحالة</label>
                        <select name="is_active" class="form-select">
                            <option value="1" @selected(old('is_active', $domainProvider->is_active) == 1)>مفعل</option>
                            <option value="0" @selected(old('is_active', $domainProvider->is_active) == 0)>معطل</option>
                        </select>
                        @error('is_active') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    {{-- حقول مشتركة --}}
                    <div id="common-fields">
                        <div class="mb-4">
                            <label class="form-label">اسم المستخدم</label>
                            <input type="text" name="username" class="form-control"
                                   value="{{ old('username', $domainProvider->username) }}">
                            @error('username') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- حقول Enom --}}
                    <div id="enom-fields" class="hidden">
                        <div class="mb-4">
                            <label class="form-label">كلمة المرور (Enom)</label>
                            <input type="password" name="password" class="form-control" autocomplete="new-password">
                            <small class="text-muted">لن نعرض كلمة المرور الحالية لأسباب أمنية. اتركها فارغة إن لم ترغب في تغييرها.</small>
                            @error('password') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label">API Token (Enom)</label>
                            <input type="text" name="api_token" class="form-control"
                                   value="{{ old('api_token', $domainProvider->api_token) }}">
                            <small class="text-muted">أدخل إما كلمة المرور أو الـ Token.</small>
                            @error('api_token') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- حقول Namecheap --}}
                    <div id="namecheap-fields" class="hidden">
                        <div class="mb-4">
                            <label class="form-label">API Key (Namecheap)</label>
                            <input type="text" name="api_key" class="form-control"
                                   value="{{ old('api_key', $domainProvider->api_key) }}">
                            @error('api_key') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Client IP (Namecheap)</label>
                            <input type="text" name="client_ip" class="form-control"
                                   value="{{ old('client_ip', $domainProvider->client_ip) }}"
                                   placeholder="مثال: 5.9.172.153">
                            <small class="text-muted">لازم يكون هذا الـ IP مبيّضًا في لوحة Namecheap.</small>
                            @error('client_ip') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- حقول Cloudflare --}}
                    <div id="cloudflare-fields" class="hidden">
                        <div class="mb-2">
                            <label class="form-label">API Token (Cloudflare)</label>
                            <input type="text" name="api_token" class="form-control"
                                   value="{{ old('api_token', $domainProvider->api_token) }}">
                            @error('api_token') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex gap-2 mt-6">
                        <button type="submit" class="btn btn-primary">تحديث</button>
                        <a href="{{ route('dashboard.domain_providers.index') }}" class="btn btn-light">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- سكريبت إدارة الحقول والـ endpoints --}}
    <script>
        const endpointsByType = {
            enom: [
                { label: 'Enom Live', url: 'https://reseller.enom.com/interface.asp' },
                { label: 'Enom Test', url: 'https://resellertest.enom.com/interface.asp' },
            ],
            namecheap: [
                { label: 'Namecheap Live',    url: 'https://api.namecheap.com/xml.response' },
                { label: 'Namecheap Sandbox', url: 'https://api.sandbox.namecheap.com/xml.response' },
            ],
            cloudflare: [
                { label: 'Cloudflare API', url: 'https://api.cloudflare.com/client/v4' },
            ]
        };

        function $(sel){ return document.querySelector(sel); }
        function show(el){ el.classList.remove('hidden'); }
        function hide(el){ el.classList.add('hidden'); }

        function fillEndpoints(type, current = '') {
            const sel = $('#endpoint');
            sel.innerHTML = '';
            (endpointsByType[type] || []).forEach(e => {
                const opt = document.createElement('option');
                opt.value = e.url;
                opt.textContent = `${e.label} — ${e.url}`;
                if (current && current === e.url) opt.selected = true;
                sel.appendChild(opt);
            });

            // في حال لم يطابق current أي خيار، اختر الأول افتراضياً
            if (!sel.value && sel.options.length) {
                sel.options[0].selected = true;
            }
        }

        function toggleFields(type) {
            const enom = $('#enom-fields');
            const nc   = $('#namecheap-fields');
            const cf   = $('#cloudflare-fields');
            hide(enom); hide(nc); hide(cf);
            if (type === 'enom') show(enom);
            else if (type === 'namecheap') show(nc);
            else if (type === 'cloudflare') show(cf);
        }

        document.addEventListener('DOMContentLoaded', () => {
            const typeSel = $('#provider-type');

            const oldType      = "{{ old('type', $domainProvider->type) }}";
            const currentEP    = "{{ old('endpoint', $domainProvider->endpoint) }}";

            // اضبط النوع الحالي
            if (oldType) typeSel.value = oldType;

            // عبّي قائمة الـ endpoints حسب النوع، مع تحديد الحالي
            fillEndpoints(typeSel.value || 'enom', currentEP);

            // أظهر الحقول المناسبة
            toggleFields(typeSel.value || '');

            // عند تغيير النوع
            typeSel.addEventListener('change', (e) => {
                fillEndpoints(e.target.value);
                toggleFields(e.target.value);
            });
        });
    </script>
</x-dashboard-layout>
