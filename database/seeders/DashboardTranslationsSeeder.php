<?php

namespace Database\Seeders;

use App\Models\TranslationValue;
use Illuminate\Database\Seeder;

class DashboardTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            // ── Sidebar user card ─────────────────────────────────
            'dashboard.My_Account'          => 'حسابي',
            'dashboard.Settings'            => 'الإعدادات',
            'dashboard.Logout'              => 'تسجيل الخروج',

            // ── Section: Navigation ───────────────────────────────
            'dashboard.Navigation'          => 'التنقل',
            'dashboard.Home'                => 'الرئيسية',
            'dashboard.Pages'               => 'الصفحات',

            // ── Template management ───────────────────────────────
            'dashboard.Template_management' => 'إدارة القوالب',
            'dashboard.All_templates'       => 'كل القوالب',
            'dashboard.Categories'          => 'التصنيفات',
            'dashboard.reviews'             => 'التقييمات',

            // ── Content ───────────────────────────────────────────
            'dashboard.services'            => 'الخدمات',
            'dashboard.testimonials'        => 'الشهادات',
            'dashboard.portfolios'          => 'المحافظ',

            // ── Section: Clients ──────────────────────────────────
            'dashboard.clients'             => 'العملاء',
            'dashboard.domains'             => 'النطاقات',
            'dashboard.plans'               => 'الباقات',
            'dashboard.plan-categories'     => 'تصنيفات الباقات',
            'dashboard.subscriptions'       => 'الاشتراكات',
            'dashboard.invoices'            => 'الفواتير',
            'dashboard.orders'              => 'الطلبات',

            // ── CRM management ────────────────────────────────────
            'dashboard.CRM_management'      => 'إدارة CRM',
            'dashboard.servers'             => 'السيرفرات',
            'dashboard.domain_providers'    => 'مزودو النطاقات',
            'dashboard.domain-tlds'         => 'امتدادات النطاقات',
            'dashboard.sync-logs'           => 'سجلات المزامنة',

            // ── Section: Site settings ────────────────────────────
            'dashboard.Site_settings'       => 'إعدادات الموقع',
            'dashboard.media'               => 'الوسائط',

            // ── Appearance ────────────────────────────────────────
            'dashboard.Appearance'          => 'المظهر',
            'dashboard.Header_Layout'       => 'تصميم الترويسة',
            'dashboard.Footer_Layout'       => 'تصميم التذييل',
            'dashboard.Menus'               => 'القوائم',
            'dashboard.languages'           => 'اللغات',

            // ── Users ─────────────────────────────────────────────
            'dashboard.Users'               => 'المستخدمون',
            'dashboard.Users_show'          => 'عرض المستخدمين',
            'dashboard.Add_User'            => 'إضافة مستخدم',

            // ── General ───────────────────────────────────────────
            'dashboard.General_Setting'     => 'الإعدادات العامة',

            // ── Servers table & actions ───────────────────────────
            'dashboard.Hostname'            => 'اسم المضيف',
            'dashboard.Search_Servers'      => 'بحث عن السيرفرات...',
            'dashboard.Per_Page'            => 'لكل صفحة',
            'dashboard.Add_Server'          => 'إضافة سيرفر',
            'dashboard.Server_Name'         => 'الاسم',
            'dashboard.Server_Type'         => 'النوع',
            'dashboard.Status'              => 'الحالة',
            'dashboard.Actions'             => 'خيارات',
            'dashboard.Active'              => 'مفعل',
            'dashboard.Inactive'            => 'معطل',
            'dashboard.Edit'                => 'تعديل',
            'dashboard.Test_Connection'     => 'اختبار الاتصال',
            'dashboard.View_Accounts'       => 'عرض المواقع',
            'dashboard.Login_SSO'           => 'دخول السيرفر (SSO)',
            'dashboard.Delete'              => 'حذف',
            'dashboard.Confirm_Delete'      => 'هل أنت متأكد من الحذف؟',
            'dashboard.Clear_Search'        => 'مسح البحث',
            'dashboard.No_Servers'          => 'لا يوجد سيرفرات بعد',
            'dashboard.No_Servers_Desc'     => 'أضف سيرفرك الأول لبدء إدارة حسابات الاستضافة',
            'dashboard.No_Search_Results'   => 'لا توجد نتائج',
            'dashboard.Try_Different_Search'=> 'جرب مصطلح بحث مختلف',

            // ── Plans table ───────────────────────────────────────
            'dashboard.Search_Plans'        => 'بحث عن الباقات...',
            'dashboard.Add_Plan'            => 'إضافة باقة',
            'dashboard.Plan_Name'           => 'الاسم',
            'dashboard.Plan_Category'       => 'التصنيف',
            'dashboard.Plan_Server'         => 'السيرفر',
            'dashboard.Plan_Price'          => 'السعر',
            'dashboard.Plan_Featured'       => 'مميزة',
            'dashboard.Monthly'             => 'شهري',
            'dashboard.Annual'              => 'سنوي',
            'dashboard.Most_Popular'        => 'الأكثر شعبية',
            'dashboard.Toggle_Status'       => 'تغيير الحالة',
            'dashboard.No_Plans'            => 'لا توجد باقات بعد',
            'dashboard.No_Plans_Desc'       => 'أنشئ باقتك الأولى لبدء تقديم الاشتراكات',

            // ── Create / Edit server form ─────────────────────────
            'dashboard.Basic_Info'              => 'المعلومات الأساسية',
            'dashboard.Connection_Info'         => 'تفاصيل الاتصال',
            'dashboard.Auth_Info'               => 'بيانات الدخول',
            'dashboard.Panel_Type'              => 'نوع لوحة التحكم',
            'dashboard.Server_Name_Placeholder' => 'مثال: سيرفر الإنتاج الرئيسي',
            'dashboard.Connection_Hint'         => 'أدخل عنوان IP أو اسم المضيف — يُطلب واحد على الأقل لإتمام الاتصال.',
            'dashboard.Server_IP'               => 'عنوان IP',
            'dashboard.Username'                => 'اسم المستخدم',
            'dashboard.Password'                => 'كلمة المرور',
            'dashboard.Password_Or_Token_Hint'  => 'استخدم كلمة المرور أو API Token (لا يلزم الاثنان)',
            'dashboard.Api_Token'               => 'API Token',
            'dashboard.Recommended'             => 'موصى به',
            'dashboard.Api_Token_Hint'          => 'أنشئه من WHM ← Development ← API Tokens',
            'dashboard.Save'                    => 'حفظ',
            'dashboard.Cancel'                  => 'إلغاء',

            // ── Plans create / edit form ──────────────────────────
            'dashboard.Add_Hosting_Plan'            => 'إضافة باقة استضافة',
            'dashboard.Plan_Slug'                   => 'رابط الباقة',
            'dashboard.Optional'                    => 'اختياري',
            'dashboard.Slug_Auto_Generated'         => 'يُنشأ تلقائياً إذا تُرك فارغاً',
            'dashboard.Pricing_And_Category'        => 'التسعير',
            'dashboard.Plan_Type'                   => 'نوع الباقة',
            'dashboard.Plan_Type_Multi_Tenant'      => 'متعدد المستأجرين (بدون cPanel)',
            'dashboard.Plan_Type_Hosting'           => 'استضافة / ووردبريس (يتضمن cPanel)',
            'dashboard.Plan_Type_Hint'              => 'يحدد إذا كان الاشتراك يعمل داخل منصة Palgoals أو يحتاج مساحة استضافة مستقلة',
            'dashboard.Monthly_Price_USD'           => 'السعر الشهري (USD)',
            'dashboard.Annual_Price_USD'            => 'السعر السنوي (USD)',
            'dashboard.Plan_Settings'               => 'إعدادات الباقة',
            'dashboard.Active_Available'            => 'مفعلة (متاحة للبيع)',
            'dashboard.Inactive_Hidden'             => 'معطلة (مخفية)',
            'dashboard.Featured_Plan'               => 'باقة مميزة',
            'dashboard.Featured_Plan_Hint'          => 'يظهر شارة مميزة على بطاقة الباقة',
            'dashboard.Featured_Badge_Label'        => 'نص شارة التميز',
            'dashboard.Featured_Badge_Label_Hint'   => 'يظهر عند تمييز الباقة. اتركه فارغاً للنص الافتراضي',
            'dashboard.Server_Package'              => 'حزمة السيرفر',
            'dashboard.Select_Server_First'         => 'حدد السيرفر أولاً',
            'dashboard.Loading'                     => 'جاري التحميل...',
            'dashboard.None'                        => 'لا شيء',
            'dashboard.Plan_Name_Label'             => 'اسم الباقة',
            'dashboard.Description'                 => 'الوصف',
            'dashboard.Plan_Features'               => 'مميزات الباقة',
            'dashboard.Available'                   => 'متاح',
            'dashboard.Remove_Feature'              => 'إزالة',
            'dashboard.Add_Feature'                 => 'إضافة ميزة',
            'dashboard.Feature_Toggle_Hint'         => 'استخدم مربع التحقق لتحديد إذا كانت الميزة متضمنة',
            'dashboard.Create_Plan'                 => 'إنشاء الباقة',

            // ── Help sidebar ──────────────────────────────────────
            'dashboard.Help'                    => 'مساعدة',
            'dashboard.Help_IP_Hostname'        => 'IP مقابل Hostname',
            'dashboard.Help_IP_Hostname_Desc'   => 'يمكنك إدخال IP أو اسم مضيف أو كليهما. يُعطى الأولوية لاسم المضيف عند وجود الاثنين.',
            'dashboard.Help_Api_Token'          => 'API Token',
            'dashboard.Help_Api_Token_Desc'     => 'الـ API Token أكثر أماناً من كلمة المرور. أنشئه من WHM ← Development ← API Tokens.',
            'dashboard.Help_Panel_Type'         => 'نوع لوحة التحكم',
            'dashboard.Help_Panel_Type_Desc'    => 'اختر cPanel/WHM لسيرفرات cPanel. يتوفر دعم DirectAdmin لأنواع اللوحات الأخرى.',

            // ── Help sidebar (plans) ──────────────────────────────
            'dashboard.Help_Plan_Type'          => 'نوع الباقة',
            'dashboard.Help_Plan_Type_Desc'     => 'متعدد المستأجرين: يعمل داخل منصة Palgoals المشتركة. استضافة: يُنشأ حساب cPanel مستقل على أحد السيرفرات.',
            'dashboard.Help_Featured'           => 'الباقة المميزة',
            'dashboard.Help_Featured_Desc'      => 'تظهر الباقات المميزة مع شارة بارزة. يمكنك تخصيص نص الشارة لكل لغة.',
            'dashboard.Help_Server_Package'     => 'حزمة السيرفر',
            'dashboard.Help_Server_Package_Desc'=> 'تُستخدم عند إنشاء حسابات الاستضافة تلقائياً. حدد السيرفر أولاً لتحميل الحزم المتاحة من WHM.',

            // ── Subscriptions create / edit form ──────────────────
            'dashboard.Add_New_Subscription'        => 'إضافة اشتراك جديد',
            'dashboard.Subscription_Info'           => 'معلومات الاشتراك',
            'dashboard.Choose_Client'               => '-- اختر عميلاً --',
            'dashboard.Choose_Plan'                 => '-- اختر باقة --',
            'dashboard.Price_USD'                   => 'السعر (USD)',
            'dashboard.Price_Hint'                  => 'يُملأ تلقائياً عند اختيار الباقة. يمكنك تعديله.',
            'dashboard.Domain_And_Server'           => 'الدومين والسيرفر',
            'dashboard.Domain_Type'                 => 'نوع الدومين',
            'dashboard.Domain_Subdomain'            => 'استخدام سب-دومين (المنصة)',
            'dashboard.Domain_Existing'             => 'دومين خاص بالعميل',
            'dashboard.Domain_New'                  => 'تسجيل دومين جديد',
            'dashboard.Domain_Name_Label'           => 'اسم الدومين',
            'dashboard.Domain_Name_Placeholder'     => 'مثال: example.com',
            'dashboard.Domain_Subdomain_Hint'       => 'مثال: client.palgoals.com',
            'dashboard.Domain_Existing_Hint'        => 'أدخل الدومين بدون http(s)://',
            'dashboard.Domain_New_Hint'             => 'أدخل الدومين الجديد المراد تسجيله',
            'dashboard.Username_Label'              => 'اسم المستخدم (cPanel)',
            'dashboard.Username_Placeholder'        => 'مثال: john123',
            'dashboard.Username_Hint'               => 'يُنشأ تلقائياً من اسم العميل / الدومين. 8 أحرف كحد أقصى.',
            'dashboard.Suggest'                     => 'اقتراح',
            'dashboard.Schedule'                    => 'المواعيد',
            'dashboard.Start_Date'                  => 'تاريخ البداية',
            'dashboard.Next_Due_Date'               => 'تاريخ الاستحقاق القادم',
            'dashboard.End_Date'                    => 'تاريخ الانتهاء',
            'dashboard.End_Date_Hint'               => 'اختياري — اتركه فارغاً للاشتراكات المفتوحة',
            'dashboard.Create_Subscription'         => 'إنشاء الاشتراك',
            'dashboard.Subscription_Created'        => 'تم إنشاء الاشتراك بنجاح.',
            'dashboard.Help_Price'                  => 'سعر الباقة',
            'dashboard.Help_Price_Desc'             => 'يُملأ تلقائياً من السعر الشهري للباقة عند اختيارها. يمكنك تعديله لتسعير مخصص.',
            'dashboard.Help_Domain_Type'            => 'نوع الدومين',
            'dashboard.Help_Domain_Type_Desc'       => 'سب-دومين: يستخدم دومين فرعي من المنصة. دومين خاص: العميل يُحضر دومينه. دومين جديد: تسجيل دومين (يدوي).',
            'dashboard.Help_Server'                 => 'السيرفر واسم المستخدم',
            'dashboard.Help_Server_Desc'            => 'لباقات الاستضافة، اختر سيرفر cPanel وحدد اسم مستخدم (8 أحرف كحد أقصى). اضغط "اقتراح" لتوليد الاسم تلقائياً.',
            'dashboard.Help_Schedule'               => 'التواريخ',
            'dashboard.Help_Schedule_Desc'          => 'تاريخ البداية مطلوب. تاريخ الاستحقاق للتذكير بالفواتير. تاريخ الانتهاء اختياري للاشتراكات غير المحددة المدة.',

            // ── Subscriptions index ───────────────────────────────
            'dashboard.Subscriptions_List'          => 'قائمة الاشتراكات',
            'dashboard.Add_Subscription'            => 'إضافة اشتراك',
            'dashboard.Search_Client_Domain'        => 'بحث بالعميل أو الدومين...',
            'dashboard.Filter_Domain_Placeholder'   => 'فلتر بالدومين...',
            'dashboard.All_Statuses'                => 'كل الحالات',
            'dashboard.Status_Active'               => 'نشط',
            'dashboard.Status_Pending'              => 'معلق',
            'dashboard.Status_Suspended'            => 'موقوف',
            'dashboard.Status_Cancelled'            => 'ملغي',
            'dashboard.Sort_By'                     => 'ترتيب',
            'dashboard.Sort_Domain'                 => 'الدومين',
            'dashboard.Sort_Start_Date'             => 'تاريخ البدء',
            'dashboard.Ascending'                   => 'تصاعدي',
            'dashboard.Descending'                  => 'تنازلي',
            'dashboard.Total'                       => 'الإجمالي',
            'dashboard.Bulk_Action_Placeholder'     => 'اختر إجراء جماعي',
            'dashboard.Bulk_Suspend'                => 'تعليق',
            'dashboard.Bulk_Unsuspend'              => 'إلغاء التعليق',
            'dashboard.Bulk_Sync'                   => 'مزامنة',
            'dashboard.Bulk_Terminate'              => 'حذف نهائي من السيرفر',
            'dashboard.Bulk_Delete'                 => 'حذف من القاعدة',
            'dashboard.Apply'                       => 'تطبيق ←',
            'dashboard.Client'                      => 'العميل',
            'dashboard.Server_Package_Col'          => 'حزمة السيرفر',
            'dashboard.Domain_Col'                  => 'الدومين',
            'dashboard.Sync_Result'                 => 'نتيجة المزامنة',
            'dashboard.Copy_Domain'                 => 'نسخ',
            'dashboard.Copied'                      => 'تم النسخ',
            'dashboard.Login_CPanel'                => 'دخول cPanel',
            'dashboard.Suspend'                     => 'تعليق',
            'dashboard.Unsuspend'                   => 'إلغاء التعليق',
            'dashboard.Bulk_Terminate'              => 'حذف نهائي من السيرفر',
            'dashboard.Provision_Reactivate'        => 'إعادة التفعيل',
            'dashboard.Sync_Success'                => 'نجحت',
            'dashboard.Sync_Failed'                 => 'فشل',
            'dashboard.Sync_Pending'                => 'قيد التنفيذ',
            'dashboard.Sync_Unknown'                => 'غير معروف',
            'dashboard.No_Subscriptions'            => 'لا توجد اشتراكات بعد',
            'dashboard.No_Subscriptions_Desc'       => 'أضف أول اشتراك لبدء إدارة مواقع عملائك',
            'dashboard.Terminate_Confirm'           => 'سيتم حذف الموقع من السيرفر نهائيًا. هل أنت متأكد؟',
            'dashboard.Bulk_Select_Action'          => 'اختر إجراءً أولاً',
            'dashboard.Bulk_Select_Min_One'         => 'اختر اشتراك واحد على الأقل',
            'dashboard.Bulk_Confirm_Suffix'         => 'اشتراك(ات) سيتأثر/تتأثر. متابعة؟',
            'dashboard.Error_Try_Again'             => 'حدث خطأ، يرجى المحاولة مرة أخرى',
            'dashboard.Verify_Domain'               => 'التحقق من الدومين',

            // ── Domain verification badge labels ──────────────────
            'dashboard.Domain_Platform_Active'      => 'الدومين الفرعي للمنصة نشط',
            'dashboard.Domain_Custom_Active'        => 'الدومين المخصص نشط',
            'dashboard.Domain_SSL_Pending'          => 'في انتظار HTTPS',
            'dashboard.Domain_DNS_Pending'          => 'في انتظار التحقق (DNS)',
            'dashboard.Domain_Verification_Failed'  => 'فشل التحقق من الدومين',
        ];

        $locale = 'ar';
        $created = 0;
        $updated = 0;

        // Clear translation cache before inserting
        cache()->flush();

        foreach ($translations as $key => $value) {
            $record = TranslationValue::firstOrNew(['key' => $key, 'locale' => $locale]);
            $record->value = $value;
            $record->save();

            if ($record->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        $this->command->info("✅ Arabic dashboard translations: {$created} created, {$updated} updated.");
    }
}
