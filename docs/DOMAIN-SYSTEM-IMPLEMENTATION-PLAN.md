# خطة تنفيذ نظام إدارة الدومينات — PalgooalWeb

> **حالة الوثيقة:** مرحلة تخطيط فقط. لا يوجد أي كود مرتبط بهذه الوثيقة حتى الآن.
> **الغرض:** المرجع الرسمي الوحيد لتطوير/استكمال/تصحيح نظام إدارة الدومينات (بحث، شراء، تسجيل، DNS، تجديد، إدارة مزودين) داخل هذا المشروع. أي عمل مستقبلي على نظام الدومينات يجب أن يمر عبر مرحلة من هذه الوثيقة.

| البند | القيمة |
|---|---|
| **Version** | 1.3 |
| **Document Owner** | Project Owner |
| **Current Phase** | Phase 0 — Audit & Discovery |
| **Overall Status** | Planning / Audit (Phase 0: Blocked — see "نتائج التحقق الميداني") |
| **Last Updated** | 2026-07-21 |
| **Approval Status** | Pending Approval |

---

## مقدمة

### هدف النظام

توفير نظام دومينات متكامل داخل PalgooalWeb يسمح بـ:

1. البحث عن دومين متاح (اسم جديد) عبر مزوّدين حقيقيين (Namecheap / Enom حالياً).
2. عرض أسعار دقيقة للتسجيل والتجديد حسب الامتداد (TLD) ومدة السنوات، مع هامش ربح قابل للتحكم من لوحة الإدارة — مع الاحتفاظ بأسعار النقل (Transfer) داخل الكتالوج لأغراض مستقبلية دون تفعيل خدمة النقل للعميل في هذه الخطة (راجع "ما هو خارج النطاق حالياً" أدناه).
3. إتمام عملية الشراء (طلب → فاتورة → دفع → تسجيل فعلي لدى المزوّد).
4. إدارة الدومين بعد الشراء: Nameservers، DNS، التجديد التلقائي واليدوي، ربط الدومين بموقع/قالب العميل.
5. تمكين لوحة الإدارة من إدارة المزوّدين (بيانات اعتماد، وضع Live/Test) وإدارة كتالوج الامتدادات والتسعير ومزامنته من المزوّد.
6. ضمان أن النظام قابل للتوسّع لإضافة مزوّدين جدد دون إعادة كتابة الكود الحالي (لا `if/elseif` متناثرة).

### النطاق (Scope)

- تسجيل دومين جديد (Register) عبر Namecheap و Enom.
- البحث عن التوفر (Availability Check) والتسعير لكل الامتدادات المفعّلة في الكتالوج.
- تخزين أسعار عملية النقل (Transfer) داخل `domain_tld_prices` (الحقل `action=transfer` الموجود بالفعل في بنية الجدول) لأغراض الكتالوج والتوسّع المستقبلي فقط — بدون بناء أي رحلة Transfer فعلية للعميل ضمن هذه الخطة (راجع Out of Scope).
- إدارة DNS الأساسية (Nameservers, Glue records) للدومينات المُدارة عبر المنصة.
- التجديد اليدوي والتلقائي (Auto-Renew) مع إصدار فواتير.
- ربط الدومين بطلب استضافة (Subscription) عند الحاجة (`domain_option`: new/subdomain/existing).
- لوحة إدارة كاملة: مزوّدين، امتدادات وأسعار، طلبات دومين، دومينات العملاء.
- طبقة تجريد (Abstraction) موحّدة للمزوّدين تسمح بإضافة مزوّد جديد (Cloudflare Registrar، GoDaddy Reseller، ...) دون تعديل منطق العمل.
- إشعارات انتهاء الصلاحية والتجديد التلقائي (بريد/إشعار داخل لوحة العميل).
- تدقيق أمني على تخزين بيانات اعتماد المزوّدين وكل عملية تسجيل/تجديد.

### ما هو خارج النطاق حالياً (Out of Scope)

- **تنفيذ أو عرض خدمة نقل الملكية (Domain Transfer-In)** للعميل من مزوّد خارجي — يشمل ذلك عدم بناء أي رحلة Transfer، وعدم جمع/التحقق من EPP/Auth Code، وعدم بناء أي منطق Transfer Approval ضمن أي مرحلة من مراحل هذه الخطة الحالية. **توضيح مهم (لا غموض):** تخزين سعر النقل داخل `domain_tld_prices` (`action=transfer`) لأغراض الكتالوج — وهو موجود بالفعل في بنية قاعدة البيانات وذُكر في "النطاق" أعلاه — لا يعني بأي شكل تفعيل خدمة النقل نفسها للعميل؛ الاثنان منفصلان تماماً. خدمة Transfer الفعلية قد تُضاف كمرحلة منفصلة لاحقة بعد استقرار Register/Renew، وتتطلّب وثيقة تخطيط خاصة بها.
- **WHOIS Privacy / ID Protection** كخدمة مدفوعة منفصلة — يُذكر فقط كملاحظة مستقبلية.
- **مزوّدون جدد فعلياً (Cloudflare Registrar، GoDaddy، ...)** — هذه الخطة تُجهّز البنية القابلة للتوسّع فقط؛ إضافة عميل (Client) فعلي لمزوّد جديد مهمة منفصلة بعد إتمام المرحلة 1.
- **نظام `domain-client` Livewire** (الملفات اليتيمة `client/domain-name-search.blade.php`, `client/domain-table-client.blade.php`) — تُعتبر كوداً ميتاً، القرار بشأنها (حذف أو إحياء) يُتّخذ صراحة في المرحلة 0 وليس ضمنياً.
- **نظام Tenancy Domain Verification** (`.well-known/palgoals-domain-check`, `DomainVerificationProbeController`, `TenantDomainHostService`) — هذا نظام مختلف تماماً (ربط دومين مخصص باستضافة العميل عبر DNS/HTTP verification) وليس نظام تسجيل/شراء دومينات. لا يُمس في هذه الخطة إلا إذا تطلّب الأمر نقطة تكامل صريحة تُوثَّق حينها.
- **تعديل جدول `subscriptions`** بشكل جذري — الأعمدة الموجودة (`domain_option`, `domain_id`, ...) تُستخدم كما هي؛ أي تغيير عليها يحتاج قراراً معمارياً موثّقاً (ADR) منفصلاً.

---

# سجل المخاطر العام

> يُحدَّث هذا السجل عند اكتشاف أي خطر جديد أو تغيّر حالة خطر موجود، في أي مرحلة من مراحل التنفيذ. الحالة الافتراضية لأي خطر غير مُعالَج هي **Open**.

