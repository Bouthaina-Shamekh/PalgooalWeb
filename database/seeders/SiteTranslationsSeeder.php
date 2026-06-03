<?php

namespace Database\Seeders;

use App\Models\TranslationValue;
use Illuminate\Database\Seeder;

class SiteTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            // ── Hero ─────────────────────────────────────────────
            'site.Live'                      => 'مباشر',
            'site.Draft'                     => 'مسودة',
            'site.Provisioned'               => 'تم الإعداد',
            'site.Pending'                   => 'قيد الانتظار',
            'site.Site_Dashboard'            => 'لوحة تحكم الموقع',
            'site.Hero_Subtitle'             => 'موقعك جاهز. ابدأ بالصفحة الرئيسية لتجعله يعبّر عن نشاطك، ثم أكمل التحسينات.',
            'site.Preview'                   => 'معاينة',
            'site.Template'                  => 'القالب',
            'site.Status'                    => 'الحالة',

            // ── Domain ───────────────────────────────────────────
            'site.Domain'                    => 'النطاق',
            'site.Platform_Subdomain_Active' => 'النطاق الفرعي نشط',
            'site.Verification_Pending'      => 'قيد التحقق (لم يُكتشف DNS بعد)',
            'site.Your_Site_Is_Live'         => 'موقعك يعمل الآن',
            'site.Use_Your_Own_Domain'       => 'استخدم نطاقك الخاص',
            'site.Domain_Summary_Subdomain'  => 'نطاقك الفرعي يعمل بالفعل. يمكنك ربط نطاقك الخاص في أي وقت.',
            'site.Domain_Summary_Active'     => 'نطاقك المخصص نشط الآن. النطاق الفرعي لا يزال يعمل كاحتياطي.',
            'site.Domain_Summary_Ssl'        => 'وجدنا النطاق في DNS، لكن HTTPS لا يزال جاري الإعداد. شارك النطاق الفرعي في الوقت الحالي.',
            'site.Domain_Summary_Failed'     => 'تعذّر التحقق من نطاقك المخصص. النطاق الفرعي لا يزال يعمل أثناء مراجعة إعدادات النطاق.',
            'site.Domain_Summary_Checking'   => 'نطاقك المخصص قيد الفحص. النطاق الفرعي يعمل حتى يكتمل كل شيء.',
            'site.Domain_Summary_Pending'    => 'نطاقك المخصص قيد الفحص. النطاق الفرعي يعمل حتى يكتمل كل شيء.',

            // ── Onboarding steps - primary buttons ───────────────
            'site.Start_Editing'             => 'ابدأ بتعديل موقعك',
            'site.Manage_Your_Pages'         => 'أدر صفحاتك',
            'site.Connect_Your_Domain'       => 'اربط نطاقك',
            'site.View_Website'              => 'عرض الموقع',

            // ── Onboarding steps - titles ─────────────────────────
            'site.Start_With_Homepage'       => 'ابدأ بالصفحة الرئيسية',
            'site.Review_Your_Pages'         => 'راجع صفحاتك',
            'site.Setup_Complete'            => 'اكتمل إعداد موقعك',

            // ── Onboarding steps - helper texts ──────────────────
            'site.Step_Helper_Homepage'      => 'ابدأ بالصفحة الرئيسية — هذه أسرع طريقة لتجعل موقعك يعبّر عن نشاطك.',
            'site.Step_Helper_Pages'         => 'راجع الصفحات التي جاءت مع موقعك وأضف أو حسّن أي محتوى جديد.',
            'site.Step_Helper_Domain'        => 'موقعك يعمل على النطاق الفرعي. اربط نطاقاً مخصصاً عندما تكون مستعداً.',
            'site.Step_Helper_Complete'      => 'الصفحة الرئيسية والصفحات والنطاق كلها جاهزة. يمكنك الاستمرار في التحسين في أي وقت.',

            // ── Onboarding steps - buttons ────────────────────────
            'site.Continue_Editing'          => 'أكمل التعديل',
            'site.Manage_Pages'              => 'إدارة الصفحات',
            'site.Open_Domain_Settings'      => 'إعدادات النطاق',

            // ── Onboarding steps - hints ──────────────────────────
            'site.Hint_Review_Pages'         => 'التالي: ستراجع صفحاتك',
            'site.Hint_Connect_Domain'       => 'التالي: ستربط نطاقك',
            'site.Hint_Onboarding_Complete'  => 'التالي: سيكتمل إعداد موقعك',
            'site.Hint_Ready_To_Share'       => 'كل شيء جاهز للمشاركة',

            // ── Progress counter ──────────────────────────────────
            'site.Step_Of_Total'             => 'الخطوة :step من :total',
            'site.Steps_Completed_Of'        => ':completed من :total مكتملة',
            'site.Current_Step'              => 'الخطوة الحالية',
            'site.Completed'                 => 'مكتملة',
            'site.Completed_So_Far'          => 'ما تم حتى الآن',

            // ── Milestones ────────────────────────────────────────
            'site.Homepage_Completed'        => 'اكتملت الصفحة الرئيسية ✓',
            'site.Pages_Completed'           => 'اكتملت الصفحات ✓',
            'site.Domain_Connected'          => 'تم ربط النطاق ✓',

            // ── Momentum messages ─────────────────────────────────
            'site.Momentum_Default'          => 'بمجرد أن تشعر أن صفحتك الرئيسية مناسبة، الخطوة التالية هي مراجعة بقية الصفحات.',
            'site.Momentum_Pages'            => 'تقدم رائع! التالي: راجع بقية صفحاتك ليتمكن الزوار من الاستكشاف.',
            'site.Momentum_Pages_With_Msg'   => 'تقدم رائع! :message. التالي: راجع بقية صفحاتك ليتمكن الزوار من الاستكشاف.',
            'site.Momentum_Domain'           => 'تقدم رائع! التالي: اربط نطاقك عندما تكون مستعداً.',
            'site.Momentum_Domain_With_Msg'  => 'تقدم رائع! :message. التالي: اربط نطاقك عندما تكون مستعداً.',
            'site.Momentum_Complete'         => 'الصفحة الرئيسية مكتملة، الصفحات مراجعة، والنطاق مربوط. موقعك جاهز للمشاركة!',

            // ── Sidebar navigation ────────────────────────────────
            'site.Dashboard'                 => 'لوحة التحكم',
            'site.Pages'                     => 'الصفحات',
            'site.Editor'                    => 'المحرر',
            'site.Header'                    => 'الترويسة',
            'site.Footer'                    => 'التذييل',
            'site.Settings'                  => 'الإعدادات',
            'site.Back_To_Account'           => 'العودة للحساب',

            // ── Sidebar section titles ────────────────────────────
            'site.Main'                      => 'رئيسي',
            'site.Growth'                    => 'النمو',

            // ── Quick action buttons ──────────────────────────────
            'site.Edit_Header'               => 'تعديل الترويسة',
            'site.Edit_Footer'               => 'تعديل التذييل',
            'site.Connect_Domain'            => 'ربط النطاق',
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

        $this->command->info("✅ Arabic site translations: {$created} created, {$updated} updated.");
    }
}