| ID | الخطر | الشدة | الاحتمالية | المرحلة | الإجراء المقترح | الحالة |
|---|---|---|---|---|---|---|
| R-01 | استدعاء `EnomClient::checkAvailability()` وهي غير موجودة فعلياً في الكلاس → **مؤكَّد بالكود** في `DomainSearchController::enomCheck()` (السطر 235). التأثير الفعلي: الاستدعاء يقع داخل `try { … } catch (\Throwable $e)` (السطر 250)، و`Error: Call to undefined method` في PHP يُنفَّذ `Throwable`، لذا يُلتقَط ولا يُسبِّب 500 غير معالج — لكنه يُحوَّل دائماً إلى استجابة JSON فاشلة (`ok:false`, `reason: exception`)، أي أن **فحص التوفر عبر Enom معطَّل بنسبة 100% دون استثناء**، بصمت نسبي (رسالة عامة لا تُفسَّر بسهولة من واجهة المستخدم). | Critical | Confirmed (100% فشل صامت عند استخدام Enom لفحص التوفر — تأكيد كودي مباشر) | المرحلة 1 | تنفيذ `checkAvailability()` ضمن `EnomClient` عبر العقد الموحّد؛ يُعتبر Blocker فوري إن كان Enom نشطاً في الإنتاج (**تعذّر تأكيد هذا الشرط في المرحلة 0 — راجع "نتائج التحقق الميداني"، لا يوجد وصول لقاعدة بيانات الإنتاج/Staging من بيئة التنفيذ**). | Open |
| R-02 | استخدام بيانات WHOIS/جهة اتصال وهمية (Placeholder) عند التسجيل الفعلي عند غياب بيانات العميل الحقيقية. | Critical | Medium | المرحلة 5 | حسم مصدر بيانات WHOIS الإلزامي (راجع المرحلة 0.5 / ADR-DOM-007) ومنع إتمام التسجيل دون بيانات حقيقية كاملة. | Open |
| R-03 | تكرار منطق التفريع بين المزوّدين (`if/elseif`) عبر أكثر من 4 ملفات دون طبقة تجريد موحّدة. | High | High (موجود فعلياً وفق جرد المرحلة 0) | المرحلة 1 | بناء عقد/Factory موحّد للمزوّدين (ADR-DOM-001) وإزالة التفريع المتكرر. | Open |
| R-04 | عدم وجود أي اختبارات End-to-End لمسار التسجيل الفعلي (Register). | High | High | المرحلة 5 / المرحلة 8 | إضافة اختبارات Sandbox شاملة لمسار التسجيل قبل أي اعتماد لبيانات `live`. | Open |
| R-05 | عدم وجود مزامنة تلقائية/مجدولة لأسعار TLD — الاعتماد الكامل على مزامنة يدوية من لوحة الإدارة. | Medium | Medium | المرحلة 2 | تقييم وإضافة مهمة مجدولة لمزامنة الأسعار مع تسجيل نتيجتها. | Open |
| R-06 | احتمال اختلاف السعر المعروض للعميل وقت البحث عن السعر الفعلي وقت الدفع. | High | Medium | المرحلة 4 | حسم سياسة تثبيت السعر (Price Locking) صراحة (راجع المرحلة 0.5 / ADR-DOM-004). | Open |
| R-07 | نجاح الدفع مع فشل التسجيل الفعلي لدى المزوّد (سيناريو "فشل صامت بعد الدفع"). | Critical | Medium | المرحلة 5 | تعريف سلوك رسمي (إعادة محاولة/تنبيه إداري) قبل تنفيذ مسار التسجيل (راجع ADR-DOM-006). | Open |
| R-08 | احتمال تسرّب بيانات اعتماد المزوّدين (API keys/passwords) في ملفات اللوج أو استجابات JSON. | Critical | Low (فحص أولي/Baseline في المرحلة 0 لكل استدعاءات `Log::` داخل `app/Services/Domains/**` و`DomainProviderController::testConnection` لم يُظهر أي قيمة خام لـ password/api_key/api_token/client secret — أقصى ما يُسجَّل هو `username`، `client_ip`، ونوع/معرّف المزوّد، بالإضافة إلى `strlen()` لمفتاح Namecheap في `NamecheapClient::request()` (طول فقط، لا قيمة). هذا **فحص ثابت (static) على نطاق محدود فقط** وليس بديلاً عن تدقيق المرحلة 7 الكامل (لا يغطي ملفات اللوج الفعلية على القرص، ولا نقاط أخرى خارج `app/Services/Domains` و`DomainProviderController`). | المرحلة 7 | تدقيق أمني مباشر لكل نقاط التسجيل (`Log::`) والاستجابات المرتبطة بالمزوّدين، بما يشمل ملفات اللوج الفعلية لا الكود الثابت فقط. | Open |
| R-09 | الاعتماد الكامل على عمليات متزامنة (Synchronous) في التجديد التلقائي، بدون Jobs/Queues. | Medium | Medium (نقطة اختناق مستقبلية مع نمو عدد الدومينات) | المرحلة 6 | تقييم الحاجة لنقل العمليات لـ Jobs عند الوصول لحجم يستدعي ذلك، وتوثيق القرار. | Open |
| R-10 | وجود خيار مزوّد (Cloudflare) ضمن `DomainProvider::TYPES` بدون أي Client فعلي يدعمه. **مؤكَّد ومُوسَّع في المرحلة 0**: `DomainProviderRequest` يتحقق بالكامل من مدخلات Cloudflare (`api_token` مطلوب) ويشتق `endpoint` تلقائياً؛ نموذجا الإنشاء/التعديل في لوحة الإدارة (`domain_providers/create.blade.php`, `edit.blade.php`) يعرضان Cloudflare كخيار فعلي قابل للاختيار؛ **`database/seeders/DomainProviderSeeder.php` ينشئ صراحة مزوّد Cloudflare بحالة `is_active=true` و`mode=test`** (بيانات وهمية `cloudflare_test_token`). ورغم كل ذلك **لا يوجد أي ملف Client فعلي** تحت `app/Services/Domains/Clients/` لـ Cloudflare (فقط `EnomClient.php` و`NamecheapClient.php` موجودان)، و`DomainProviderController::testConnection()` لا يحتوي حالة `cloudflare` في switch الاختبار الفعلي (فقط `enom`/`namecheap`) فيعيد رسالة "نوع غير مدعوم للاختبار الآلي" دون خطأ فادح. | Medium | High (موجود فعلياً في الكود وواجهة الإدارة، ومُفعَّل افتراضياً عبر الـ Seeder إن شُغِّل) | المرحلة 0.5 / المرحلة 1 | حسم القرار بشأن Cloudflare (إخفاء/إزالة مؤقتة) — راجع المرحلة 0.5، القرار رقم 3. | Open |
| R-11 | اعتبار جميع قرارات المرحلة 0.5 بوابة واحدة قد يؤخر إصلاحات حرجة (مثل R-01) بسبب قرار غير مرتبط وظيفياً بها (مثل القرار 10 التنظيفي). | Medium | Medium | المرحلة 0.5 | تصنيف القرارات حسب المرحلة التي تعطلها باستخدام حقل Blocking Level (راجع تصنيف القرارات داخل قسم المرحلة 0.5) بدل بوابة واحدة شاملة. | Mitigated |
| R-12 | Route حي (`domains.page` — `GET /domains`) يُعيد `view('domains.search')` غير الموجود إطلاقاً، ومرتبط فعلياً وبشكل حي بثلاثة مصادر مؤكَّدة (مراجعة معمارية 2026-07-22): (أ) رابط ظاهر في فوتر الموقع `front/layouts/footers/palgoals_marketing.blade.php`؛ (ب) رابط في القائمة الافتراضية `front/layouts/partials/footer/menu-links.blade.php`؛ (ج) نموذج بحث `GET` داخل Section مسجَّل فعلياً `components/template/sections/domains_showcase.blade.php`. **التأثير المؤكَّد**: أي زائر يستخدم أياً من هذه المداخل الثلاثة يُصادف خطأ فعلي (View غير موجود) بدل نتيجة بحث. | Critical | Confirmed (تأكيد كودي مباشر لثلاثة مصادر استخدام حية، لا افتراض) | المرحلة 0.5 (لاتخاذ القرار) ثم المرحلة 3 (للتنفيذ) | حسم القرار الجديد في المرحلة 0.5 (القرار 11) بين: توجيه الرابط لصفحة تحتوي الـ Section الرسمي / تعديل `domains_showcase` لاستخدام `domains.check` / إزالة الروابط والـ Route القديم. | Open |

---

# فهرس القرارات المعمارية

> جميع الملفات أدناه **لم تُنشأ بعد** في هذه المرحلة (مرحلة تخطيط فقط). الحالة `Planned` تعني أن الحاجة لـ ADR مُحدَّدة، دون إنشاء الملف الفعلي. الترقيم `ADR-DOM-*` مخصص لنظام الدومينات تحديداً لتفادي التعارض مع ترقيم ADRs الموجود فعلاً في المشروع لأنظمة أخرى (مثل `ADR_003_*`, `ADR_005_*`).

| ADR | القرار | المرحلة | الحالة | الملف |
|---|---|---|---|---|
| ADR-DOM-001 | Provider Abstraction (عقد المزوّدين الموحّد) | المرحلة 1 | Planned | لم يُنشأ بعد |
| ADR-DOM-002 | Domain Order Storage Strategy (`orders`/`order_items` مقابل `domain_orders`) | المرحلة 0.5 / المرحلة 4 | Planned | لم يُنشأ بعد |
| ADR-DOM-003 | Registrar Relation Strategy (`domains.registrar` كنص حر مقابل FK) | المرحلة 0.5 / المرحلة 1 | Planned | لم يُنشأ بعد |
| ADR-DOM-004 | Domain Pricing and Price Locking (مصدر السعر ولحظة تثبيته) | المرحلة 4 | Planned | لم يُنشأ بعد |
| ADR-DOM-005 | Provider Selection Policy (سياسة اختيار المزوّد عند تعدد الدعم لنفس الامتداد) | المرحلة 0.5 / المرحلة 1 | Planned | لم يُنشأ بعد |
| ADR-DOM-006 | Registration Failure After Payment (السلوك الرسمي عند فشل التسجيل بعد نجاح الدفع) | المرحلة 5 | Planned | لم يُنشأ بعد |
| ADR-DOM-007 | WHOIS Contact Data Source (مصدر بيانات جهة الاتصال الإلزامي) | المرحلة 5 | Planned | لم يُنشأ بعد |
| ADR-DOM-008 | Dead Code and Legacy Views Cleanup (الملفات القديمة/اليتيمة المكتشفة في المرحلة 0) | المرحلة 0 / المرحلة 0.5 | Planned | لم يُنشأ بعد |
| ADR-DOM-009 | Domain Search Interface Consolidation & Legacy Route Resolution (توحيد واجهات بحث الدومين الثلاث ومصير Route `domains.page`) | المرحلة 0.5 / المرحلة 3 | **Accepted** (2026-07-22) | `docs/ADR-DOM-009-DOMAIN-SEARCH-ENTRY-POINT.md` |

---

## المرحلة 0 — Audit & Discovery

### الهدف
توثيق الحالة الفعلية للنظام بالكامل قبل أي تعديل، بحيث تُبنى كل مرحلة لاحقة على حقائق مؤكدة لا افتراضات.

### نتائج الجرد الأولي (Baseline Findings)

> ملاحظة: هذا الجرد نُفّذ كبحث read-only في 2026-07-21، وتم **التحقق الميداني الفعلي** لاحقاً (نفس التاريخ) عبر قراءة مباشرة للكود الحالي (migrations، Models، Controllers، Services، Routes، Seeders) داخل بيئة تنفيذ لا تملك اتصالاً بقاعدة البيانات ولا PHP (راجع القسم الكامل "### نتائج التحقق الميداني" أدناه لتفاصيل هذا القيد وأثره على أي بند يعتمد على بيانات حيّة). كل بند أدناه إما **مؤكَّد بدليل كودي مباشر (ملف + سطر حيث أمكن)**، أو مُعلَّم صراحة **"تعذّر التحقق" مع السبب**. لا توجد بعد الآن عبارات "يحتاج تأكيد" غامضة دون سبب أو دليل.

#### قاعدة البيانات — مؤكَّد عبر ملفات الـ Migrations (راجع القيد أدناه)
> **قيد منهجي مهم:** هذا الجدول مبني على قراءة ملفات `database/migrations/*` (مصدر الحقيقة للبنية كما كُتبت) وليس على استعلام حي لقاعدة بيانات فعلية — بيئة التنفيذ لا تملك اتصالاً بأي قاعدة بيانات (لا إنتاج ولا Staging). البنية أدناه **مؤكَّدة كما هي مكتوبة في الكود**؛ التطابق الكامل مع الحالة الفعلية على السيرفر (هل كل migration نُفِّذ فعلاً بنفس الترتيب؟) **تعذّر تأكيده** لنفس السبب.

| الجدول | الحالة | ملاحظات |
|---|---|---|
| `domains` | مؤكَّد (migration + Model) | `registrar` عمود `string` حر (غير FK) — مؤكَّد من `2025_08_03_133326_create_domains_table.php`. عمود `template_id` أُضيف في `2025_08_17_123121_add_cloums_to_domains_table.php` ثم **حُذف فعلياً** في `2025_09_16_000001_drop_template_id_from_domains_table.php` — **ولا يزال مذكوراً في `$fillable` بـ `App\Models\Domain` (السطر 12)** وهناك أيضاً علاقة `template()` في نفس الموديل تفترض وجوده. هذا **كود ميت مؤكَّد بالدليل** (fillable لعمود غير موجود في الجدول). كما يحتوي الموديل على `Domain::checkAvailability(string $domain): bool` (السطر 48) وهي **دالة ساكنة تُعيد `true` دائماً بلا أي منطق فعلي** — غير مستخدمة من مسار الفحص الحقيقي (`DomainSearchController`)، لكنها كود ميت/مضلِّل إضافي يستحق قراراً صريحاً. |
| `domain_providers` | مؤكَّد (migration + Model) | بيانات `password`/`api_token`/`api_key` مشفّرة عبر `encrypted` cast في الموديل (مؤكَّد)، وأيضاً `hidden` (لا تظهر في `toArray/toJson`). `type` قيمه الممكنة `App\Models\DomainProvider::TYPES = ['enom','namecheap','cloudflare']` (مؤكَّد). `mode` القيمة الافتراضية `test` (مؤكَّد من `$attributes` بالموديل — قيمة آمنة افتراضياً). |
| `domain_tlds` | مؤكَّد (migration + Model) | `provider_id` FK حقيقي على `domain_providers` (`cascadeOnDelete`)، بالإضافة لعمود `provider` نصي منسوخ (denormalized) — مؤكَّد من migration. عمود `in_catalog` أُضيف لاحقاً بـ migration منفصلة (مؤكَّد). |
| `domain_tld_prices` | مؤكَّد (migration + Model) | `action` هو `enum('register','renew','transfer','restore')` مع `years` و`cost`/`sale`، وقيد `unique(domain_tld_id, action, years)` — مؤكَّد. |
| `orders` / `order_items` | مؤكَّد (migration + Model) | لا يوجد عمود `domain_orders`/`domain_search` في أي migration بالمشروع (تم التأكد ببحث شامل). عمود `orders.type` نصي حر يحمل قيماً مثل `domain`, `domain_renewal` (مؤكَّد من استخدام فعلي في `DomainRenewalService`/`Client\DomainController`). `order_items` يحتوي عمود `domain` (نص) + `item_option` (`register`/`renew`) + `meta` (json) يحمل `registrar`, `registration_date`, `renewal_date`, `term_years`, `domain_id` — مؤكَّد من `RegistrarProvisioningService` و`DomainRenewalService`. |
| `subscriptions.domain_*` | مؤكَّد (migrations متعددة) | `domain_option` (`new`/`subdomain`/`existing`) و`domain_name` من migration الإنشاء الأصلية؛ **`domain_id` (FK على `domains`, nullable) أُضيف لاحقاً** في `2025_11_17_120000_extend_subscriptions_for_tenants.php` — لم يكن في الوثيقة الأصلية موضحاً أنه أُضيف بمigration منفصلة لاحقة. أعمدة `domain_verification_status`, `domain_last_checked_at`, `domain_verified_at`, `domain_verification_error` (نظام Tenancy Domain Verification، مختلف عن نظام تسجيل الدومينات) أُضيفت في `2026_03_28_000001_add_domain_verification_to_subscriptions_table.php` — مؤكَّد أنها مفهوم منفصل تماماً (راجع قسم WHM/Tenancy أدناه). |

**مؤكَّد بالبحث الشامل (لا نتائج سوى هذه الوثيقة نفسها):** لا يوجد جدول أو Model باسم `domain_orders` أو `domain_search` في كامل المشروع. القرار الحالي (إعادة استخدام `orders`/`order_items`) هو الواقع المؤكَّد حالياً؛ الحسم النهائي بشأن استمراريته هو قرار معماري (القرار رقم 1 في المرحلة 0.5) — **لم يتغيّر ولا يزال `Pending`**.

#### المزوّدون (Enom / Namecheap) — مؤكَّد بالكامل بقراءة مباشرة للملفات
- **Namecheap**: `App\Services\Domains\Clients\NamecheapClient` (مؤكَّد) يحتوي: `getBalance()`, `callGeneric()`, `setCustomNameservers()`, `getNameserverInfo()`, `createNameserver()`, `updateNameserver()`, `renewDomain()`, `getNameservers()`. **لا يحتوي دالة تسجيل صريحة (`register`/`purchase`)** — التسجيل الفعلي يتم عبر `callGeneric('namecheap.domains.create', ...)` من داخل `RegistrarProvisioningService::registerDomainWithProvider()` مباشرة (مؤكَّد، السطر 386). فحص التوفر (`namecheap.domains.check`) منفّذ فعلاً **خارج** الكلاس، داخل `DomainSearchController::namecheapCheck()` (مؤكَّد) — تكرار معماري كما ورد في الجرد الأولي.
- **Enom**: `App\Services\Domains\Clients\EnomClient` (مؤكَّد) يحتوي: `getBalance()`, `getProductPrice()`, `getRetailPrice()`, `getResellerPrice()`, `getAnyPrice()`, `purchaseDomain()`, `renewDomain()`, `checkNameserverStatus()`, `registerNameserver()`, `updateNameserverIp()`, `updateNameservers()`, `getDns()`. **مؤكَّد تماماً: لا توجد دالة `checkAvailability()` في هذا الكلاس (بحث كامل بالملف)**. `DomainSearchController::enomCheck()` (السطر 235) يستدعي فعلياً `$client->checkAvailability($p, $sld, $tld)` — **مؤكَّد أن هذا الاستدعاء سيفشل دائماً**. التأثير الفعلي (مؤكَّد بقراءة الكود): الاستدعاء داخل `try/catch (\Throwable $e)` (السطر 250) — بما أن `Error: Call to undefined method` في PHP يُنفِّذ واجهة `Throwable`، **يتم التقاطه فعلياً ولا يُسبِّب صفحة خطأ 500**؛ بدلاً من ذلك تُعاد استجابة JSON بصيغة `{"ok": false, "reason": "exception", "message": "استثناء: Call to undefined method App\\Services\\Domains\\Clients\\EnomClient::checkAvailability()"}`. النتيجة العملية: **فحص التوفر عبر Enom معطَّل 100%** لكن بفشل "أنيق" (JSON error) وليس بانهيار كامل — راجع R-01 المُحدَّث في سجل المخاطر العام.
- **Cloudflare**: **مؤكَّد ومُوسَّع (وأخطر مما ورد سابقاً)**: `DomainProvider::TYPES` يتضمنه (مؤكَّد)، `DomainProviderRequest` يتحقق من مدخلاته بالكامل (`api_token` مطلوب، endpoint يُشتق تلقائياً)، ونماذج الإنشاء/التعديل في لوحة الإدارة (`resources/views/dashboard/management/domain_providers/{create,edit}.blade.php`) تعرضه كخيار فعلي قابل للاختيار من القائمة. **الأخطر: `database/seeders/DomainProviderSeeder.php` ينشئ صراحة مزوّد Cloudflare بـ `is_active=true` و`mode=test`** إن تم تشغيل هذا الـ Seeder. رغم ذلك **لا يوجد أي ملف `CloudflareClient` أو مكافئ** تحت `app/Services/Domains/Clients/` (تم التأكد بـ Glob — الملفان الوحيدان هما `EnomClient.php` و`NamecheapClient.php`)، و`DomainProviderController::testConnection()` لا يحتوي حالة `cloudflare` (فقط `enom`/`namecheap` في الـ switch الفعلي) فيعيد رسالة "نوع غير مدعوم للاختبار الآلي حالياً" بأمان (لا كسر).
- **مؤكَّد**: لا توجد طبقة تجريد موحّدة (Interface/Contract) للمزوّدين. التفريع النصي (`if ($provider->type === 'namecheap') / elseif === 'enom'`) موجود فعلياً ومؤكَّد بالقراءة المباشرة في: `RegistrarProvisioningService` (دالتان: `registerDomainWithProvider`, `renewDomainWithProvider`)، `DomainDnsService` (دالتان: `fetchRemoteDnsSnapshot`, `pushNameserversToProvider`، بالإضافة لـ `ensureSubordinateHostsReady`)، `DomainSearchController::check()`، و`DomainProviderController::testConnection()` — أي **4 ملفات مؤكَّدة على الأقل**، بما يطابق ادعاء الجرد الأولي تماماً.

#### التسجيل الفعلي (Registration) — مؤكَّد بالكامل بقراءة الكود، غير مؤكَّد تشغيلياً
- `RegistrarProvisioningService::provisionOrderDomain()` يُستدعى فعلياً من `OrderActivationService::activate()` (مؤكَّد، السطر 61-62)، وهذا بدوره يُستدعى من `InvoiceSettlementService::markPaid()` (مؤكَّد، السطر 122) — **بشكل متزامن بالكامل (synchronous)، لا Job ولا Queue في أي نقطة من هذا المسار** (مؤكَّد بعدم وجود أي `dispatch()`/`Queue::` في هذه السلسلة).
- **مؤكَّد ومُوثَّق بدقة أكبر — بيانات Placeholder الفعلية** في `RegistrarProvisioningService::buildRegistrarContactPayload()` و`formatRegistrarPhone()`: هاتف احتياطي `+1.5555555555` (إن كان الهاتف المُدخل أقل من 4 أرقام)، اسم منظمة احتياطي `Palgooal Client`، بريد احتياطي `support@example.com`, رمز بريدي احتياطي `00000`, دولة احتياطية `US`. **لعملاء Enom تحديداً**: حقل `Fax` يُضبَط دائماً على `0000000000` لكل الأدوار (Registrant/Admin/Tech/AuxBilling) **بلا شرط أي نقص بيانات** (مؤكَّد، `expandContactForEnom()`). **لا توجد أي Validation تمنع إتمام التسجيل عند نقص بيانات العميل** — القيم الاحتياطية تُستخدَم بصمت (`sanitizeContactValue()` لا ترفض، فقط تستبدل).
- **لا توجد Retry mechanism ولا Jobs/Queues مؤكَّدة** في كامل مسار `RegistrarProvisioningService`/`OrderActivationService`/`InvoiceSettlementService` (بحث شامل، لا نتائج).
- **لا يوجد تنبيه إداري (Notification/Mail) مؤكَّد** عند فشل التسجيل تحديداً — فقط `Log::error()` وإرجاع `ok:false` (مؤكَّد).
- **حالات الدومين المؤكَّدة فعلياً في الكود**: `pending` (قبل/عند فشل التسجيل)، `active` (بعد نجاح التسجيل/التجديد). **لا توجد حالتا `processing` أو `registered`/`failed` صريحتان** — الفشل يبقى بحالة `pending` مع `dns_last_note` نصي يحمل رسالة الخطأ (مؤكَّد).
- **اكتشاف جديد مهم غير مذكور في الجرد الأولي — دليل كودي مباشر على سيناريو R-07 ("نجاح الدفع + فشل التسجيل")**: `InvoiceSettlementService::markPaid()` (مؤكَّد، الأسطر 40-141) ينفّذ **كل شيء داخل `DB::transaction()` واحدة**: تحديث حالة الفاتورة إلى `paid`، زيادة عداد الكوبون، تفعيل الطلب (`Order::STATUS_ACTIVE`)، **ثم استدعاء `OrderActivationService::activate()` الذي يستدعي تسجيل الدومين**. إذا فشل التسجيل، يُرمى `RuntimeException` صراحة (السطر 133) **من داخل نفس الـ transaction** — ما يعني **تراجع (rollback) كامل** لتحديث حالة الفاتورة والطلب وعداد الكوبون. التعليقات في الكود نفسه (`markPaid()` "fires only after real payment") تؤكد أن هذه الدالة تُستدعى **بعد** نجاح الدفع الفعلي لدى بوابة الدفع الخارجية (مؤكَّد من `CheckoutController.php`, `InvoiceCheckoutController.php`, `PaymentWebhookController.php`). **الأثر العملي المؤكَّد بالكود**: عميل يدفع فعلياً ثم تفشل عملية تسجيل الدومين → الفاتورة تعود/تبقى غير مدفوعة محلياً رغم نجاح الدفع الخارجي، دون أي آلية استرجاع أو تنبيه تلقائي مؤكَّدة. هذا **دليل كودي مباشر يدعم R-07** وليس افتراضاً نظرياً فقط.

#### DNS وNameservers — مؤكَّد
- `DomainDnsService` (مؤكَّد بالكامل بالقراءة) يعمل لِـ Namecheap وEnom تحديداً مع معالجة حالات خاصة بكل مزوّد (Glue records لـ Enom عبر `checkNameserverStatus`/`registerNameserver`/`updateNameserverIp`؛ Personal DNS لـ Namecheap عبر `ensureNamecheapPersonalNameserver`). أي مزوّد آخر → رسالة صريحة "DNS sync is not yet implemented for X" أو "Fetching DNS snapshot is not implemented for X yet" (مؤكَّد، سلوك آمن ومتوقَّع، ليس عطلاً).

#### التجديد التلقائي (Auto-Renew) — مؤكَّد
- مؤكَّد: يعمل عبر أمر Artisan **مُعرَّف كـ closure مباشرة داخل `routes/console.php`** (وليس Command class منفصلة تحت `app/Console/Commands` — لا يوجد أي ملف هناك لهذا الغرض، تم التأكد بـ Glob) باسم `domains:process-auto-renewals {--dry-run}` (السطر 20-35)، مجدولاً عبر `Schedule::command('domains:process-auto-renewals')->dailyAt('02:00')->withoutOverlapping();` (السطر 815-817، مؤكَّد حرفياً).
- **مؤكَّد**: **لا يوجد `->onOneServer()`** على هذه الجدولة (فقط `withoutOverlapping()` موجود) — نقطة ضعف إضافية غير مذكورة سابقاً إن كان النشر متعدد السيرفرات.
- **مؤكَّد**: لا يوجد `Log::` داخل تعريف الأمر نفسه أو نداء الجدولة — المخرَجات تقتصر على جدول Console (`$this->table(...)`) الذي لا يُخزَّن إلا إذا كانت مخرجات الأمر المجدول تُوجَّه لملف لوج بإعداد منفصل خارج هذا الكود (لم يُؤكَّد وجود مثل هذا الإعداد ضمن نطاق هذا الفحص).
- منطق "lead days" مؤكَّد فعلاً في `DomainRenewalService::leadDaysFor()` (Enom=30 يوماً، امتدادات معينة =12/5، وإلا 7).
- **لا يوجد Job/Event/Listener منفصل** — مؤكَّد أن كل شيء متزامن (synchronous) داخل الأمر نفسه عبر `DomainRenewalService::processDueAutoRenewals()`.
- **لا توجد إشعارات انتهاء صلاحية منفصلة (بريد/تنبيه داخل لوحة العميل)** مؤكَّدة بحثاً — فقط تحديث حقل `dns_last_note` النصي على سجل الدومين نفسه عند حالة "awaiting_payment"، وهو ليس إشعاراً فعلياً للعميل (بحسب الكود المفحوص).
- **لا توجد مهمة مجدولة لمزامنة أسعار TLD** — مؤكَّد، المزامنة يدوية فقط من لوحة الإدارة (`POST /domain-tlds/sync`).

#### لوحة الإدارة (Dashboard) — مؤكَّد
- `management/domains/*`, `management/domain_providers/*`, `management/domain_tlds/*` — مجموعة Views مرتبطة فعلياً بـ Routes نشطة (مؤكَّد من `routes/dashboard.php`).
- `resources/views/dashboard/domains.blade.php` — **مؤكَّد**: يحمل تعليقاً صريحاً `{{-- deprecated - do not use. Legacy admin Livewire mount retained only for fallback safety. --}}` ويستدعي `<livewire:domain-component />`. **مؤكَّد بالبحث الشامل: لا يوجد أي Route أو Controller في المشروع بأكمله يُنادي `view('dashboard.domains')` أو ما يعادلها** — اسم الـ Route الفعلي `dashboard.domains.index` تتم معالجته بواسطة `Admin\Management\DomainController::index()` الذي يُعيد `view('dashboard.management.domains.index')` (ملف مختلف تماماً). **كما لا يوجد أي كلاس Livewire باسم `domain-component` في `app/Livewire` أو `app/Http/Livewire`** (بحث شامل، لا نتائج). الخلاصة المؤكَّدة: هذا الملف **يتيم فعلياً بنسبة 100%** — غير قابل للوصول عبر أي مسار معروف حالياً، وإن استُدعي يدوياً لظهر خطأ "Unable to find component: [domain-component]".

#### الواجهة الأمامية (Front-end) — مؤكَّد، مع تحديث حرج (مراجعة معمارية 2026-07-22): Route `/domains` نشط ومكسور فعلياً (راجع التحديث أدناه)
- **`front/sections/templates/domain_search.blade.php`**: مؤكَّد أنه بلوك عرض ثابت **بدون أي استدعاء لدالة الترجمة `t()` على الإطلاق** (0 نتيجة بحثاً) وبدون أي JS أو ربط بأي Endpoint — نص hardcoded بالكامل. الزر لا يفعل شيئاً حالياً (مؤكَّد).
- **`components/template/sections/search-domain.blade.php`**: مؤكَّد أنه ودجت بحث دومين فعّال وكامل (استخدام `t()` مؤكَّد 75 مرة داخل نفس الملف — Acceptable من ناحية الترجمة)، يستدعي `route('domains.check')` عبر `fetch` (مؤكَّد)، ومستخدَم فعلياً في 3 ملفات مؤكَّدة: `front/pages/checkout.blade.php`, `client/domains/search.blade.php`, ونفس الملف نفسه كمكوّن. توجد نسخة مكررة قديمة تحت `legacy/visual-builder/resources/views/components/template/sections/search-domain.blade.php` (مؤكَّد وجودها بالمسار) — **مؤكَّد بالبحث: لا يوجد أي Service Provider أو إعداد `view.php` يُسجِّل مجلد `legacy/visual-builder` كمسار Views محمَّل** → هذه النسخة **غير محمَّلة فعلياً من أي مكان في التطبيق الحالي**، أي كود ميت مؤكَّد وليس مجرد احتمال.
- **اكتشاف جديد مؤكَّد (مراجعة معمارية 2026-07-22) — `components/template/sections/domains_showcase.blade.php`**: مؤكَّد أنه **Section مسجَّل فعلياً** ضمن `SectionRenderer::LEGACY_FRONTEND_SECTION_TYPES` (المفتاح `domains_showcase`)، أي قابل للإضافة والعرض الفعلي على أي صفحة عبر Page Builder، وله محرِّر حقول مخصَّص في لوحة الإدارة (`dashboard/pages/sections/partials/blocks/domains-search-fields.blade.php`). يحتوي نموذج بحث بصيغة `<form method="GET">` يُرسِل مباشرة إلى `route('domains.page', [], false)` — **أي أنه يعتمد كلياً على الـ Route المكسور** (راجع الملاحظة أدناه)، ولا يستخدم `route('domains.check')` ولا أي AJAX. هذا يعني وجود **ثلاث واجهات بحث دومين منفصلة ومتوازية فعلياً** في نفس المشروع: (١) `front/sections/templates/domain_search.blade.php` (الـ Section الرسمي المعتمَد، غير وظيفي حالياً)، (٢) `components/template/sections/search-domain.blade.php` (الودجت الفعّال عبر `domains.check`)، (٣) `components/template/sections/domains_showcase.blade.php` (Section فعّال ومسجَّل، لكن هدفه Route مكسور). هذه الازدواجية الثلاثية **تحتاج قرار توحيد صريح** (راجع القرار الجديد رقم 11 في المرحلة 0.5 أدناه) — لا يُنفَّذ أي حل ضمن هذا التحديث التوثيقي.
- **ملفات يتيمة (مؤكَّدة 100%)**: `client/domain-name-search.blade.php` (يستدعي `<livewire:domain-client />`) و`client/domain-table-client.blade.php` (يستدعي `<livewire:domain-table-client />`) — **مؤكَّد بالبحث الشامل: لا يوجد أي كلاس Livewire بأي من الاسمين `domain-client` أو `domain-table-client` في المشروع بأكمله**، ولا أي Route يُشير لأي من هذين الملفين مباشرة (البحث الوحيد الآخر لهما كان داخل هذه الوثيقة نفسها وملف `structure.txt` — وهو مجرد تفريغ لشجرة الملفات، ليس كوداً منفَّذاً).
- **تحديث معماري معتمَد من Project Owner (2026-07-21)**: **واجهة البحث الرسمية عن الدومينات ليست صفحة مستقلة**، وإنما Section ضمن نظام Page Builder — الملف المعتمَد هو `resources/views/front/sections/templates/domain_search.blade.php`. أي صفحة مستقلة مثل `resources/views/domains/search.blade.php` **ليست جزءاً من التصميم المستهدف لهذا المشروع**.
- **تحديث حرج (مراجعة معمارية 2026-07-22) — إعادة تصنيف Route `domains.page`**: `DomainSearchController::page()` (السطر 16-19) لا يزال يُعيد `return view('domains.search');`، والملف `resources/views/domains/search.blade.php` **غير موجود على الإطلاق** (مؤكَّد). التصنيف السابق لهذا الـ Route في هذه الوثيقة (`Legacy / Needs Confirmation`) استند إلى عدم العثور على دليل استخدام فعلي وقت فحص المرحلة 0 — **وهذا الاستنتاج ثبت خطؤه بمراجعة معمارية لاحقة (Current Architecture Inventory، 2026-07-22)**. الدليل المؤكَّد الجديد: الرابط `route('domains.page')` **مستخدَم فعلياً وبشكل حي** في: (أ) فوتر الموقع `resources/views/front/layouts/footers/palgoals_marketing.blade.php` (رابط ظاهر بعنوان "دومين"/"Domains")؛ (ب) القائمة الافتراضية `resources/views/front/layouts/partials/footer/menu-links.blade.php` (تُستخدَم كلما لا توجد قائمة مخصَّصة)؛ (ج) نموذج بحث حقيقي داخل Section مسجَّل فعلياً `components/template/sections/domains_showcase.blade.php` يُرسِل طلب `GET` مباشرة إلى هذا الـ Route. **النتيجة العملية المؤكَّدة**: أي زائر يضغط أياً من هذه الروابط أو يستخدم نموذج البحث في `domains_showcase` سيصطدم بخطأ فعلي (View غير موجود)، وليس مجرد كود Legacy غير مستخدَم. **التصنيف الجديد: `Active Broken Route / Functional Blocker`** (إلغاء تصنيف `Legacy / Needs Confirmation` السابق لهذا البند تحديداً). **توضيح مهم**: إنشاء صفحة `resources/views/domains/search.blade.php` **ليس الحل المعتمَد تلقائياً** — القرار بين ثلاثة خيارات (توجيه الرابط لصفحة تحتوي الـ Section الرسمي / تعديل `domains_showcase` لاستخدام `domains.check` / إزالة أو تعطيل الـ Route والروابط القديمة) يبقى معلَّقاً ويُحسم صراحة في المرحلة 0.5 (القرار الجديد رقم 11 أدناه) قبل أي تنفيذ في المرحلة 3.

#### إعدادات (Config) — مؤكَّد
- **مؤكَّد بـ Glob: لا يوجد `config/domains.php`** في المشروع. استدعاءات مثل `config('domains.providers.cloudflare.live', 'https://api.cloudflare.com/client/v4')` الموجودة في `DomainProviderRequest::resolveEndpoint()` تعتمد بالكامل على القيمة الافتراضية الثانية (fallback) لأن مفتاح `domains.*` غير موجود أصلاً في أي ملف config — هذا **متوافق مع قرار عدم وجود ملف تهيئة مستقل** وليس خطأً، لكنه يستحق توضيحاً بأن هذه الاستدعاءات تعمل فقط بفضل الـ fallback الثابت المكتوب مباشرة في الكود. بيانات الاعتماد كلها في جدول `domain_providers` (مشفّرة)، تُدار من لوحة الإدارة فقط، وليس عبر `.env` — قرار معماري قائم مؤكَّد ويجب احترامه.

### نطاق العمل المطلوب في هذه المرحلة (Checklist)

- [x] مراجعة وتأكيد كل بند في الجدول أعلاه عبر قراءة مباشرة للكود الفعلي الحالي (migrations، Models، Controllers، Services، Routes، Seeders) — **لكن ليس "تشغيلاً فعلياً في بيئة Staging"** كما كان مطلوباً حرفياً في الصياغة الأصلية. **السبب:** بيئة التنفيذ الحالية (sandbox القراءة) لا تملك اتصال شبكة بأي قاعدة بيانات (تم اختبار المنفذ 3306 على `127.0.0.1` فعلياً وأعاد "Connection refused")، ولا تملك PHP مثبتاً (`php -v` أعاد "No such file or directory")، وبالتالي لا يمكن تشغيل `php artisan` أو أي اختبار Staging فعلي من هذه البيئة. لا توجد معلومات عن بيئة Staging منفصلة يمكن الوصول إليها ضمن نطاق هذا الفحص.
- [ ] تحديد ما إذا كان Enom لا يزال مزوّداً نشطاً فعلياً في قاعدة بيانات الإنتاج (`domain_providers` حيث `type=enom AND is_active=1`) — **تعذّر التحقق**. السبب: لا يوجد اتصال بقاعدة بيانات الإنتاج ولا PHP/artisan tinker من بيئة التنفيذ (نفس القيد أعلاه). ملف `.env` الموجود في نسخة العمل يشير إلى `APP_ENV=production` لكن `DB_HOST=127.0.0.1`/`DB_DATABASE=palgoalsnewtest1` (قاعدة بيانات محلية تجريبية غير قابلة للوصول من بيئة الفحص) — وهذا بحد ذاته **تناقض في تصنيف البيئة يستحق تنبيه Project Owner** (راجع "نتائج التحقق الميداني" أدناه). هذا البند يبقى **الحاجز الرئيسي أمام إغلاق المرحلة 0**.
- [ ] فحص عدد الدومينات الفعلية المسجَّلة عبر `RegistrarProvisioningService` في الإنتاج (سجلات/فواتير) للتأكد من مستوى النضج الفعلي لمسار التسجيل — **تعذّر التحقق لنفس سبب غياب الوصول لقاعدة البيانات**. تم بدلاً من ذلك تأكيد المسار البرمجي الكامل نظرياً بقراءة الكود (راجع "التسجيل الفعلي" أعلاه ومستوى النضج المصنَّف "Not Confirmed" أدناه).
- [x] حصر كل الأماكن التي تستدعي `route('domains.check')` أو تتفرّع على `registrar` كنص حر، تمهيداً للمرحلة 1 — **منجَز بالكامل**، النتائج مفصَّلة في "نتائج التحقق الميداني" (قسم Routes/Services) أدناه.
- [x] القرار الرسمي بشأن كل عنصر "كود ميت/يتيم": **تم حصر كل العناصر وتوثيقها بدليل مؤكَّد في هذه المرحلة (بما فيها عنصر مكتشَف: Route `domains.page` لمسار `/domains` — مصنَّف الآن `Active Broken Route / Functional Blocker` بعد مراجعة معمارية لاحقة (2026-07-22) أثبتت استخداماً فعلياً حياً له من الفوتر والقائمة الافتراضية ونموذج `domains_showcase`؛ راجع R-12 وP-06 المُحدَّثين)** — لكن **القرار النهائي (إبقاء/حذف/إحياء) لكل عنصر يبقى ضمن المرحلة 0.5، القرار رقم 10، وحالته لا تزال `Pending` كما هي** (لم تُغيَّر في هذا التحديث، بحسب التعليمات).
- [x] التحقق من كل الترجمات (`t()`) المستخدمة في شاشات الدومين — **منجَز**. تم اكتشاف **مخالفات فعلية مؤكَّدة**: استخدام `__()` (ممنوع صراحة في `CLAUDE.md`) في `resources/views/dashboard/management/domains/{renew,register,dns}.blade.php` (69 استدعاء `__()` مقابل 17 فقط لـ `t()` عبر 4 ملفات في نفس المجلد)، وغياب كامل لأي `t()` في `front/sections/templates/domain_search.blade.php` (نص hardcoded 100%). كما تم تأكيد غياب `dir="ltr" font-mono"` عن حقول `username`/`api_token`/`api_key`/`client_ip` في `dashboard/management/domain_providers/create.blade.php` — مخالفة إضافية مؤكَّدة لقاعدة UX الخاصة بحقول IP/Token. التفاصيل الكاملة في "نتائج التحقق الميداني" (قسم الترجمة).
- [x] جرد صلاحيات WHM/Reseller ذات الصلة — **منجَز، لا يوجد تقاطع فعلي**. الكلمة "reseller" الظاهرة داخل `EnomClient.php` (مثل `reseller.enom.com`, `PE_GetResellerPrice`) هي مصطلح خاص بواجهة Enom كـ"مسجِّل دومينات Reseller" ولا علاقة له إطلاقاً بصلاحيات WHM/cPanel Reseller الموثَّقة في `CLAUDE.md` لخدمات الاستضافة. تم تأكيد عدم وجود أي إشارة لدومينات داخل `ServerController.php` (المسؤول عن حزم WHM). الأنظمة الثلاثة (تسجيل الدومينات، Hosting Provisioning، Tenancy Domain Verification عبر أعمدة `subscriptions.domain_verification_*`) **مؤكَّد أنها منفصلة كودياً بالكامل ولا تتشارك أي منطق مباشر**.

### نتائج التحقق الميداني

> نُفِّذ هذا التحقق في 2026-07-21 كبحث كودي مباشر (Read-only) من بيئة عمل معزولة (sandbox) متصلة بنسخة العمل المحلية للمشروع، دون أي اتصال بقاعدة بيانات حية أو تنفيذ PHP.

**Environment**
- الفرع الحالي: `main`. `git status --short` أعاد ناتجاً فارغاً (شجرة عمل نظيفة) في أول فحص خلال هذه الجلسة، قبل أي تعديل على هذه الوثيقة.
- إصدار Laravel: `v12.58.0` (من `composer.lock`)، `laravel/framework: ^12.0` في `composer.json`.
- **لا يوجد PHP مثبَّت في بيئة التنفيذ** (`php -v` → "No such file or directory") — مؤكَّد، يطابق ملاحظة `CLAUDE.md` ("PHP غير متوفر في sandbox — كل أوامر artisan تُشغَّل على جهاز المستخدم مباشرة").
- ملف `.env` الخاص بنسخة العمل: `APP_ENV=production`, `APP_DEBUG=true`, `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`, `DB_DATABASE=palgoalsnewtest1`. **لم تُعرَض أي قيم حساسة أخرى (لا كلمات مرور، لا مفاتيح).**
- **تناقض مؤكَّد يستحق تنبيه Project Owner**: `APP_ENV=production` بينما `DB_DATABASE` تحمل اسم قاعدة بيانات تجريبية (`palgoalsnewtest1`) على `127.0.0.1` — هذا يطابق ما ورد في `CLAUDE.md` كقاعدة بيانات الاختبار المحلية، وليس واضحاً ما إذا كان هذا ملف `.env` فعلي للإنتاج أم نسخة عمل محلية بعلم `APP_ENV` غير مُحدَّث. **لم يكن ممكناً حسم هذا التناقض ضمن نطاق هذا الفحص.**
- محاولة فتح اتصال TCP مباشر إلى `127.0.0.1:3306` من بيئة التنفيذ أعادت **"Connection refused"** — مؤكَّد عبر اختبار مباشر، وليس افتراضاً.
- **الوصول إلى Staging: تعذّر التأكد من وجوده أصلاً.** لا يوجد أي إعداد أو توثيق ضمن نطاق هذا الفحص يشير إلى عنوان/بيانات بيئة Staging منفصلة يمكن الاتصال بها من هذه البيئة.
- تصنيف بيانات مزوّدي الدومينات الحالية (test/live/خليط): **تعذّر التحقق** لنفس سبب غياب اتصال قاعدة البيانات. المعروف من الكود الثابت فقط: القيمة الافتراضية لعمود `mode` في الموديل هي `test` (آمنة)، و`DomainProviderSeeder.php` (إن شُغِّل) يُنشئ 3 مزوّدين تجريبيين (`enom`/`test`, `namecheap`/`test`, `cloudflare`/`test`) ببيانات وهمية واضحة (`enom_test_pass`, `sandbox_api_key`, `cloudflare_test_token`) — لكن **لا يمكن التأكد إن كانت هذه هي السجلات الفعلية الموجودة حالياً في قاعدة بيانات الإنتاج، أو أن سجلات `live` حقيقية أُضيفت لاحقاً من لوحة الإدارة.**

**Database findings**
- ملخَّص في الجدول أعلاه ("قاعدة البيانات — مؤكَّد عبر ملفات الـ Migrations"). لا يوجد `domain_orders` أو `domain_search` (مؤكَّد ببحث شامل). `template_id` على `domains` كود ميت مؤكَّد (محذوف من الجدول، لا يزال في `$fillable`). `Domain::checkAvailability()` دالة زائفة تُعيد `true` دوماً (كود ميت/مضلِّل إضافي مكتشَف حديثاً).

**Provider findings**
- جدول القدرات الفعلي:

| Capability | Namecheap | Enom | مكان التنفيذ |
|---|---|---|---|
| Balance | ✅ `NamecheapClient::getBalance()` | ✅ `EnomClient::getBalance()` | كلا الكلاسين |
| Availability Check | ✅ لكن **خارج** الكلاس، في `DomainSearchController::namecheapCheck()` | ❌ **غير موجودة في `EnomClient`** — استدعاء `checkAvailability()` من `DomainSearchController::enomCheck()` (سطر 235) يفشل دائماً (مُلتقَط بـ try/catch، لا كسر كامل) | `DomainSearchController` (كلا المسارين) |
| Pricing | عبر الكتالوج المحلي (`domain_tld_prices`) + Namecheap API عام (`callGeneric`) | ✅ `getProductPrice/getRetailPrice/getResellerPrice/getAnyPrice` | `NamecheapClient` (جزئي) + `EnomClient` (كامل) |
| Register | ✅ عبر `callGeneric('namecheap.domains.create', …)` (لا دالة مخصَّصة) | ✅ `purchaseDomain()` | `RegistrarProvisioningService::registerDomainWithProvider()` |
| Renew | ✅ `renewDomain()` | ✅ `renewDomain()` | كلا الكلاسين + `RegistrarProvisioningService::renewDomainWithProvider()` |
| Nameservers | ✅ `setCustomNameservers/getNameserverInfo/createNameserver/updateNameserver/getNameservers` | ✅ `checkNameserverStatus/registerNameserver/updateNameserverIp/updateNameservers` | كلا الكلاسين + `DomainDnsService` |
| DNS | ✅ `getNameservers()` (استخدام كـ"قراءة" DNS) | ✅ `getDns()` | `DomainDnsService::fetchRemoteDnsSnapshot()` |
| Glue Records | ✅ Personal DNS عبر `ensureNamecheapPersonalNameserver()` | ✅ عبر `checkNameserverStatus/registerNameserver/updateNameserverIp` | `DomainDnsService::ensureSubordinateHostsReady()` |
| Test Connection | ✅ `getBalance()` عبر `DomainProviderController::testConnection()` | ✅ `getBalance()` عبر نفس الدالة | `DomainProviderController::testConnection()` (لا حالة `cloudflare`) |

- `Cloudflare`: لا Client فعلي (مؤكَّد بـ Glob)، لكنه مدعوم بالكامل في التحقق (`DomainProviderRequest`) والواجهة (`create`/`edit` blade) ومُنشَأ فعلياً كمزوّد نشط بوضع اختبار عبر `DomainProviderSeeder` — تفاصيل كاملة في سجل المخاطر العام (R-10 المُحدَّث).
- لا يوجد Factory/Resolver/Interface موحَّد — مؤكَّد، التفريع يدوي في 4 ملفات على الأقل.

**Registration maturity**
- المسار البرمجي الكامل (Order → Invoice → `markPaid()` → `OrderActivationService` → `RegistrarProvisioningService`) **مؤكَّد ومفهوم بالكامل من الكود**، بما في ذلك اكتشاف جديد مهم: **`markPaid()` تُنفِّذ تسجيل الدومين داخل نفس معاملة قاعدة البيانات (`DB::transaction`) التي تُحدِّث حالة الدفع، وفشل التسجيل يرمي استثناء يُراجِع (rollback) حالة الفاتورة/الطلب رغم نجاح الدفع الفعلي خارجياً** — دليل كودي مباشر يدعم R-07.
- **مستوى النضج**: **Not Confirmed**. لا يمكن الاستدلال على نجاح أي تسجيل خارجي فعلي (لدى Namecheap/Enom الحقيقيين) من الكود وحده؛ هذا يتطلب فحص سجلات `domains`/`orders`/الفواتير الفعلية أو سجلات اللوج الحقيقية على السيرفر — وكلاهما غير متاح من بيئة هذا الفحص. **لا يوجد أي دليل كودي (تعليق، اختبار، تقرير) يوثِّق نجاح تسجيل خارجي حقيقي حتى الآن.**

**Routes and services**
- Routes مؤكَّدة (راجع الجدول الكامل في التقرير النهائي المُرفَق مع هذا التحديث) — Admin: `domains.*` (resource + register/renew/dns مخصَّصة)، `domain_providers.*` (resource + test-connection)، `domain-tlds.*` (index/sync/update-sale/save-catalog/save-all/apply-pricing/destroy/bulk-destroy). Front: `/domains` (`domains.page` — **Active Broken Route / Functional Blocker، راجع أدناه**، الواجهة الرسمية للبحث أصبحت Section ضمن Page Builder وليست هذا الـ Route)، `/api/domains/check` (`domains.check`). Client: `domains.search/search.process/buy/purchase/auto-renew/renew/dns.edit/dns.update` + `Route::resource('domains', …)`.
- Services مؤكَّدة: `RegistrarProvisioningService`, `DomainDnsService`, `DomainRenewalService` تحت `app/Services/Domains/`.
- Command مؤكَّد: `domains:process-auto-renewals` (closure في `routes/console.php`، ليس Command class منفصلة) — مجدول `dailyAt('02:00')` مع `withoutOverlapping()` فقط (بدون `onOneServer()`، بدون `Log::` صريح).
- **ملاحظة (مُحدَّثة بمراجعة معمارية 2026-07-22)**: Route `domains.page` (`GET /domains`) يُعيد `view('domains.search')` وهذا الملف غير موجود في المشروع. خلافاً للاستنتاج السابق في هذه الوثيقة، ثبت أن هذا الـ Route **نشط ومستخدَم فعلياً** عبر روابط الفوتر/القائمة الافتراضية ونموذج `domains_showcase` (تفصيل كامل في قسم "الواجهة الأمامية" أعلاه، وR-12 في سجل المخاطر). **التصنيف الجديد: `Active Broken Route / Functional Blocker`** — لم يعد يُصنَّف `Legacy / Needs Confirmation`. القرار بشأن الحل (توجيه/تعديل `domains_showcase`/إزالة) معلَّق للمرحلة 0.5 (القرار 11).

**Legacy/orphaned files**
| الملف | الحالة المؤكَّدة |
|---|---|
| `resources/views/dashboard/domains.blade.php` | **Orphaned مؤكَّد 100%** — موسوم `@deprecated` صراحة، يستدعي `<livewire:domain-component />` غير الموجود، ولا يوجد أي Route/Controller يعرضه حالياً. |
| `resources/views/client/domain-name-search.blade.php` | **Orphaned مؤكَّد 100%** — يستدعي `<livewire:domain-client />` غير الموجود، لا Route يشير إليه. |
| `resources/views/client/domain-table-client.blade.php` | **Orphaned مؤكَّد 100%** — يستدعي `<livewire:domain-table-client />` غير الموجود، لا Route يشير إليه. |
| `legacy/visual-builder/resources/views/components/template/sections/search-domain.blade.php` | **Deprecated/غير محمَّل مؤكَّد** — لا تسجيل view-path لمجلد `legacy/` في أي Service Provider. |
| `resources/views/domains/search.blade.php` (متوقَّع سابقاً من `DomainSearchController::page()`) | **الملف غير موجود فعلاً** (مؤكَّد). الصفحة المستقلة **ليست التصميم المستهدف مستقبلاً** (واجهة البحث الرسمية تبقى Section ضمن Page Builder). **لكن غياب هذا الملف حالياً يسبب عطلاً وظيفياً فعلياً**، لأن Route `domains.page` وروابط حية (فوتر الموقع، القائمة الافتراضية، نموذج `domains_showcase`) لا تزال تعتمد عليه فعلياً — راجع R-12 وP-06 المُحدَّثين. **المشكلة الحقيقية الحالية ليست ضرورة إنشاء صفحة مستقلة دائمة، بل الروابط والـ Route النشط الذي يستهدف ملفاً غير موجود.** التصنيف: `Active Broken Route / Functional Blocker` (لم يعد `Not Applicable`). |
| `App\Models\Domain::checkAvailability()` | **Dead/Misleading code مؤكَّد** — دالة ساكنة تُعيد `true` دائماً، غير مستخدَمة من مسار الفحص الحقيقي. |
| `App\Models\Domain::$fillable['template_id']` وعلاقة `template()` | **Dead code مؤكَّد** — العمود محذوف من الجدول فعلياً. |

**Tests**
- **مؤكَّد بالبحث الشامل: صفر (0) اختبارات لأي جزء من نظام الدومينات** في كامل المشروع (لا Domain، لا Registrar، لا Provider، لا TLD، لا Auto-Renew). ملفات الاختبار الوحيدة الموجودة في المشروع (8 ملفات إجمالاً تحت `tests/`) غير متعلقة إطلاقاً بالدومينات (`ExampleTest`, `SectionDefinitionFrontendViewDataFactoryTest`, `AdminSectionWorkspaceBrandSettingsTest`, `MenuManagementTest`, `SafeUrlTest`).
- **لم يتم تشغيل أي اختبار** — لا يوجد PHP/Composer/PHPUnit-Pest قابل للتشغيل من بيئة هذا الفحص، وبما أنه لا توجد أصلاً أي اختبارات مكتوبة لهذا النظام، فالسؤال عن "الاختبارات الآمنة التي تم تشغيلها" لا ينطبق (0 اختبارات موجودة، 0 اختبارات نُفِّذت).

**Security baseline (تمهيدي فقط، ليس بديلاً عن المرحلة 7)**
- فحص ثابت لكل استدعاءات `Log::` داخل `app/Services/Domains/**` و`DomainProviderController::testConnection()`: **لم يظهر أي تسجيل لقيمة خام لـ password/api_key/api_token**. أقصى ما يُسجَّل: `username`, `client_ip`, `provider_id`, `provider_type`, ورسائل الأخطاء/الاستثناءات. `NamecheapClient::request()` يسجِّل `'api_key_len' => strlen(...)` (طول المفتاح فقط، وليس قيمته) — ممارسة آمنة.
- **قيد صريح**: هذا فحص لسطور الكود فقط ضمن نطاق محدود (خدمات الدومينات + متحكم اختبار المزوّد)، وليس فحصاً لملفات اللوج الفعلية على القرص، ولا يغطي كل نقطة محتملة في المشروع بأكمله. هذا Baseline تمهيدي لـ R-08، والمرحلة 7 وحدها تُعتبر التدقيق الكامل والنهائي.

**Unverified items (البنود التي تعذّر التحقق منها فعلياً)**
1. هل `domain_providers` يحتوي مزوّد Enom نشطاً (`type=enom AND is_active=1`) في بيانات الإنتاج الفعلية؟ — **تعذّر**، لا اتصال بقاعدة بيانات.
2. عدد الدومينات/الطلبات الفعلية ومستوى نضج التسجيل الحقيقي (نجاح خارجي موثَّق) — **تعذّر**، لا اتصال بقاعدة بيانات ولا وصول لملفات اللوج الفعلية على السيرفر.
3. تصنيف بيانات مزوّدي الدومينات الحالية (test/live/خليط) في قاعدة الإنتاج — **تعذّر**، نفس السبب.
4. الوصول الفعلي لبيئة Staging والتحقق التشغيلي (وليس القرائي فقط) لكل بند في الجدول — **تعذّر**، لا توجد بيئة Staging معروفة/متاحة ضمن نطاق هذا الفحص، ولا PHP لتشغيل أي أمر حتى لو توفَّر اتصال.
5. هل ملف `.env` الحالي (`APP_ENV=production` مع قاعدة بيانات محلية تجريبية) يعكس فعلاً بيئة إنتاج حقيقية أم نسخة عمل محلية بعلم غير مُحدَّث؟ — **تعذّر الحسم**، يحتاج توضيحاً مباشراً من Project Owner.

### ترتيب المشاكل حسب الأولوية

| ID | المشكلة | الأولوية | الدليل | المرحلة المستهدفة | الحالة |
|---|---|---|---|---|---|
| P-01 | `EnomClient::checkAvailability()` غير موجودة → فحص توفر Enom معطَّل 100% (فشل مُلتقَط، JSON خطأ، ليس 500) | Blocker (مشروط بنشاط Enom في الإنتاج — غير مؤكَّد) | `DomainSearchController.php:235`, `EnomClient.php` (بحث كامل، لا دالة) | المرحلة 1 | Open |
| P-02 | تسجيل الدومين يتم داخل نفس `DB::transaction` الخاصة بتفعيل الدفع؛ فشل التسجيل يُراجِع حالة الفاتورة رغم نجاح الدفع الخارجي فعلياً | Blocker | `InvoiceSettlementService.php:40-141` (خصوصاً السطر 125-134), تعليق "fires only after real payment" | المرحلة 5 | Open |
| P-03 | صفر اختبارات لكامل نظام الدومينات (بحث/تسعير/تسجيل/تجديد/DNS/مزوّدين) | High | بحث شامل في `tests/` (0 نتائج) | المرحلة 8 / عاجل | Open |
| P-04 | بيانات Contact وهمية (Placeholder) تُستخدَم بصمت بلا Validation تمنع التسجيل، بما فيها Fax ثابت `0000000000` لعملاء Enom دائماً | High | `RegistrarProvisioningService.php` (`buildRegistrarContactPayload`, `formatRegistrarPhone`, `expandContactForEnom`) | المرحلة 5 | Open |
| P-05 | Cloudflare مدعوم بالكامل في الواجهة والتحقق ويُنشَأ **نشطاً** افتراضياً عبر Seeder، بلا أي Client فعلي خلفه | High | `DomainProviderSeeder.php`, `DomainProviderRequest.php`, `create/edit.blade.php`, غياب `CloudflareClient` | المرحلة 0.5 (القرار 3) / المرحلة 1 | Open |
| P-06 | Route حي (`domains.page` → `/domains`) يعيد `view('domains.search')` غير الموجود إطلاقاً، **وثبت بمراجعة معمارية لاحقة (2026-07-22) أنه مستخدَم فعلياً وبشكل حي** من فوتر الموقع، والقائمة الافتراضية، ونموذج بحث داخل Section مسجَّل (`domains_showcase.blade.php`) — أي زائر يستخدم أياً منها يُصادف خطأ فعلي. | **Blocker مؤكَّد** (سابقاً: Low / Needs Confirmation — تصنيف مُلغى) | `DomainSearchController.php:16-19`, `routes/web.php:145-146`, `front/layouts/footers/palgoals_marketing.blade.php`, `front/layouts/partials/footer/menu-links.blade.php`, `components/template/sections/domains_showcase.blade.php`, غياب `resources/views/domains/search.blade.php` بالكامل | المرحلة 0.5 (القرار 11) ثم المرحلة 3 | Open |
| P-07 | تعذّر تأكيد نشاط Enom في الإنتاج ونضج التسجيل الفعلي بسبب غياب الوصول لقاعدة البيانات/Staging من بيئة الفحص | Blocker (لإغلاق المرحلة 0 نفسها) | لا اتصال DB (`Connection refused` على 3306)، لا PHP في sandbox | المرحلة 0 (هذه المرحلة) | Open |
| P-08 | مخالفات ترجمة مؤكَّدة: `__()` بدل `t()` في 3 ملفات إدارة دومين (69 استدعاء)، ونص hardcoded كامل في `domain_search.blade.php`، وغياب `dir="ltr" font-mono"` في نموذج مزوّد جديد | Medium | `domains/{renew,register,dns}.blade.php`, `front/sections/templates/domain_search.blade.php`, `domain_providers/create.blade.php` | تنظيف مستقل (لا يعطّل مرحلة وظيفية) | Open (جديد) |
| P-09 | كود ميت مؤكَّد: `Domain::checkAvailability()` تُعيد `true` دائماً، و`template_id` في `$fillable`/علاقة `template()` لعمود محذوف | Low | `App\Models\Domain.php` | المرحلة 0.5 (القرار 10) | Open |
| P-10 | لا `->onOneServer()` على جدولة `domains:process-auto-renewals`، ولا `Log::` صريح داخل الأمر | Low | `routes/console.php:20-35, 815-817` | المرحلة 6 | Open |

### الملفات المتوقع الاطلاع عليها (بدون تعديل)
كل الملفات المذكورة في جدول الجرد أعلاه، بالإضافة إلى: `database/seeders/*Domain*`, أي اختبارات موجودة تحت `tests/` تخص الدومينات (يجب حصرها).

### التبعيات
لا يوجد — هذه أول مرحلة.

### المخاطر
- الاعتماد على قراءة كود فقط دون تشغيل فعلي قد يُخفي أخطاء وقت التشغيل (مثل خلل `EnomClient::checkAvailability`).
- بيانات اعتماد حقيقية للمزوّدين قد تكون بوضع `live` في قاعدة بيانات الإنتاج — أي اختبار فعلي في هذه المرحلة **يجب أن يتم فقط على مزوّد بوضع `test/sandbox`** لتفادي عمليات شراء/رسوم حقيقية غير مقصودة.

### معايير النجاح
- وثيقة جرد نهائية معتمدة (يمكن أن تكون تحديثاً لهذا القسم نفسه) بدون علامات "يحتاج تأكيد" **غامضة** متبقية — **مُحقَّق جزئياً**: كل بند الآن إما مؤكَّد بدليل أو معلَّم صراحة "تعذّر التحقق" مع سبب دقيق، لكن بندين جوهريين (نشاط Enom في الإنتاج، ونضج التسجيل الفعلي) **لا يزالان بلا إجابة قاطعة** بسبب قيد بيئة التنفيذ (لا اتصال DB، لا PHP)، وليس بسبب نقص في الجهد.
- قائمة واضحة بالمشاكل المرتّبة حسب الأولوية (Blocker / High / Medium / Low) — **مُحقَّق**، راجع "ترتيب المشاكل حسب الأولوية" أعلاه (10 بنود).
- قرار موثّق لكل عنصر كود ميت/يتيم — **حُصرت العناصر وتم توثيقها بدليل، لكن القرار النهائي لكل عنصر يبقى في المرحلة 0.5 (القرار رقم 10) بحالته `Pending` كما هي**؛ هذا البند لا يُعتبر "منجزاً" بالمعنى الكامل (حسم القرار) حتى تُعتمد المرحلة 0.5.

### كيفية اختبار المرحلة
لا يوجد كود يُختبر؛ الاختبار هنا هو مراجعة (Review) من مالك المنتج/المطوّر المسؤول لصحة الجرد ومطابقته للواقع. **تم تنفيذ الجزء القابل للتحقق آلياً من هذه المراجعة (قراءة كود مباشرة، بحث شامل، فحص بيئة) في هذا التحديث؛ الجزء غير القابل للتنفيذ من بيئة الفحص الحالية (تشغيل فعلي على Staging، استعلام قاعدة بيانات حية) موثَّق صراحة كبند غير مكتمل أعلاه.**

### ملاحظات مهمة
- هذه المرحلة **لا تُنتج أي كود**. أي إصلاح يُكتشف أثناءها (مثل خلل Enom، أو مسار `markPaid()`/التسجيل، أو Route `domains.page` القديم) يُسجَّل كبند في المرحلة المناسبة (غالباً المرحلة 1 أو 5، أو كتنظيف Legacy مستقل) ولا يُنفَّذ فوراً إلا إذا اتُّفق صراحة على استثناء Blocker. **لم يُعدَّل أي ملف كود ضمن هذا التحديث — فقط هذه الوثيقة.**
- **تحديث معماري معتمَد (2026-07-21)**: واجهة البحث الرسمية عن الدومينات هي Section ضمن Page Builder (`front/sections/templates/domain_search.blade.php`) وليست صفحة مستقلة. هذا القرار نفسه لم يتغيّر. **لكن تحديث لاحق (مراجعة معمارية 2026-07-22) ألغى تصنيف الـ Route القديم (`domains.page`) من `Legacy / Needs Confirmation` إلى `Active Broken Route / Functional Blocker` (P-06، R-12)** — لأن هذا الـ Route ثبت أنه لا يزال مستخدَماً فعلياً وبشكل حي من فوتر الموقع والقائمة الافتراضية ونموذج Section مسجَّل (`domains_showcase`)، وليس مجرد بقايا كود قديم بلا استخدام. غياب `resources/views/domains/search.blade.php` ليس بالضرورة يعني وجوب إنشاء صفحة مستقلة دائمة — الحل يُحسم في المرحلة 0.5 (القرار الجديد رقم 11) بشأن توحيد واجهات البحث الثلاث.
- **حاجز الإغلاق الوحيد المتبقي فعلياً لهذه المرحلة هو الوصول التشغيلي لبيئة تحتوي بيانات حية** (Staging أو نسخة من الإنتاج) لتنفيذ استعلامين محدَّدين: حالة نشاط Enom، وعدد/حالة الدومينات والطلبات الفعلية. كل ما عدا ذلك من بنود الجرد **مؤكَّد بالكامل بدليل كودي مباشر**.

### Definition of Done
- تم تنفيذ جميع بنود الـ Checklist أعلاه فعلياً (تحقق ميداني/تشغيلي، وليس قراءة كود فقط) على بيئة Staging — **غير مُحقَّق بالكامل**: التحقق الكودي (قراءة مباشرة + بحث شامل) مُنجَز 100%؛ التحقق التشغيلي على Staging **لم يُنفَّذ** لعدم توفر بيئة Staging متاحة أو بيانات دخول لها ضمن نطاق هذا الفحص.
- تم تأكيد أو نفي حالة نشاط Enom في قاعدة بيانات الإنتاج (`domain_providers.type=enom AND is_active=1`) بشكل قاطع وموثَّق — **غير مُحقَّق**: تعذّر الاتصال بأي قاعدة بيانات من بيئة التنفيذ (تم اختبار ذلك فعلياً، النتيجة "Connection refused").
- لا تبقى أي علامة "يحتاج تأكيد" في نتائج الجرد أعلاه بدون إجابة موثّقة صراحة داخل هذا القسم — **مُحقَّق**: كل بند إما مؤكَّد أو مُعلَّم "تعذّر التحقق" مع سبب محدَّد (راجع "Unverified items" أعلاه).
- كل عناصر الكود الميت/اليتيم المذكورة أعلاه مُدرَجة كبند قرار في المرحلة 0.5 (وليست متروكة بلا مسار قرار) — **مُحقَّق للعناصر الأصلية**؛ دالة `Domain::checkAvailability()` الزائفة لا تزال ضمن القرار رقم 10 (Non-blocking) في المرحلة 0.5. أما Route `domains.page` (بعد إعادة تصنيفه إلى `Active Broken Route / Functional Blocker` — راجع R-12 وP-06 المُحدَّثين) **فقد أُضيف له الآن قرار صريح مستقل في المرحلة 0.5 (القرار 11، Blocking Level = `Blocking for Phase 3`)** يغطي أيضاً روابط الفوتر/القائمة الافتراضية وSection `domains_showcase` وازدواجية واجهات البحث الثلاث — راجع القرار الجديد داخل قسم المرحلة 0.5.
- قائمة المشاكل مرتّبة حسب الأولوية (Blocker / High / Medium / Low) ومنعكسة في سجل المخاطر العام أعلاه — **مُحقَّق** (جدول الأولويات + تحديث R-01/R-08/R-10 في سجل المخاطر العام).
- يتم تحديث حالة المرحلة (عمود "الحالة") إلى `Completed` في جدول "متابعة تقدم التنفيذ" بعد اكتمال الجرد وإغلاق كل البنود أعلاه — **لم يتم**؛ الحالة أُبقيت `Blocked` (راجع الجدول أدناه) لعدم اكتمال بندي Enom الإنتاج ونضج التسجيل.
- يتم تحديث عمود "الاعتماد" إلى `Approved` بعد موافقة Project Owner الصريحة على الجرد النهائي — لم يتغيّر، يبقى `Pending` كما هو (الاعتماد ليس قراراً تقنياً).

---

## المرحلة 0.5 — اعتماد القرارات المعمارية

### الهدف
حسم القرارات المعمارية الجوهرية التي تؤثر على كل مرحلة لاحقة، **قبل** بدء أي Refactor أو كتابة كود فعلي. هذه المرحلة وثائقية بالكامل مثل المرحلة 0 — ناتجها قرارات موثّقة ومعتمدة، لا كود.

> **تنبيه:** لا تُعتبر أي حالة أدناه معتمدة (`Approved`) تلقائياً بمجرد كتابتها في هذه الوثيقة. الحالة الافتراضية لكل قرار هي `Pending` حتى تصدر موافقة صريحة من Project Owner. القرارات المقترحة هنا هي توصيات للمراجعة، وليست قرارات نافذة.

### تصنيف القرارات حسب مستوى التعطيل (Blocking Level)

> **مبدأ أساسي:** المرحلة 0.5 **ليست بوابة واحدة** تُوقف كل شيء حتى تُحسم القرارات العشرة معاً. كل قرار يحمل حقل **Blocking Level** يحدّد بدقة أي مرحلة لاحقة يعطّلها فعلياً، بحيث يمكن البدء بمرحلة معيّنة بمجرد حسم القرارات التي تخصّها فقط، دون انتظار حسم القرارات التي تخص مراحل أخرى بعيدة. هذا التصنيف يعالج مباشرة الخطر R-11 المسجَّل في سجل المخاطر العام.

القيم المسموح استخدامها لحقل Blocking Level: `Blocking for Phase 1`, `Blocking for Phase 3`, `Blocking for Phase 4`, `Blocking for Phase 5`, `Non-blocking`, `Conditional`.

#### Blocking for Phase 1
- القرار 2: علاقة `domains.registrar`.
- القرار 3: مصير Cloudflare.
- القرار 4: عقد مزوّدي الدومينات.
- القرار 5: سياسة اختيار المزوّد.

#### Blocking for Phase 3
- القرار 11: مصير Route `domains.page` وتوحيد واجهات بحث الدومين الثلاث (أُضيف بتاريخ 2026-07-22 بعد مراجعة معمارية أثبتت أن هذا الـ Route حي ومكسور فعلياً — راجع R-12 وP-06).

#### Blocking for Phase 4
- القرار 1: استراتيجية تخزين الطلبات.
- القرار 6: مصدر السعر.
- القرار 7: لحظة تثبيت السعر.

#### Blocking for Phase 5
- القرار 8: فشل التسجيل بعد الدفع.
- القرار 9: مصدر بيانات WHOIS.

#### Non-blocking
- القرار 10: الكود القديم واليتيم.

**قاعدة بدء المراحل المترتبة على هذا التصنيف:**
- لا تبدأ المرحلة 1 قبل حسم جميع قرارات `Blocking for Phase 1` (القرارات 2، 3، 4، 5).
- لا تبدأ المرحلة 3 قبل حسم قرار `Blocking for Phase 3` (القرار 11).
- لا تبدأ المرحلة 4 قبل حسم جميع قرارات `Blocking for Phase 4` (القرارات 1، 6، 7).
- لا تبدأ المرحلة 5 قبل حسم جميع قرارات `Blocking for Phase 5` (القرارات 8، 9).
- القرارات `Non-blocking` (القرار 10) لا تعطّل بدء أي مرحلة وظيفية، لكنها يجب أن تُحسم قبل إغلاق المرحلة 8 نهائياً.

### القرارات المطلوب حسمها

#### القرار 1 — استراتيجية تخزين طلبات الدومين
- **الخيارات المتاحة:**
  (أ) الاستمرار في استخدام `orders`/`order_items` كما هو حالياً (النظام العام للطلبات، مع `type='domain'` وبيانات المزوّد/المدة داخل `meta` json).
  (ب) استحداث جدول مخصص `domain_orders` منفصل عن نظام الطلبات العام.
- **القرار المقترح:** الخيار (أ) — الاستمرار في `orders`/`order_items` حالياً، مع إعادة تقييم الأمر لاحقاً فقط إذا أثبتت المرحلة 4 عملياً أن حقل `meta` (json) غير كافٍ لتغطية احتياجات تتبّع عمليات الدومين المتعددة (تسجيل/تجديد/نقل) بشكل منفصل عن الطلب العام.
- **سبب القرار:** النظام الحالي يعمل فعلياً ومُعاد استخدامه بنجاح وفق جرد المرحلة 0؛ استحداث جدول موازٍ الآن يخالف قاعدة "إعادة استخدام البنية الحالية متى أمكن" دون دليل مثبت على القصور.
- **التأثير على المراحل اللاحقة:** يحدّد بنية المرحلة 4 (الطلبات والدفع) بالكامل؛ أي تغيير لاحق لهذا القرار سيتطلّب Migration بيانات لسجلات الطلبات الحالية.
- **هل يحتاج ADR منفصلاً؟** نعم — ADR-DOM-002 (Domain Order Storage Strategy).
- **Blocking Level:** `Blocking for Phase 4` — لا تبدأ المرحلة 4 (الطلبات والدفع) قبل حسم هذا القرار.
- **حالة القرار:** Pending

#### القرار 2 — علاقة `domains.registrar` بجدول `domain_providers`
- **الخيارات المتاحة:**
  (أ) البقاء كما هو الآن: `registrar` عمود نصي حر (غير مرتبط بـ FK).
  (ب) ربطه مستقبلاً بـ `domain_providers` عبر Foreign Key (`provider_id`).
- **القرار المقترح:** إعادة تقييمه ضمن المرحلة 1 بعد بناء طبقة التجريد الموحّدة؛ الميل المبدئي (غير نهائي) نحو الخيار (ب) لأنه يزيل الحاجة للمطابقة النصية (`namecheap`/`enom`) المتكررة في الكود ويمنع تضارب البيانات الناتج عن خطأ إملائي صامت.
- **سبب القرار:** الوضع الحالي (نص حر) يعمل، لكنه هش تقنياً — لا تكامل مرجعي (Referential Integrity)، ولا حماية من قيمة غير متطابقة مع أي مزوّد فعلي مسجَّل.
- **التأثير على المراحل اللاحقة:** يؤثر مباشرة على تصميم المرحلة 1 (الطبقة الموحّدة) والمرحلة 6 (إدارة الدومينات) وأي استعلام حالي يعتمد على `registrar` كنص.
- **هل يحتاج ADR منفصلاً؟** نعم — ADR-DOM-003 (Registrar Relation Strategy).
- **Blocking Level:** `Blocking for Phase 1` — لا تبدأ المرحلة 1 (توحيد طبقة مزوّدي الدومينات) قبل حسم هذا القرار.
- **حالة القرار:** Pending

#### القرار 3 — مصير `Cloudflare` ضمن `DomainProvider::TYPES`
- **الخيارات المتاحة:**
  (أ) الإبقاء عليه كخيار مستقبلي غير مفعَّل، مع توثيق واضح أنه غير مكتمل.
  (ب) إزالته/إخفاؤه مؤقتاً من واجهة اختيار المزوّد في لوحة الإدارة حتى يوجد Client فعلي يدعمه.
- **القرار المقترح:** الخيار (ب) — إخفاؤه من واجهة الإنشاء/التعديل في لوحة الإدارة إلى حين وجود Client فعلي، مع إمكانية إبقائه في الكود كقيمة موثَّقة بتعليق `TODO` واضح إن لم يكن الحذف الكامل مرغوباً.
- **سبب القرار:** ظهور خيار قابل للاختيار في لوحة الإدارة بلا أي تنفيذ فعلي خلفه يُنتج تجربة إدارية مضلِّلة ويحتمل اختياره بالخطأ من قِبل مدير النظام.
- **التأثير على المراحل اللاحقة:** تأثير محدود — يمس بشكل أساسي `DomainProviderController` (نموذج إنشاء/تعديل مزوّد) والمرحلة 1 فقط.
- **هل يحتاج ADR منفصلاً؟** اختياري — يمكن تضمينه ضمن ADR-DOM-001 (Provider Abstraction) بدل ADR مستقل قائم بذاته.
- **Blocking Level:** `Blocking for Phase 1` — لا تبدأ المرحلة 1 قبل حسم هذا القرار (لتفادي ظهور Cloudflare كخيار وهمي في نموذج إنشاء/تعديل مزوّد يُبنى ضمن المرحلة 1).
- **حالة القرار:** Pending

#### القرار 4 — العقد النهائي لطبقة مزوّدي الدومينات (عمليات إلزامية مقابل اختيارية)
- **الخيارات المتاحة:**
  (أ) عقد أساسي صغير وإلزامي لكل مزوّد (`checkAvailability`, `getPricing`, `register`, `renew`)، مع عمليات إضافية (مثل إدارة DNS/Glue records) كواجهات فرعية اختيارية يُنفّذها المزوّد فقط إن كان يدعمها.
  (ب) عقد شامل واحد يُلزم كل مزوّد بتنفيذ كل العمليات (بما فيها DNS) دفعة واحدة.
- **القرار المقترح:** الخيار (أ) — عقد أساسي إلزامي صغير يغطي فقط العمليات التي يحتاجها كل مزوّد فعلياً لدعم البحث والشراء، بينما تُعامَل DNS/Glue كواجهة فرعية اختيارية (مثال مفاهيمي: `SupportsNameserverManagement`).
- **سبب القرار:** يمنع إجبار أي مزوّد مستقبلي على تنفيذ عمليات لا يدعمها فعلياً — وهو الوضع القائم فعلياً اليوم (DNS مقتصر على Namecheap وEnom فقط وفق جرد المرحلة 0).
- **التأثير على المراحل اللاحقة:** يحدّد تصميم العقد بالكامل في المرحلة 1؛ قرار جوهري يصعب تعديله لاحقاً دون كسر توافقية مع أي مزوّد يُضاف مستقبلاً.
- **هل يحتاج ADR منفصلاً؟** نعم — ADR-DOM-001 (Provider Abstraction).
- **Blocking Level:** `Blocking for Phase 1` — هذا القرار يحدّد تصميم العقد بالكامل، ولا تبدأ المرحلة 1 قبل حسمه.
- **حالة القرار:** Pending

#### القرار 5 — سياسة اختيار المزوّد عند دعم أكثر من مزوّد لنفس الامتداد
- **الخيارات المتاحة:**
  (أ) أولوية ثابتة مُعرَّفة يدوياً في الكود (كما هو حالياً: Namecheap ثم Enom عبر `RegistrarProvisioningService::defaultProvider()`).
  (ب) اختيار ديناميكي حسب أقل سعر متاح وقت الطلب.
  (ج) الاعتماد على `domain_tlds.provider_id` الموجود بالفعل في قاعدة البيانات كمصدر وحيد للحقيقة (كل TLD مرتبط مسبقاً بمزوّد محدَّد عبر الكتالوج).
- **القرار المقترح:** الخيار (ج) — الاعتماد على `domain_tlds.provider_id` كمصدر القرار بدل منطق أولوية مبرمَج يصعب تتبّعه أو تعديله دون نشر كود جديد.
- **سبب القرار:** البنية الحالية لقاعدة البيانات تدعم هذا فعلياً؛ نقل القرار من الكود إلى البيانات (الكتالوج) يقلّل التزامن المطلوب بين تعديلات الإدارة وتعديلات الكود.
- **التأثير على المراحل اللاحقة:** يبسّط تصميم المرحلة 1 والمرحلة 4، ويتطلّب مراجعة/إعادة تصميم `defaultProvider()` الحالية.
- **هل يحتاج ADR منفصلاً؟** نعم — ADR-DOM-005 (Provider Selection Policy).
- **Blocking Level:** `Blocking for Phase 1` — يبسّط تصميم الطبقة الموحّدة في المرحلة 1، ولا تبدأ المرحلة 1 قبل حسمه.
- **حالة القرار:** Pending

#### القرار 6 — المصدر النهائي للسعر المعروض للعميل
- **الخيارات المتاحة:**
  (أ) قراءة `domain_tld_prices.sale` مباشرة (استعلام حي) في كل لحظة عرض.
  (ب) نسخة مسعّرة (Snapshot) تُحفظ وقت العرض/الإضافة للسلة داخل `order_items.meta` وتُستخدم حتى لحظة الدفع.
- **القرار المقترح:** الخيار (ب) — اعتماد Snapshot يُحفظ وقت إضافة الدومين للسلة/الطلب (البنية — `meta` json — موجودة بالفعل وفق جرد المرحلة 0)، مع مقارنته بالسعر الحي وقت الدفع الفعلي وإظهار تنبيه واضح للعميل إن تغيّر السعر، بدل تحديثه بصمت.
- **سبب القرار:** يعالج مباشرة الخطر المسجَّل R-06 في سجل المخاطر العام (اختلاف السعر بين البحث والدفع)، مع الحفاظ على الشفافية أمام العميل إن تغيّر السعر فعلياً في الكتالوج بين اللحظتين.
- **التأثير على المراحل اللاحقة:** يحدّد تصميم المرحلة 4 (الطلبات والدفع) بالكامل، ومرتبط مباشرة بالقرار رقم 7 التالي.
- **هل يحتاج ADR منفصلاً؟** نعم — ADR-DOM-004 (Domain Pricing and Price Locking).
- **Blocking Level:** `Blocking for Phase 4` — لا تبدأ المرحلة 4 (الطلبات والدفع) قبل حسم هذا القرار.
- **حالة القرار:** Pending

#### القرار 7 — لحظة تثبيت السعر (Price Locking)
- **الخيارات المتاحة:**
  (أ) وقت البحث. (ب) وقت إضافة السلة. (ج) وقت إنشاء الطلب. (د) وقت الدفع فقط.
- **القرار المقترح:** الخيار (ج) — تثبيت السعر وقت إنشاء الطلب فعلياً (لحظة إنشاء سجل `order_items` وحفظ السعر داخل `meta`)، مع إعادة التحقق من السعر الحي وقت الدفع وإشعار العميل عند وجود فرق، بدل تغييره بصمت (متوافق تماماً مع القرار رقم 6).
- **سبب القرار:** تثبيت السعر وقت البحث (أ) يُعرِّض النظام لأسعار قديمة إذا تأخّر العميل في إتمام الشراء؛ الاكتفاء بتثبيته وقت الدفع فقط (د) يعني عرض سعر غير نهائي طوال رحلة السلة، وهي تجربة مستخدم غير موثوقة.
- **التأثير على المراحل اللاحقة:** يربط مباشرة بتصميم آلية حساب السعر المقترحة في المرحلة 4 (`DomainQuoteService` أو مكافئها).
- **هل يحتاج ADR منفصلاً؟** يُدمَج ضمن نفس ADR-DOM-004 (Domain Pricing and Price Locking) الخاص بالقرار رقم 6.
- **Blocking Level:** `Blocking for Phase 4` — مرتبط مباشرة بالقرار رقم 6، ولا تبدأ المرحلة 4 قبل حسم كليهما معاً.
- **حالة القرار:** Pending

#### القرار 8 — السلوك الرسمي عند نجاح الدفع مع فشل التسجيل لدى المزوّد
- **الخيارات المتاحة:**
  (أ) إعادة محاولة تلقائية محدودة (Retry بعدد مرات وفاصل زمني معقولَين) ثم تنبيه إداري فوري إن استمر الفشل.
  (ب) تنبيه إداري فوري بدون أي إعادة محاولة تلقائية (تدخّل يدوي دائماً).
  (ج) استرجاع (Refund) تلقائي فوري عند أول فشل تسجيل.
- **القرار المقترح:** الخيار (أ) — إعادة محاولة تلقائية محدودة عبر آلية Queue/Job، ثم تنبيه إداري واضح وفوري إن استمر الفشل، مع إبقاء حالة الطلب "قيد المعالجة" (وليس "مكتمل") حتى ينجح التسجيل فعلياً — بدون اللجوء لاسترجاع تلقائي فوري (ج) لأن أغلب أسباب فشل التسجيل مؤقتة (رصيد، انقطاع اتصال) لا نهائية.
- **سبب القرار:** يوازن بين تجربة عميل معقولة (لا استرجاع مزعج لخلل مؤقت) وعدم ترك العميل بدون دومين رغم الدفع دون أي تدخّل من النظام.
- **التأثير على المراحل اللاحقة:** **الأعلى تأثيراً بين كل القرارات في هذه القائمة** — يحدّد تصميم المرحلة 5 (التسجيل الفعلي) بالكامل، بما في ذلك الحاجة الفعلية لآلية Retry عبر Jobs/Queues، وهي غير موجودة حالياً في النظام (لا توجد أي Jobs مرتبطة بالدومينات وفق جرد المرحلة 0) — ما قد يستدعي مراجعة قاعدة "إعادة استخدام البنية الحالية" إن ثبتت الحاجة الفعلية لبنية جديدة هنا تحديداً.
- **هل يحتاج ADR منفصلاً؟** نعم — ADR-DOM-006 (Registration Failure After Payment).
- **Blocking Level:** `Blocking for Phase 5` — لا تبدأ المرحلة 5 (التسجيل الفعلي) قبل حسم هذا القرار.
- **حالة القرار:** Pending

#### القرار 9 — مصدر بيانات WHOIS Contact الإلزامي
- **الخيارات المتاحة:**
  (أ) استخدام بيانات العميل المسجَّلة في `clients` مباشرة، مع رفض المتابعة إن كانت ناقصة (بدون أي قيم احتياطية وهمية).
  (ب) نموذج WHOIS Contact مستقل وصريح يُملأ/يُؤكَّد من العميل أثناء رحلة شراء الدومين تحديداً (يمكن تعبئته مبدئياً من بيانات `clients` لتسهيل الإدخال).
  (ج) الإبقاء على النظام الحالي (قيم احتياطية وهمية عند نقص البيانات) كحل مؤقت مقبول.
- **القرار المقترح:** الخيار (ب) — نموذج WHOIS Contact صريح ومستقل ضمن رحلة الشراء، مع رفض إتمام الطلب دون بيانات كاملة وصحيحة، واستبعاد الخيار (ج) الحالي صراحة كونه خطراً تشغيلياً موثَّقاً بالفعل (الخطر R-02 في سجل المخاطر العام).
- **سبب القرار:** بيانات `clients` الحالية قد لا تحتوي كل الحقول التي يتطلّبها WHOIS فعلياً (عنوان كامل، دولة، إلخ)، ولا تُلزم العميل بمراجعتها/تأكيدها تحديداً لغرض تسجيل دومين حقيقي باسمه.
- **التأثير على المراحل اللاحقة:** يضيف خطوة UI جديدة إلى مسار الشراء في المرحلة 4/5؛ قد يتطلّب حقولاً إضافية أو بنية تخزين جديدة لبيانات WHOIS (تُحسم التفاصيل التنفيذية وقت التنفيذ الفعلي، لا في هذه الوثيقة).
- **هل يحتاج ADR منفصلاً؟** نعم — ADR-DOM-007 (WHOIS Contact Data Source).
- **Blocking Level:** `Blocking for Phase 5` — لا تبدأ المرحلة 5 (التسجيل الفعلي) قبل حسم هذا القرار.
- **حالة القرار:** Pending

#### القرار 11 — مصير Route `domains.page` وتوحيد واجهات بحث الدومين المتعددة (أُضيف 2026-07-22، اعتُمد 2026-07-22)
- **الخيارات المتاحة (كسجل تاريخي لمسار القرار):**
  (أ) توجيه الرابط الحالي (`domains.page` / `/domains`) إلى صفحة فعلية جديدة تحتوي الـ Section الرسمي (`front/sections/templates/domain_search.blade.php`) بدل إعادة `view('domains.search')` المفقودة.
  (ب) تعديل `domains_showcase.blade.php` ليستخدم نفس منطق `domains.check` (AJAX) بدل نموذج الـ GET المباشر نحو `domains.page`، مع إبقاء الـ Route أو حذفه لاحقاً بعد التأكد من عدم استخدامه من أي مصدر آخر.
  (ج) إزالة/تعطيل الـ Route وكل الروابط الثابتة التي تستهدفه (الفوتر، القائمة الافتراضية) صراحة، وتوجيهها بدلاً من ذلك نحو الصفحة التي تحتوي الـ Section الرسمي.
- **القرار المعتمَد (Approved — 2026-07-22)**: سياسة مركَّبة توثيقية (بلا أي تنفيذ كود ضمن هذا الاعتماد)، تجمع عناصر من الخيارات الثلاثة أعلاه حسب الملف المعني تحديداً:
  1. واجهة البحث الرسمية **الوحيدة** تبقى `resources/views/front/sections/templates/domain_search.blade.php` كـ Section ضمن Page Builder — لا تُعتمد أي صفحة مستقلة بديلة دائمة.
  2. `resources/views/components/template/sections/search-domain.blade.php` يبقى **المرجع الوظيفي الحالي** لمنطق البحث (AJAX عبر `domains.check`)؛ يُعاد استخدام منطقه أو استخلاصه إلى مكوّن مشترك عند تنفيذ المرحلة 3.
  3. `resources/views/components/template/sections/domains_showcase.blade.php` **لا يبقى معتمداً على `route('domains.page')`** — يُربَط في المرحلة 3 بنفس Endpoint الموحّد `route('domains.check')`، أو يُدمَج مع/يُوقَف لصالح الـ Section الرسمي إن ثبت وقت التنفيذ أنه يكرر وظيفته بالكامل.
  4. روابط الفوتر (`front/layouts/footers/palgoals_marketing.blade.php`) والقائمة الافتراضية (`front/layouts/partials/footer/menu-links.blade.php`) التي تستخدم `domains.page` تُحوَّل في المرحلة 3 إلى صفحة فعلية تحتوي الـ Section الرسمي، أو تُزال إن لم توجد صفحة مخصَّصة معتمدة.
  5. Route `domains.page` يُصنَّف **`Deprecated Pending Removal`** — لا يُحذف فوراً ولا ضمن هذا الاعتماد؛ يُحذف فقط بعد تحقّق فعلي لاحق من: (أ) إزالة/تحويل كل المراجع إليه، (ب) بحث شامل يؤكِّد عدم وجود أي استدعاء متبقٍ، (ج) اختبار فعلي للروابط والتنقل بعد التحويل.
  6. **لا يُنشأ** `resources/views/domains/search.blade.php` كحل دائم أو كواجهة مستقلة جديدة — إنشاؤه يخالف القرار الأصلي بأن البحث جزء من Page Builder.
- **سبب الاعتماد:** الـ Route ثبت أنه `Active Broken Route / Functional Blocker` مؤكَّد (R-12، P-06)، والمشكلة الجوهرية ليست غياب صفحة مستقلة بل وجود **مسار دخول وظيفي ثانٍ ومتوازٍ** (`domains.page` عبر GET) بجانب المسار الموحَّد الصحيح (`domains.check` عبر AJAX). توحيد نقطة الدخول على `domains.check` فقط، مع تصنيف الـ Route القديم كمرشَّح للحذف التدريجي (وليس حذفاً فورياً)، يحافظ على استمرارية الروابط الحالية أثناء الانتقال ويمنع تكرار قرار Page Builder كموقع رسمي وحيد للبحث.
- **التأثير التنفيذي (Impact) — توثيقي فقط، بلا أي تنفيذ كود ضمن هذا التحديث:**
  - المرحلة 3 تصبح مسؤولة تنفيذياً عن: (١) تفعيل `domain_search.blade.php` وربطه بمنطق `search-domain.blade.php`/`domains.check`، (٢) إعادة توجيه `domains_showcase.blade.php` نحو `domains.check` أو دمجه، (٣) تحديث/إزالة روابط الفوتر والقائمة الافتراضية، (٤) تنفيذ خطة إزالة `domains.page` بعد التحقق الكامل المذكور في البند 5 أعلاه.
  - لا يُنشأ أي View أو صفحة مستقلة جديدة تحت `resources/views/domains/` كجزء من هذا القرار.
  - Blocking Level يبقى مسجَّلاً `Blocking for Phase 3` كمرجع تصنيفي، لكن اعتماد هذا القرار **يزيل** الحاجز أمام بدء المرحلة 3 من ناحية هذا البند تحديداً.
- **هل يحتاج ADR منفصلاً؟** نعم — **ADR-DOM-009 (Domain Search Interface Consolidation & Legacy Route Resolution)**، تم إنشاؤه فعلياً: `docs/ADR-DOM-009-DOMAIN-SEARCH-ENTRY-POINT.md` (Status: Accepted، بتاريخ 2026-07-22).
- **Blocking Level:** `Blocking for Phase 3`.
- **حالة القرار:** **Approved** (بتاريخ 2026-07-22)

#### القرار 10 — مصير الملفات القديمة/اليتيمة المكتشفة في المرحلة 0
- **الخيارات المتاحة:**
  (أ) حذف الملفات فور تأكيد عدم استخدامها (`dashboard/domains.blade.php`, `client/domain-name-search.blade.php`, `client/domain-table-client.blade.php`, نسخة `legacy/visual-builder/...`).
  (ب) الإبقاء عليها كما هي دون أي إجراء ضمن هذه الخطة.
  (ج) الإبقاء عليها مؤقتاً مع وسمها بوضوح (`@deprecated` حيث لم تكن موسومة أصلاً بعد) وتحديد موعد حذف صريح لاحقاً (بعد اعتماد المرحلة 8).
- **القرار المقترح:** الخيار (ج) — وسم واضح + جدولة حذف صريحة بعد اكتمال واعتماد الخطة كاملة، بدل حذف فوري (قد يُخفي استخداماً غير متوقَّع لم يظهر في الجرد) أو تجاهل كامل (تراكم دَين تقني بلا نهاية محددة).
- **سبب القرار:** يتوافق مع قاعدة "لا يُحذف كود يعمل دون مبرر موثَّق" (القاعدة رقم 4 في Development Rules)، مع تجنّب ترك كود ميت إلى ما لا نهاية بلا خطة تعامل معه.
- **التأثير على المراحل اللاحقة:** تأثير محدود — بند تنظيفي، لا يعطّل أي مرحلة إن تأخّر تنفيذه.
- **هل يحتاج ADR منفصلاً؟** نعم (خفيف الوزن) — ADR-DOM-008 (Dead Code and Legacy Views Cleanup).
- **Blocking Level:** `Non-blocking` — لا يعطّل بدء أي مرحلة وظيفية (1 حتى 7)، لكن يجب حسمه قبل إغلاق المرحلة 8 نهائياً.
- **حالة القرار:** Pending

### الملفات المتوقع تعديلها
لا يوجد — هذه مرحلة قرارات وتوثيق بحتة، بلا أي كود.

### التبعيات
المرحلة 0 (يجب اعتماد الجرد النهائي أولاً قبل حسم أي قرار مبني عليه).

### المخاطر
- اتخاذ قرار غير مدروس بشكل كافٍ في هذه المرحلة قد يُكلّف إعادة عمل كبيرة في مراحل لاحقة (خصوصاً القرارات 1، 2، 8 التي تمس بنية البيانات وسلوك ما بعد الدفع).
- الضغط لتسريع الاعتماد دون مراجعة كافية من Project Owner قد يُنتج قرارات لم تُختبر افتراضاتها فعلياً.

### معايير النجاح
- تم تصنيف كل قرار من القرارات الأحد عشر أعلاه حسب Blocking Level (`Blocking for Phase 1` / `Blocking for Phase 3` / `Blocking for Phase 4` / `Blocking for Phase 5` / `Non-blocking`) — منجَز فعلياً ضمن هذا القسم.
- كل قرار من قرارات `Blocking for Phase 1` (القرارات 2، 3، 4، 5) يحمل حالة صريحة (`Approved` أو `Rejected`) قبل بدء المرحلة 1.
- قرار `Blocking for Phase 3` (القرار 11) يحمل حالة صريحة قبل بدء المرحلة 3.
- كل قرار من قرارات `Blocking for Phase 4` (القرارات 1، 6، 7) يحمل حالة صريحة قبل بدء المرحلة 4.
- كل قرار من قرارات `Blocking for Phase 5` (القرارات 8، 9) يحمل حالة صريحة قبل بدء المرحلة 5.
- القرار `Non-blocking` (القرار 10) لا يُشترط حسمه لبدء أي مرحلة وظيفية، لكنه يجب أن يُحسم قبل إغلاق المرحلة 8 نهائياً.
- كل قرار `Approved` يتطلّب ADR منفصل إما بدأ إنشاؤه فعلياً تحت `docs/`، أو تأجيله بسبب موثَّق صراحة.
- فهرس القرارات المعمارية أعلاه محدَّث بما يعكس حالة كل قرار بدقة.

### كيفية اختبار المرحلة
لا يوجد كود يُختبر؛ "الاختبار" هنا هو مراجعة واعتماد صريح من Project Owner لكل قرار على حدة، موثَّق بتاريخ وتوقيع/موافقة صريحة (نصية على الأقل). يمكن اعتماد قرارات `Blocking for Phase 1` أولاً والبدء بالمرحلة 1 دون انتظار حسم بقية القرارات (`Blocking for Phase 4/5` أو `Non-blocking`).

### ملاحظات مهمة
- المرحلة 0.5 **ليست بوابة واحدة** توقف كل العمل اللاحق حتى تُحسم القرارات الأحد عشر معاً — البوابة الفعلية على مستوى كل قرار حسب Blocking Level الخاص به (راجع قسم "تصنيف القرارات حسب مستوى التعطيل" أعلاه). هذا يعالج مباشرة الخطر R-11 في سجل المخاطر العام.
- لا يبدأ أي عمل برمجي في المرحلة 1 قبل أن تتحوّل قرارات `Blocking for Phase 1` تحديداً (القرارات 2، 3، 4، 5) من `Pending` إلى حالة نهائية صريحة (`Approved` أو `Rejected`).
- لا يبدأ أي عمل برمجي في المرحلة 3 قبل حسم قرار `Blocking for Phase 3` (القرار 11 — مصير Route `domains.page` وتوحيد واجهات البحث الثلاث؛ أُضيف 2026-07-22 بعد اكتشاف R-12).
- لا يبدأ أي عمل برمجي في المرحلة 4 قبل حسم قرارات `Blocking for Phase 4` (القرارات 1، 6، 7)، ولا في المرحلة 5 قبل حسم قرارات `Blocking for Phase 5` (القرارات 8، 9).
- القرار 10 (`Non-blocking`) لا يعطّل بدء أي مرحلة وظيفية، لكن إغلاق المرحلة 8 (الإطلاق النهائي) يشترط حسمه هو أيضاً.
- القرارات المرفوضة (`Rejected`) تبقى موثَّقة في هذا القسم كسجل تاريخي (لا تُحذف)، مع توضيح البديل المعتمد بدلاً منها.

### Definition of Done
- تم تدوين خيار مقترح وسبب وتأثير و**Blocking Level** لكل القرارات الأحد عشر أعلاه (منجَز فعلياً ضمن هذا القسم).
- تم تصنيف جميع القرارات الأحد عشر دون استثناء حسب Blocking Level.
- تم حسم (`Approved` أو `Rejected`) جميع قرارات `Blocking for Phase 1` (القرارات 2، 3، 4، 5) قبل بدء أي عمل برمجي في المرحلة 1.
- تم حسم قرار `Blocking for Phase 3` (القرار 11) قبل بدء أي عمل برمجي في المرحلة 3.
- تم حسم جميع قرارات `Blocking for Phase 4` (القرارات 1، 6، 7) قبل بدء المرحلة 4، وجميع قرارات `Blocking for Phase 5` (القرارات 8، 9) قبل بدء المرحلة 5.
- لا يلزم حسم القرار 10 (`Non-blocking`) لبدء المرحلة 1 أو أي مرحلة وظيفية أخرى.
- يجب حسم جميع القرارات الأحد عشر (بما فيها القرارين 10 و11) قبل الإغلاق النهائي للمرحلة 8.
- كل قرار `Approved` يتطلّب ADR منفصل تم إما إنشاء ملفه الفعلي تحت `docs/`، أو تأجيله صراحة بسبب موثَّق داخل فهرس القرارات المعمارية.
- فهرس القرارات المعمارية أعلاه محدَّث بحالة كل ADR (`Planned` → `Drafted` → `Accepted`، حسب التقدّم الفعلي).
- لا يبدأ أي تنفيذ (كود، Migration، Route) متأثر بقرار معيّن قبل حسم ذلك القرار تحديداً — وليس بالضرورة انتظار حسم القرارات العشرة معاً.
- حالة المرحلة 0.5 نفسها (عمود "الحالة" في جدول "متابعة تقدم التنفيذ") يمكن أن تبقى `In Progress` جزئياً طالما توجد قرارات لمرحلة لاحقة لم تُحسم بعد (مثال: قرارات `Blocking for Phase 5` لا تزال `Pending` بينما بدأت المرحلة 1 فعلياً بعد حسم قرارات `Blocking for Phase 1`) — لا تتحول المرحلة 0.5 إلى `Completed` إلا بعد حسم القرارات الأحد عشر كاملة (بما فيها القرارين 10 و11).

---

## المرحلة 1 — توحيد طبقة مزوّدي الدومينات

### الهدف
إنشاء طبقة تجريد موحّدة (Provider Abstraction / Contract) بحيث يتعامل باقي النظام مع "مزوّد دومين" بواجهة موحّدة (`checkAvailability`, `register`, `renew`, `getPricing`, `manageNameservers`, ...) بدلاً من تفرّعات `if/elseif` المتناثرة. تشمل هذه المرحلة أيضاً إصلاح الخلل الموثّق في المرحلة 0 (فحص توفر Enom المفقود).

### الملفات المتوقع تعديلها (تقديري، يُحسم عند التنفيذ الفعلي)
- إنشاء عقد/واجهة جديدة (مثال: `app/Services/Domains/Contracts/RegistrarClientInterface.php`).
- `app/Services/Domains/Clients/EnomClient.php` — تنفيذ الواجهة + إضافة `checkAvailability()`.
- `app/Services/Domains/Clients/NamecheapClient.php` — تنفيذ الواجهة، نقل منطق فحص التوفر من `DomainSearchController` إليه.
- `app/Services/Domains/RegistrarProvisioningService.php`, `DomainDnsService.php`, `DomainRenewalService.php` — استبدال التفريع اليدوي باستدعاء عبر الواجهة الموحّدة.
- `app/Http/Controllers/Admin/Management/DomainSearchController.php`, `DomainTldController.php` — نفس المبدأ.
- احتمال إضافة Factory/Resolver بسيط (`RegistrarClientFactory`) يُعيد الـ Client المناسب بناءً على `DomainProvider::type`.

### التبعيات
يعتمد على نتائج واعتماد المرحلة 0 (خصوصاً القرار بشأن Cloudflare وبقاء/حذف الكود الميت في `Domain::checkAvailability()`).

### المخاطر
- كسر مسارات تعمل حالياً (تسجيل، تجديد، DNS) إذا لم تُحافظ إعادة الهيكلة على نفس السلوك الخارجي تماماً.
- تغيير التوقيعات (signatures) قد يؤثر على أي كود آخر يستدعي الـ Clients مباشرة دون المرور بالخدمات.

### معايير النجاح
- كل الاستدعاءات الحالية (تسجيل/تجديد/DNS/فحص توفر) تعمل بنفس النتائج عبر الواجهة الموحّدة.
- لا يوجد أي `if ($provider->type === 'namecheap')` متبقٍ خارج طبقة الـ Factory/Resolver نفسها.
- Enom قادر على فحص التوفر بدون خطأ فادح.

### كيفية اختبار المرحلة
- اختبار فحص التوفر لكلا المزوّدين (namecheap + enom) في وضع Sandbox/Test لعدة امتدادات.
- اختبار يدوي لكل مسار كان يعمل سابقاً (تجديد، DNS) للتأكد من عدم وجود Regression.
- إن وُجدت اختبارات آلية (`tests/`) لهذه الطبقة — تشغيلها والتأكد من نجاحها؛ إن لم توجد، تُضاف اختبارات أساسية هنا (وحدة أو Feature) تغطي الواجهة الجديدة.

### ملاحظات مهمة
- لا تُغيَّر بنية قاعدة البيانات في هذه المرحلة (`registrar` يبقى نصاً حراً كما هو، ما لم يُقرَّر خلاف ذلك صراحة في مرحلة لاحقة منفصلة وموثّقة).
- الأولوية القصوى في هذه المرحلة: **عدم كسر أي شيء يعمل حالياً** — إعادة الهيكلة تُبنى فوق السلوك الحالي، لا تُغيّره.

### Definition of Done
- تم اعتماد عقد المزوّدين (بناءً على القرار المعتمد فعلياً في المرحلة 0.5، القرار رقم 4).
- تم تنفيذ `checkAvailability()` لكلا المزوّدين (Namecheap وEnom) فعلياً وبدون خطأ.
- لم تعد توجد أي تفرّعات `if/elseif` على نوع المزوّد خارج طبقة الـ Factory/Resolver نفسها، إلا بمبرر موثَّق صراحة في هذا القسم.
- اختبارات Namecheap وEnom في وضع Sandbox/Test نجحت فعلياً لعدة امتدادات.
- لم يحدث أي Regression موثَّق في مسارات التسجيل أو التجديد أو DNS القائمة.
- تم تحديث هذه الوثيقة بنتائج التنفيذ الفعلية (وليس الخطة فقط).
- يتم تحديث حالة المرحلة (عمود "الحالة") إلى `Completed` بعد تحقّق كل المعايير أعلاه فعلياً.
- يتم تحديث عمود "الاعتماد" إلى `Approved` بعد موافقة Project Owner الصريحة، ولا تبدأ المرحلة 2 قبل ذلك.

---

## المرحلة 2 — إدارة الامتدادات والأسعار

### الهدف
التأكد من أن كتالوج الامتدادات (`domain_tlds`) وتسعيرها (`domain_tld_prices`) دقيق، محدَّث، وقابل للمزامنة بشكل موثوق من كلا المزوّدين عبر الطبقة الموحّدة الجديدة من المرحلة 1، مع معالجة فجوة "لا توجد مزامنة أسعار مجدولة" المذكورة في المرحلة 0.

### الملفات المتوقع تعديلها
- `app/Http/Controllers/Admin/Management/DomainTldController.php` (استخدام الطبقة الموحّدة بدل الاستدعاء المباشر للمزوّد).
- احتمال إضافة Artisan Command جديد لمزامنة الأسعار (مجدول)، مثال: `domains:sync-tld-pricing`.
- `routes/console.php` — تسجيل الجدولة الجديدة إن اعتُمدت.
- Views: `resources/views/dashboard/management/domain_tlds/*` — تعديلات طفيفة إن استدعى الأمر إظهار "آخر مزامنة" أو حالة فشل.

### التبعيات
المرحلة 1 (الطبقة الموحّدة).

### المخاطر
- مزامنة تلقائية غير مراقَبة قد تُدخل أسعاراً خاطئة من المزوّد إذا فشل الطلب جزئياً — يجب معالجة الفشل الجزئي بحذر (Transaction أو تحقق قبل الكتابة).
- تضارب بين `enabled`/`in_catalog` الحاليين ومنطق مزامنة جديد إن لم يُراعَ الفرق بينهما بدقة.

### معايير النجاح
- مزامنة يدوية تعمل كما هي (بدون Regression) عبر الطبقة الجديدة.
- إن أُضيفت مزامنة مجدولة: تعمل دون تدخل يدوي وتسجّل نتيجتها (نجاح/فشل) بشكل يمكن مراجعته.
- لا تغييرات غير مقصودة على أسعار البيع (`sale`) اليدوية التي أدخلها المدير — المزامنة تُحدّث السعر الأساسي (`cost`) فقط ما لم يُقرَّر خلاف ذلك صراحة.

### كيفية اختبار المرحلة
- تشغيل المزامنة اليدوية والتأكد من مطابقة الأسعار المعروضة لما يُرجعه المزوّد فعلياً (على الأقل عيّنة من الامتدادات).
- إن أُضيفت مهمة مجدولة: تشغيلها يدوياً (`--dry-run` إن وُجد) قبل تفعيل الجدولة الفعلية.

### ملاحظات مهمة
- يجب الحفاظ على الفصل بين `cost` (سعر التكلفة من المزوّد) و`sale` (سعر البيع للعميل) — أي أتمتة يجب ألا تكتب فوق `sale` دون قرار صريح.

### Definition of Done
- المزامنة اليدوية الحالية تعمل بدون Regression عبر الطبقة الموحّدة من المرحلة 1.
- إن اعتُمدت مزامنة مجدولة: الأمر المسؤول عنها (مثال: `domains:sync-tld-pricing`) يعمل فعلياً ويسجّل نتيجته (نجاح/فشل) بشكل قابل للمراجعة.
- تم التحقق ميدانياً (لا افتراضاً) من أن حقل `sale` اليدوي لا يُكتب فوقه تلقائياً دون قرار صريح موثَّق.
- لا اختلاف بين الأسعار المعروضة في الكتالوج والأسعار الفعلية لدى المزوّد لعيّنة اختبار مُحدَّدة من الامتدادات.
- تم تحديث هذه الوثيقة بنتائج التنفيذ الفعلية.
- يتم تحديث حالة المرحلة (عمود "الحالة") إلى `Completed` بعد تحقّق كل المعايير أعلاه فعلياً.
- يتم تحديث عمود "الاعتماد" إلى `Approved` بعد موافقة Project Owner الصريحة، ولا تبدأ المرحلة 3 قبل ذلك.

---

## المرحلة 3 — البحث عن الدومينات

### الهدف
توحيد ومَأسسة تجربة البحث عن الدومين للعميل، بما يشمل ربط بلوك `front/sections/templates/domain_search.blade.php` (حالياً بلا وظيفة) بنفس منطق البحث الفعّال المستخدَم في `components/template/sections/search-domain.blade.php`، عبر Endpoint موحّد واحد (`domains.check`) بدل ازدواجية محتملة.

### الملفات المتوقع تعديلها
- `resources/views/front/sections/templates/domain_search.blade.php` — إضافة الربط الفعلي (Alpine.js/fetch حسب نمط المشروع) نحو `route('domains.check')`.
- احتمال استخلاص منطق JS مشترك بين هذا البلوك ومكوّن `search-domain.blade.php` لتفادي التكرار (يُقرَّر وقت التنفيذ حسب حجم التشابه الفعلي).
- `app/Http/Controllers/Admin/Management/DomainSearchController.php` أو مكافئه بعد المرحلة 1 — لا تغيير وظيفي متوقَّع إن كانت المرحلة 1 قد وحّدت المسار بالفعل.

### التبعيات
المرحلة 1 (الطبقة الموحّدة يجب أن تكون جاهزة قبل ربط أي واجهة أمامية جديدة بها).

### المخاطر
- ربط بلوك Page Builder بمنطق بحث حي دون Rate Limiting قد يفتح باب إساءة استخدام (طلبات بحث مكثّفة تجاه المزوّد الخارجي وتُستهلك حصة الـ API).
- تعارض محتمل بين هذا البلوك وودجت `search-domain.blade.php` إذا ظهرا في نفس الصفحة.

### معايير النجاح
- بلوك `domain_search` يُرجع نتائج بحث حقيقية (توفر + سعر) عند إدخال اسم دومين.
- لا تكرار غير مبرَّر في منطق الجافاسكربت بين البلوكين إن أمكن الدمج، أو توثيق سبب إبقائهما منفصلَين إن لم يكن الدمج عملياً.
- حماية أساسية من الإساءة (Rate limiting / Debounce) مطبَّقة على طلبات البحث الجديدة.

### كيفية اختبار المرحلة
- اختبار البحث من الواجهة الأمامية لصفحة تستخدم هذا البلوك (يدوياً في المتصفح، حسب توجيه المشروع بشأن اختبار الواجهات قبل اعتبار المهمة منجَزة).
- اختبار حالات الحافة: اسم دومين غير صالح، امتداد غير مفعَّل في الكتالوج، فشل اتصال بالمزوّد (رسالة خطأ واضحة للمستخدم لا صفحة بيضاء).

### ملاحظات مهمة
- الالتزام بقاعدة الترجمة (`t()`) لكل نص جديد يُضاف لهذا البلوك، بلا استثناء.
- **توضيح معماري معتمَد من Project Owner**: التنفيذ الفعلي لهذه المرحلة يكون **حصراً** على `resources/views/front/sections/templates/domain_search.blade.php` باعتباره الـ Section الرسمي ضمن Page Builder — **وليس على صفحة مستقلة** (مثل أي View تحت `resources/views/domains/`). `components/template/sections/search-domain.blade.php` **يبقى المرجع الوظيفي** الذي يُستفاد من منطقه (استدعاء `route('domains.check')`، معالجة الاستجابة، الترجمة عبر `t()`) ويُربَط بالـ Section الجديد عند التنفيذ، مع تجنّب تكرار نفس المنطق بين الملفين قدر الإمكان (استخلاص مشترك أو إعادة استخدام مباشرة، يُحسَم التفصيل التنفيذي وقت التنفيذ الفعلي).

### Definition of Done
- بلوك `domain_search.blade.php` يُرجع نتائج بحث حقيقية (توفر + سعر) فعلياً عند إدخال اسم دومين، مربوطاً بـ `route('domains.check')`.
- تم تطبيق حماية أساسية من الإساءة (Rate limiting/Debounce) والتحقق من فعاليتها باختبار فعلي، لا نظري فقط.
- تم التأكد فعلياً من عدم تعارض هذا البلوك مع ودجت `search-domain.blade.php` عند تواجدهما في نفس الصفحة.
- كل نص جديد يمرّ عبر `t()` بدون استثناء واحد (تحقّق مباشر بمراجعة الكود المضاف، لا افتراض).
- تم تحديث هذه الوثيقة بنتائج التنفيذ الفعلية.
- يتم تحديث حالة المرحلة (عمود "الحالة") إلى `Completed` بعد تحقّق كل المعايير أعلاه فعلياً.
- يتم تحديث عمود "الاعتماد" إلى `Approved` بعد موافقة Project Owner الصريحة، ولا تبدأ المرحلة 4 قبل ذلك.

---

## المرحلة 4 — الطلبات والدفع

### الهدف
تثبيت مسار "طلب دومين → فاتورة → دفع" بشكل موثوق فوق نظام `orders`/`order_items`/`invoices` الحالي (بدون إنشاء جدول جديد ما لم تُثبت المرحلة 0 ضرورة ذلك)، بما يشمل حالتي "دومين مستقل" و"دومين مرتبط باشتراك استضافة".

### الملفات المتوقع تعديلها
- `app/Http/Controllers/Client/DomainController.php` (طرق `search/processSearch/buy/purchase` والمساعدات المرتبطة).
- `app/Http/Controllers/Front/CartController.php` أو مكافئه (`processDomains` المذكور في جدول Routes بالمرحلة 0).
- `resources/views/front/pages/checkout-domains.blade.php`, `checkout-domains-success.blade.php`.
- منطق حساب السعر النهائي (تكلفة + هامش) إن لم يكن مركزياً بالفعل بعد المرحلة 2 — يُفضَّل تجميعه في خدمة واحدة (مثال: `DomainQuoteService` إن لم توجد مكافئة).

### التبعيات
المرحلتان 2 (تسعير دقيق) و3 (بحث موحّد يُغذّي سلة الشراء بنفس البيانات المعروضة).

### المخاطر
- تضارب بين مسار "domain-only checkout" (`checkout-domains.blade.php`, يعتمد على `localStorage`) ومسار "template checkout" (الذي يستدعي `domains.check` أيضاً) — يجب التأكد من أن السعر المعروض في كليهما متطابق ومصدره نفس الخدمة الموحَّدة.
- أخطاء في حساب السعر (هامش الربح، العملة) قد تؤدي لخسارة مالية أو تسعير غير عادل للعميل.

### معايير النجاح
- طلب دومين يُنشئ سجلاً صحيحاً في `orders`/`order_items` بكل البيانات اللازمة (المزوّد، المدة بالسنوات، السعر) في `meta`.
- الفاتورة الناتجة تعكس نفس السعر المعروض للعميل وقت البحث، دون تغيّر غير مبرَّر بين لحظة العرض ولحظة الدفع (أو مع رسالة واضحة إن تغيّر السعر).
- مسارا "domain-only" و"ضمن قالب/استضافة" يعملان بدون تعارض عند استخدامهما في نفس الجلسة.

### كيفية اختبار المرحلة
- اختبار شراء دومين مستقل من البداية للنهاية في Sandbox (بحث → سلة → دفع → فاتورة).
- اختبار شراء دومين مرتبط باشتراك استضافة (`domain_option = new`) والتأكد من ربط `subscriptions.domain_id` الصحيح بعد الدفع.
- مراجعة الفاتورة الناتجة يدوياً للتأكد من صحة البنود والمبالغ.

### ملاحظات مهمة
- عدم لمس بنية `orders`/`order_items` الحالية (كما هو مذكور في "خارج النطاق") إلا إذا اتُّضح أثناء التنفيذ أنها غير كافية — عندها يُوثَّق قرار معماري صريح (ADR) قبل أي تعديل بنيوي.

### Definition of Done
- سياسة تثبيت السعر المعتمدة في المرحلة 0.5 (ADR-DOM-004، القراران 6 و7) مُطبَّقة فعلياً في مسار الطلب، لا في التوثيق فقط.
- اختبار شراء دومين مستقل واختبار شراء دومين مرتبط باشتراك استضافة تم تنفيذهما بنجاح فعلياً في بيئة Sandbox.
- الفاتورة الناتجة مطابقة تماماً للسعر المثبَّت وقت الطلب، مع تنبيه واضح للعميل عند أي تغيّر سعر بدل تغييره بصمت (تم التحقق باختبار فعلي لسيناريو تغيّر السعر).
- تم اختبار مساري "domain-only checkout" وcheckout القالب/الاستضافة معاً في نفس الجلسة والتأكد من عدم التعارض.
- تم تحديث هذه الوثيقة بنتائج التنفيذ الفعلية.
- يتم تحديث حالة المرحلة (عمود "الحالة") إلى `Completed` بعد تحقّق كل المعايير أعلاه فعلياً.
- يتم تحديث عمود "الاعتماد" إلى `Approved` بعد موافقة Project Owner الصريحة، ولا تبدأ المرحلة 5 قبل ذلك.

---

## المرحلة 5 — التسجيل الفعلي (Registration)

### الهدف
تثبيت مسار تسجيل الدومين الفعلي لدى المزوّد (Namecheap/Enom) بعد الدفع، مع معالجة جذرية لمشكلة "بيانات جهة الاتصال الاحتياطية الوهمية" (Placeholder contact data) الموثّقة في المرحلة 0، لأنها خطر تشغيلي حقيقي (رفض تسجيل، أو تعليق الدومين من قبل ICANN/المزوّد لاحقاً بسبب بيانات غير صحيحة).

### الملفات المتوقع تعديلها
- `app/Services/Domains/RegistrarProvisioningService.php` — إعادة النظر في `buildRegistrarContactPayload`/`expandContactForNamecheap`/`expandContactForEnom`: منع المتابعة بدون بيانات عميل حقيقية كافية (بدل استخدام قيم وهمية صامتة)، أو جمع البيانات الناقصة من العميل صراحة قبل التسجيل.
- احتمال إضافة خطوة UI في مسار الشراء لجمع/تأكيد بيانات جهة الاتصال (WHOIS Contact) قبل إتمام الدفع.
- معالجة الأخطاء عند فشل التسجيل الفعلي بعد الدفع (سيناريو: الدفع نجح لكن التسجيل فشل لدى المزوّد) — يجب تعريف سلوك واضح (إعادة محاولة؟ تنبيه إداري؟ استرجاع؟).

### التبعيات
المرحلة 4 (مسار الدفع مكتمل ومستقر) والمرحلة 1 (طبقة المزوّد الموحّدة).

### المخاطر
- **الأعلى في كامل الخطة**: هذه المرحلة تلمس عمليات حقيقية لا رجعة فيها (تسجيل دومين فعلي، رسوم فعلية من رصيد الحساب لدى المزوّد). أي اختبار يجب أن يتم حصراً على وضع Sandbox/Test.
- فشل صامت بعد الدفع (العميل يدفع، الدومين لا يُسجَّل، لا أحد يُخطَر) هو أخطر سيناريو ممكن ويجب تغطيته صراحة باختبار.

### معايير النجاح
- تسجيل دومين تجريبي ناجح فعلياً على بيئة Sandbox لكلا المزوّدين، يتم التحقق من نجاحه بالطريقة الرسمية التي توفرها بيئة Sandbox الخاصة بكل مزوّد — سواء بظهور الدومين في لوحة تحكم المزوّد، أو بإرجاع معرّف عملية/استجابة نجاح موثوقة عبر API المزوّد يمكن التحقق منها مباشرة (بحسب ما تسمح به قدرات Sandbox لكل مزوّد، فهي تختلف بين Enom وNamecheap). **نجاح سجل قاعدة بياناتنا الداخلية وحده لا يُعتبر إثباتاً كافياً لنجاح التسجيل لدى المزوّد.**
- سيناريو "دفع ناجح + تسجيل فاشل" يُعالَج بوضوح (تنبيه إداري على الأقل، أو آلية إعادة محاولة موثّقة).
- لا مسار يسمح بإتمام تسجيل فعلي ببيانات جهة اتصال وهمية بشكل صامت.

### كيفية اختبار المرحلة
- **إلزامي**: تنفيذ كل الاختبارات على `mode = test/sandbox` فقط في `domain_providers`. أي اختبار على وضع `live` يتطلب موافقة صريحة مسبقة من مالك المشروع نظراً لتكلفته المالية الحقيقية.
- اختبار المسار الكامل: بحث → دفع (وضع اختبار) → تسجيل فعلي في Sandbox → التحقق من نجاح التسجيل بالطريقة الرسمية التي توفرها بيئة Sandbox للمزوّد (ظهور الدومين وحالته في لوحة المزوّد، أو معرّف عملية/استجابة نجاح موثوقة عبر API المزوّد إن لم توفر بيئة الـ Sandbox لوحة مرئية).
- اختبار متعمَّد لسيناريو فشل التسجيل (مثال: بيانات ناقصة) والتأكد أن النظام لا "يبتلع" الخطأ بصمت.

### ملاحظات مهمة
- هذه المرحلة تحديداً تستحق مراجعة أمنية/تشغيلية إضافية (راجع المرحلة 7) قبل اعتمادها على بيئة الإنتاج الحيّة.

### Definition of Done
- القرار المعتمد فعلياً في المرحلة 0.5 بخصوص ADR-DOM-006 (فشل التسجيل بعد الدفع) وADR-DOM-007 (مصدر بيانات WHOIS) مُطبَّقان فعلياً في الكود، لا في التوثيق فقط.
- تسجيل دومين تجريبي ناجح فعلياً لكل من Namecheap وEnom (Sandbox)، مع التحقق من النجاح بالطريقة الرسمية التي توفرها بيئة Sandbox لكل مزوّد على حدة (ظهور في لوحة التحكم أو معرّف عملية/استجابة نجاح موثوقة عبر API) — نجاح سجل قاعدة بياناتنا الداخلية وحده لا يُعتبر إثباتاً كافياً.
- سيناريو "دفع ناجح + تسجيل فاشل" تم اختباره فعلياً بشكل متعمَّد، والسلوك الناتج مطابق تماماً للقرار المعتمد (Retry ثم تنبيه إداري).
- تم التحقق فعلياً (بمحاولة تعمّدية ببيانات ناقصة) من أنه لا يوجد مسار يسمح بإتمام تسجيل ببيانات وهمية بشكل صامت.
- تم تحديث هذه الوثيقة بنتائج التنفيذ الفعلية.
- يتم تحديث حالة المرحلة (عمود "الحالة") إلى `Completed` بعد تحقّق كل المعايير أعلاه فعلياً.
- يتم تحديث عمود "الاعتماد" إلى `Approved` بعد موافقة Project Owner الصريحة، ولا تبدأ المرحلة 6 قبل ذلك.

---

## المرحلة 6 — إدارة الدومينات (بعد الشراء)

### الهدف
تحسين واستقرار إدارة الدومينات بعد التسجيل: Nameservers، DNS، التجديد اليدوي والتلقائي، وإغلاق الفجوات الموثّقة في المرحلة 0 (لا إشعارات انتهاء صلاحية، الفاتورة الناقصة في التجديد اليدوي من لوحة الإدارة).

### الملفات المتوقع تعديلها
- `app/Http/Controllers/Admin/Management/DomainController.php` — إكمال `@todo: إنشاء فاتورة/عملية دفع للتجديد` (السطر 255 حسب جرد المرحلة 0).
- `app/Services/Domains/DomainRenewalService.php` — إضافة/تحسين منطق الإشعار قبل انتهاء الصلاحية.
- احتمال إضافة Notification/Mailable جديد (مثال: `DomainExpiringSoonNotification`) + جدولة فحص يومي للدومينات القريبة من الانتهاء بدون Auto-Renew.
- `app/Services/Domains/DomainDnsService.php` — لا تغيير بنيوي متوقَّع، فقط استخدام الطبقة الموحّدة من المرحلة 1 إن لم يكن قد تم بالفعل.

### التبعيات
المرحلة 1 (طبقة موحّدة) والمرحلة 5 (تسجيل فعلي يعمل، بما أن التجديد يفترض دومينات مسجَّلة فعلياً).

### المخاطر
- إشعارات انتهاء صلاحية خاطئة (متكررة أو مفقودة) تُفقد ثقة العميل.
- فاتورة تجديد يدوي جديدة يجب ألا تتعارض مع منطق الفوترة العام الحالي (تكرار فواتير لنفس الدومين).

### معايير النجاح
- التجديد اليدوي من لوحة الإدارة يُنشئ فاتورة صحيحة (إغلاق الـ `@todo`).
- دومين قريب من الانتهاء بدون Auto-Renew يُرسل إشعاراً واحداً واضحاً على الأقل قبل الانتهاء بفترة معقولة (تُحدَّد رقمياً وقت التنفيذ، مثال: 30/15/7 أيام — بالتوافق مع منطق `lead days` الموجود أصلاً في `DomainRenewalService`).
- لا ازدواجية في الفواتير لنفس عملية التجديد.

### كيفية اختبار المرحلة
- اختبار تجديد يدوي من لوحة الإدارة والتأكد من إنشاء فاتورة واحدة صحيحة.
- اختبار الإشعار بضبط تاريخ `renewal_date` صناعياً لدومين تجريبي والتأكد من إطلاق الإشعار في التوقيت الصحيح.
- تشغيل أمر `domains:process-auto-renewals --dry-run` والتأكد من عدم وجود Regression بعد تعديلات هذه المرحلة.

### ملاحظات مهمة
- إعادة استخدام منطق "lead days" الموجود بالفعل في `DomainRenewalService` بدل بناء منطق زمني موازٍ.

### Definition of Done
- الـ `@todo` الخاص بفاتورة التجديد اليدوي (`DomainController.php`) مُغلَق فعلياً — تم إنشاء فاتورة صحيحة عند اختباره يدوياً.
- إشعار انتهاء الصلاحية يعمل فعلياً، وتم اختباره بتاريخ `renewal_date` صناعي، بدون تكرار أو فقدان للإشعار.
- تم التحقق فعلياً من عدم وجود ازدواجية فواتير لنفس عملية التجديد (تجديد واحد = فاتورة واحدة).
- أمر `domains:process-auto-renewals --dry-run` يعمل بدون Regression بعد كل تعديلات هذه المرحلة.
- تم تحديث هذه الوثيقة بنتائج التنفيذ الفعلية.
- يتم تحديث حالة المرحلة (عمود "الحالة") إلى `Completed` بعد تحقّق كل المعايير أعلاه فعلياً.
- يتم تحديث عمود "الاعتماد" إلى `Approved` بعد موافقة Project Owner الصريحة، ولا تبدأ المرحلة 7 قبل ذلك.

---

## المرحلة 7 — الأمان والسجلات (Security & Audit Logging)

### الهدف
تدقيق شامل لأمان بيانات اعتماد المزوّدين، وتوثيق كل عملية حساسة (تسجيل، تجديد، تغيير Nameservers، تغيير بيانات اعتماد مزوّد) في سجل قابل للمراجعة (Audit Log)، تحضيراً لمرحلة الإطلاق.

### الملفات المتوقع تعديلها
- مراجعة (وليس بالضرورة تعديل) طريقة تخزين/فك تشفير بيانات `domain_providers` (`encrypted` casts) للتأكد من عدم تسرّبها في اللوجات أو الاستجابات (JSON) بالخطأ.
- احتمال إضافة جدول/آلية Audit Log مخصصة لعمليات الدومين إن لم يوجد نظام تدقيق عام قابل لإعادة الاستخدام في المشروع (يُتحقَّق من ذلك أولاً في هذه المرحلة قبل اقتراح جدول جديد).
- `app/Http/Controllers/Admin/Management/DomainProviderController.php::testConnection` — مراجعة أنها لا تُسجّل أو تُعرض بيانات اعتماد كاملة في الاستجابة/اللوج عند الفشل.

### التبعيات
يُفضَّل تنفيذها بعد استقرار المراحل 1 و5 (بما أنها تراجع كل عمليات المزوّد الحساسة الفعلية)، لكنها لا تعتمد بنيوياً عليها بشكل صارم — يمكن تشغيلها بالتوازي كمراجعة مستقلة إذا لزم الأمر.

### المخاطر
- تسريب بيانات اعتماد (API keys/passwords) في ملفات اللوج هو أخطر مخاطر هذه المرحلة تحديداً — يجب فحص كل نقطة `Log::` أو استجابة JSON مرتبطة بمزوّد الدومين.
- إضافة سجل تدقيق ثقيل قد يؤثر أداءً إذا نُفّذ بشكل متزامن (synchronous) على كل عملية.

### معايير النجاح
- تأكيد (بالفحص المباشر، ليس افتراضاً) أن لا بيانات اعتماد خام تظهر في أي لوج أو استجابة API.
- كل عملية حساسة (تسجيل/تجديد/تغيير NS/تعديل بيانات مزوّد) تترك أثراً قابلاً للمراجعة: من نفّذ العملية، متى، على أي دومين، والنتيجة.
- تقرير تدقيق أمني مكتوب (Markdown، ضمن `docs/`) يوثّق النتائج — بنفس نمط `docs/24-security-notes.md` الموجود في المشروع.

### كيفية اختبار المرحلة
- مراجعة يدوية لملفات اللوج بعد تنفيذ عمليات دومين تجريبية (تسجيل، تجديد، فحص اتصال مزوّد) للتأكد من خلوّها من بيانات حساسة.
- محاولة استدعاء نقاط API الحساسة ببيانات غير مصرَّح بها (اختبار صلاحيات) للتأكد من رفضها بشكل صحيح.

### ملاحظات مهمة
- الاستفادة من `docs/24-security-notes.md` الموجود مسبقاً كمرجع للمعايير الأمنية المعتمدة في المشروع بدل ابتكار معايير جديدة.

### Definition of Done
- تم فحص مباشر (وليس افتراضاً) لكل نقاط اللوج (`Log::`) والاستجابات (JSON) المرتبطة بمزوّدي الدومين، ولا توجد بيانات اعتماد خام ظاهرة في أي منها.
- كل عملية حساسة (تسجيل/تجديد/تغيير Nameservers/تعديل بيانات مزوّد) تترك أثراً قابلاً للمراجعة فعلياً — تم التحقق بتنفيذ عملية تجريبية ومراجعة الأثر الناتج عنها.
- تقرير تدقيق أمني مكتوب فعلياً ومنشور تحت `docs/`، بنفس نمط `docs/24-security-notes.md`.
- تم تحديث هذه الوثيقة بنتائج التنفيذ الفعلية.
- يتم تحديث حالة المرحلة (عمود "الحالة") إلى `Completed` بعد تحقّق كل المعايير أعلاه فعلياً.
- يتم تحديث عمود "الاعتماد" إلى `Approved` بعد موافقة Project Owner الصريحة، ولا تبدأ المرحلة 8 قبل ذلك.

---

## المرحلة 8 — الاختبارات والإطلاق

### الهدف
تجميع اختبارات شاملة (End-to-End) لكامل رحلة الدومين، ومراجعة نهائية قبل تفعيل النظام على بيانات اعتماد `live` حقيقية، مع خطة رجوع (Rollback) واضحة.

### الملفات المتوقع تعديلها
- إضافة/تجميع اختبارات Feature تحت `tests/Feature/Domains/` (أو المسار المعتمد في المشروع) تغطي: البحث، الطلب، الدفع، التسجيل (Sandbox)، التجديد، DNS.
- توثيق نهائي: تحديث هذه الوثيقة نفسها بحالة "منجَز" لكل مرحلة، وربما إنشاء `docs/DOMAIN-SYSTEM-LAUNCH-REPORT.md` منفصل بنفس نمط تقارير الإطلاق الأخرى في المشروع (`ADR_005_CLOSEOUT_REPORT.md` كمثال للنمط).

### التبعيات
كل المراحل من 0 إلى 7 معتمدة ومكتملة.

### المخاطر
- الانتقال إلى بيانات اعتماد `live` دون اختبار Sandbox كافٍ يعني مخاطرة مالية حقيقية (رسوم تسجيل فعلية، احتمال شراء دومين بالخطأ).
- عدم وجود خطة Rollback واضحة إذا ظهرت مشكلة بعد التفعيل الفعلي.

### معايير النجاح
- كل الاختبارات (يدوية وآلية) في المراحل السابقة تمر بنجاح على بيئة Staging/Sandbox.
- خطة تفعيل تدريجي موثّقة: مثال — تفعيل مزوّد واحد أولاً بحد أقصى للمبلغ اليومي، مراقبة لمدة محددة، ثم توسيع.
- خطة Rollback موثّقة (كيفية تعطيل نظام التسجيل الفعلي فوراً — مثال: `is_active=false` على `domain_providers` — دون كسر البحث/العرض).

### كيفية اختبار المرحلة
- تشغيل كامل رحلة العميل من البداية للنهاية على Staging بمزوّد Sandbox، مرة واحدة على الأقل لكل مزوّد (Namecheap وEnom)، مع التحقق من نجاح التسجيل بالطريقة الرسمية التي توفرها بيئة Sandbox لكل مزوّد (وفق نفس معيار المرحلة 5) — لا يُكتفى بنجاح السجل الداخلي في قاعدة بياناتنا وحده.
- اختبار Rollback فعلياً (تعطيل مزوّد أثناء التشغيل والتأكد من تدهور رشيق — Graceful Degradation — وليس عطلاً كاملاً للموقع).

### ملاحظات مهمة
- لا يُفعَّل أي مزوّد بوضع `live` إلا بموافقة صريحة من مالك المشروع بعد مراجعة تقرير هذه المرحلة.

### Definition of Done
- كل الاختبارات (يدوية وآلية) من المراحل 0 حتى 7 نُفِّذت ونجحت فعلياً على Staging/Sandbox — وليس افتراضاً بالوراثة من نجاح مراحل سابقة.
- خطة التفعيل التدريجي وخطة الـ Rollback موثَّقتان **ومختبَرتان فعلياً** (تم تعطيل مزوّد أثناء التشغيل فعلياً والتحقق من تدهور رشيق — Graceful Degradation).
- تم الحصول على موافقة صريحة وموثَّقة من Project Owner للانتقال إلى بيانات اعتماد `live`.
- هذه الوثيقة مُحدَّثة بالكامل بحالة `Completed` (عمود "الحالة") و`Approved` (عمود "الاعتماد") لكل مرحلة سابقة (0 حتى 7) في جدول "متابعة تقدم التنفيذ" أدناه، قبل اعتبار الإطلاق منجزاً.
- يتم تحديث حالة المرحلة 8 نفسها (عمود "الحالة") إلى `Completed` في جدول "متابعة تقدم التنفيذ" بعد تحقّق كل المعايير أعلاه فعلياً.
- يتم تحديث عمود "الاعتماد" الخاص بالمرحلة 8 إلى `Approved` بعد موافقة Project Owner الصريحة على الإطلاق الكامل — وهذه هي البوابة النهائية لكامل الخطة.

---

# متابعة تقدم التنفيذ

> يُحدَّث هذا الجدول عند بداية/نهاية كل مرحلة فعلياً. عمود "الحالة" وعمود "الاعتماد" منفصلان تماماً ولهما مجموعتا قيم مختلفتان — لا يجوز خلطهما:
>
> - **حالات التنفيذ (عمود "الحالة" فقط):** `Not Started`, `In Progress`, `Blocked`, `Under Review`, `Completed`.
> - **حالات الاعتماد (عمود "الاعتماد" فقط):** `Pending`, `Approved`, `Rejected`.
>
> `Approved` **لا تُستخدم أبداً كحالة تنفيذ** داخل عمود "الحالة" — هي قيمة خاصة بعمود "الاعتماد" فقط، وتُسجَّل فيه بعد موافقة Project Owner الصريحة، وبعد أن تكون المرحلة قد وصلت فعلياً لحالة `Completed` في عمود "الحالة".

| المرحلة | الحالة | تاريخ البدء | تاريخ الإغلاق | الاعتماد | ملاحظات |
|---|---|---|---|---|---|
| المرحلة 0 — Audit & Discovery | Blocked | 2026-07-21 | — | Pending | تحقق كودي شامل (قراءة مباشرة + بحث شامل) منجَز 100% ومُدرَج في الوثيقة بدليل لكل بند. **الحاجز الوحيد المتبقي**: لا يمكن تأكيد نشاط Enom في الإنتاج ولا نضج التسجيل الفعلي لغياب اتصال بقاعدة بيانات حية (Production/Staging) وغياب PHP من بيئة التنفيذ — هذا قيد بيئي وليس نقصاً في الجهد. راجع "نتائج التحقق الميداني" وجدول "ترتيب المشاكل حسب الأولوية" (P-07) داخل قسم المرحلة 0. |
| المرحلة 0.5 — اعتماد القرارات المعمارية | Not Started | — | — | Pending | تنتظر اكتمال المرحلة 0 (`Completed` + `Approved`) أولاً. القرارات العشرة مصنَّفة حسب Blocking Level (راجع تفصيل التصنيف داخل قسم المرحلة 0.5) — لا يلزم حسمها جميعاً دفعة واحدة؛ كل قرار يُحسم قبل بدء المرحلة التي يعطّلها تحديداً. |
| المرحلة 1 — توحيد طبقة مزوّدي الدومينات | Not Started | — | — | Pending | تنتظر حسم قرارات `Blocking for Phase 1` فقط (القرارات 2، 3، 4، 5) من المرحلة 0.5. |
| المرحلة 2 — إدارة الامتدادات والأسعار | Not Started | — | — | Pending | — |
| المرحلة 3 — البحث عن الدومينات | Not Started | — | — | Pending | — |
| المرحلة 4 — الطلبات والدفع | Not Started | — | — | Pending | تنتظر حسم قرارات `Blocking for Phase 4` فقط (القرارات 1، 6، 7) من المرحلة 0.5. |
| المرحلة 5 — التسجيل الفعلي (Registration) | Not Started | — | — | Pending | تنتظر حسم قرارات `Blocking for Phase 5` فقط (القرارات 8، 9) من المرحلة 0.5. |
| المرحلة 6 — إدارة الدومينات (بعد الشراء) | Not Started | — | — | Pending | — |
| المرحلة 7 — الأمان والسجلات | Not Started | — | — | Pending | — |
| المرحلة 8 — الاختبارات والإطلاق | Not Started | — | — | Pending | إغلاقها النهائي يشترط حسم القرار 10 (`Non-blocking`) أيضاً، رغم أنه لا يعطّل بدء أي مرحلة سابقة. |

---

# Development Rules

هذه القواعد ملزمة لكل من يعمل على أي مرحلة من هذه الخطة:

1. **لا يُعمَل على أكثر من مرحلة واحدة في نفس الوقت.** كل مرحلة تُنجَز وتُختبر وتُعتمد قبل الانتقال للتالية. **استثناء موثَّق:** المرحلة 0.5 قد تبقى `In Progress` جزئياً (قرارات لمرحلة لاحقة لا تزال `Pending`) بينما تبدأ مرحلة وظيفية معيّنة (1 أو 4 أو 5) بعد حسم قرارات Blocking Level الخاصة بها فقط — راجع تفصيل هذه القاعدة داخل قسم المرحلة 0.5. هذا الاستثناء مقصور على العلاقة بين المرحلة 0.5 وباقي المراحل تحديداً، ولا يُوسَّع لأي مرحلتين وظيفيتين أخريين.
2. **لا يبدأ تنفيذ مرحلة قبل اعتماد (Approval) المرحلة السابقة لها صراحة** من مالك المشروع أو المسؤول المخوَّل، مع مراعاة الاستثناء الموثَّق في القاعدة رقم 1 أعلاه بخصوص المرحلة 0.5 تحديداً.
3. **كل مرحلة يجب أن تنتهي باختبارات ناجحة** حسب معايير "كيفية الاختبار" المذكورة لها تحديداً — لا تُعتبر المرحلة منجزة بالكود فقط.
4. **لا يُحذف كود يعمل دون مبرر موثَّق.** أي كود "ميت" أو "يتيم" يُكتشف يُسجَّل في المرحلة 0 (أو يُحدَّث فيها) والقرار بشأنه يُتّخذ صراحة، لا ضمنياً أثناء مرحلة أخرى.
5. **إعادة استخدام البنية الحالية متى أمكن** — لا جداول جديدة (`domain_orders` مثلاً)، ولا أنظمة تجريد موازية، ولا خدمات مكررة، إلا إذا أثبتت المرحلة ذات الصلة أن البنية الحالية غير كافية، مع توثيق السبب.
6. **توثيق أي قرار معماري جديد** كملف ADR منفصل تحت `docs/` (بنفس نمط `ADR_003_*`, `ADR_005_*` الموجود في المشروع)، ثم الإشارة إليه من هذه الخطة.
7. **أي اختبار يتضمن عمليات فعلية لدى مزوّد خارجي (Namecheap/Enom) يجب أن يتم على وضع `test`/`sandbox` حصراً**، إلا في المرحلة 8 النهائية وبموافقة صريحة موثّقة للانتقال إلى `live`.
8. **الالتزام الكامل بقواعد `CLAUDE.md`** في كل تنفيذ: الترجمة عبر `t()` بلا نصوص hardcoded، Field Scope (Translatable/Shared) عند أي حقل جديد، أنماط UX المعتمدة (بحث حقيقي، empty states، flash messages بمفتاح `ok`/`error`)، و`dir="ltr" font-mono` لحقول IP/hostname/token/domain.
9. **لا Feature إضافية خارج نطاق المرحلة الحالية.** أي فكرة جديدة تظهر أثناء التنفيذ (مثل WHOIS Privacy) تُسجَّل في قسم "خارج النطاق" أعلاه لمرحلة لاحقة، ولا تُنفَّذ فوراً.
10. **كل مرحلة تنتج، عند اكتمالها، ملاحظة تحديث في هذه الوثيقة نفسها** (قسم يوضح ما تم فعلياً مقابل المخطَّط)، حفاظاً على كون هذه الوثيقة مرجعاً حياً (Living Document) لا خطة جامدة.

---

# سجل تحديثات الوثيقة

| الإصدار | التاريخ | التغيير | المنفذ |
|---|---|---|---|
| 1.0 | 2026-07-21 | إنشاء الوثيقة الأولية: مقدمة (الهدف/النطاق/خارج النطاق)، جرد المرحلة 0 الكامل، المراحل 1–8 بتفاصيلها، وقواعد Development Rules. | Claude (بناءً على طلب Project Owner) |
| 1.1 | 2026-07-21 | إضافة المرحلة 0.5 (اعتماد القرارات المعمارية — 10 قرارات)، إضافة سجل المخاطر العام (10 مخاطر)، إضافة فهرس القرارات المعمارية (8 ADRs مقترحة)، إضافة قسم Definition of Done قابل للتحقق لكل مرحلة من 0 حتى 8، إضافة جدول متابعة تقدم التنفيذ، تحسين بيانات حالة الوثيقة (Version/Owner/Current Phase/Status/Approval)، إضافة هذا السجل. لم يُعدَّل أي كود أو Route أو Migration ضمن هذا التحديث. | Claude (بناءً على طلب Project Owner) |
| 1.2 | 2026-07-21 | تأكيد صحة عدد أقسام Definition of Done (10 أقسام: المراحل 0، 0.5، 1–8 — كانت صحيحة بالفعل داخل الوثيقة، والتصحيح خاص بتقرير نصي سابق خارج الملف). فصل حالة التنفيذ (عمود "الحالة": `Not Started`/`In Progress`/`Blocked`/`Under Review`/`Completed`) عن حالة الاعتماد (عمود "الاعتماد": `Pending`/`Approved`/`Rejected`) في جدول "متابعة تقدم التنفيذ" وفي كل أقسام Definition of Done من المرحلة 0 حتى 8، مع تحديث القاعدتين 1 و2 في Development Rules ليعكسا الفصل. توضيح نطاق Domain Transfer في هدف النظام والنطاق وخارج النطاق (تخزين أسعار النقل داخل الكتالوج مسموح، تفعيل خدمة النقل نفسها للعميل خارج النطاق). تحسين معيار اختبار التسجيل في بيئة Sandbox بالمرحلتين 5 و8 بحيث يراعي اختلاف قدرات Sandbox بين Enom وNamecheap دون تخفيف شرط الاختبار الفعلي. إضافة حقل Blocking Level لكل قرار من قرارات المرحلة 0.5 العشرة (تصنيف: `Blocking for Phase 1`/`Blocking for Phase 4`/`Blocking for Phase 5`/`Non-blocking`) وإعادة صياغة بوابات بدء المراحل 1 و4 و5 بحيث لا تعطّلها كل القرارات العشرة دفعة واحدة. إضافة الخطر R-11 (بوابة قرارات موحّدة تؤخر إصلاحات حرجة) إلى سجل المخاطر العام، بحالة `Mitigated`. لم يُعدَّل أي كود أو Route أو Migration ضمن هذا التحديث، ولم يُعتمَد أي قرار معماري تلقائياً (كل القرارات العشرة لا تزال `Pending`). | Claude (بناءً على طلب Project Owner) |
| 1.3 | 2026-07-21 | **تنفيذ Audit ميداني فعلي (Read-only) للمرحلة 0** عبر قراءة مباشرة لكل الملفات ذات الصلة (migrations، Models، Controllers، Services، Routes، Seeders، Blade views) وبحث شامل في المشروع بأكمله. تحويل كل عبارات "يحتاج تأكيد" الغامضة إلى نتيجة مؤكَّدة بدليل (ملف/سطر) أو "تعذّر التحقق" مع سبب دقيق (لا اتصال بقاعدة بيانات من بيئة التنفيذ — تم اختبار المنفذ 3306 فعلياً وأعاد "Connection refused"، ولا PHP مثبَّت لتشغيل artisan). تحديث Checklist المرحلة 0 بالكامل (بنود منجزة `[x]` مع دليل، وبندان `[ ]` مع سبب واضح لعدم الإمكان). إضافة قسمي "نتائج التحقق الميداني" (Environment/Database/Provider/Registration maturity/Routes/Legacy files/Tests/Security baseline/Unverified items) و"ترتيب المشاكل حسب الأولوية" (10 بنود P-01…P-10) داخل قسم المرحلة 0. تحديث سجل المخاطر العام: تدقيق R-01 (تأكيد أن الاستدعاء يُلتقَط عبر try/catch فلا يُسبِّب 500، لكنه يُعطِّل الفحص 100%)، R-08 (فحص Baseline لملفات `Log::` في طبقة خدمات الدومينات لم يُظهر تسريب أسرار)، R-10 (تأكيد أن Cloudflare مدعوم بالكامل في الواجهة/التحقق ويُنشَأ نشطاً افتراضياً عبر Seeder). اكتشاف واكتشافات جديدة غير واردة سابقاً: (أ) `InvoiceSettlementService::markPaid()` تُنفِّذ تسجيل الدومين داخل نفس `DB::transaction` الخاصة بتفعيل الدفع، وفشل التسجيل يُراجِع حالة الفاتورة رغم نجاح الدفع الخارجي فعلياً — دليل كودي مباشر لسيناريو R-07؛ (ب) Route/View مكسور فعلياً: `GET /domains` (`domains.page`) يُعيد `view('domains.search')` غير الموجود إطلاقاً؛ (ج) `App\Models\Domain::checkAvailability()` دالة زائفة تُعيد `true` دائماً؛ (د) مخالفات ترجمة مؤكَّدة (`__()` بدل `t()` في 3 ملفات، 69 استدعاء) وغياب `dir="ltr" font-mono"` في نموذج مزوّد جديد. تحديث جدول "متابعة تقدم التنفيذ": حالة المرحلة 0 من `In Progress` إلى `Blocked` (سبب: تعذّر تأكيد نشاط Enom في الإنتاج ونضج التسجيل الفعلي لغياب وصول لبيانات حيّة، وليس نقصاً في المراجعة الكودية). **لم يُعدَّل أي ملف كود، Route، Migration، أو Interface/Service/Controller/Factory ضمن هذا التحديث — تعديل هذه الوثيقة فقط. لم يُنفَّذ أي اتصال أو عملية Live مع أي مزوّد خارجي. لم يتغيّر أي قرار من قرارات المرحلة 0.5 عن حالته `Pending`. عمود "الاعتماد" لم يتغيّر (يبقى `Pending` — الاعتماد حق Project Owner حصراً).** | Claude (بناءً على طلب Project Owner) |
| 1.3 — ملاحظة لاحقة | 2026-07-21 | **ملاحظة لاحقة**: بعد اعتماد واجهة البحث الرسمية عبر Section داخل Page Builder، أُعيد تصنيف غياب ‎`‎resources/views/domains/search.blade.php‎`‎ من مشكلة وظيفية إلى ‎`‎Not Applicable‎`‎، لأن التنفيذ المعتمد أصبح عبر ‎`‎resources/views/front/sections/templates/domain_search.blade.php‎`‎. وبقي Route ‎`‎domains.page‎`‎ عنصراً ‎`‎Legacy / Needs Confirmation‎`‎ يحتاج مراجعة لاحقة فقط. (هذه الملاحظة توضيحية فقط ولا تُلغي أو تُعيد كتابة سجل الإصدار 1.3 أعلاه، وهو باقٍ كما هو كسجل تاريخي). | Claude (بناءً على طلب Project Owner) |
| 1.3 — ملاحظة لاحقة ثانية | 2026-07-22 | **تصحيح لاحق ثانٍ**: مراجعة معمارية شاملة (Current Architecture Inventory) أثبتت بدليل كودي مباشر أن Route `domains.page` **مستخدَم فعلياً وبشكل حي** من ثلاثة مصادر: رابط في فوتر الموقع (`front/layouts/footers/palgoals_marketing.blade.php`)، رابط في القائمة الافتراضية (`front/layouts/partials/footer/menu-links.blade.php`)، ونموذج بحث `GET` داخل Section مسجَّل فعلياً (`components/template/sections/domains_showcase.blade.php`). بناءً على ذلك، **أُلغي تصنيف `Legacy / Needs Confirmation`** الذي مُنح لهذا الـ Route في الملاحظة اللاحقة الأولى أعلاه، **وأُعيد تصنيفه كعطل وظيفي مؤكَّد (`Active Broken Route / Functional Blocker`)**. أُضيف الخطر R-12 إلى سجل المخاطر العام (Critical / Confirmed)، وحُدِّث بند الأولوية P-06 من `Low / Needs Confirmation` إلى `Blocker مؤكَّد`، وأُضيف قرار معماري جديد (القرار 11) في المرحلة 0.5 بمستوى تعطيل `Blocking for Phase 3` لحسم مصير الـ Route وروابطه وSection `domains_showcase` وازدواجية واجهات البحث الثلاث. كما وُثِّق اكتشاف أن `domains_showcase.blade.php` يمثل واجهة بحث دومين ثالثة موازية لـ `domain_search.blade.php` وَ`search-domain.blade.php`. **لم يُصلَح أي Route، ولم يُنشأ أي View، ولم تُعدَّل روابط الفوتر أو ملف `domains_showcase.blade.php`، ولم يُعتمَد أي قرار معماري (القرار 11 لا يزال `Pending`) — تعديل توثيقي بحت على هذه الوثيقة فقط. لم يتغيّر Version أو Current Phase أو Overall Status أو Approval Status.** | Claude (بناءً على طلب Project Owner) |
