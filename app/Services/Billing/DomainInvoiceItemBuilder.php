<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

/**
 * ADR — توحيد InvoiceItems لفواتير الدومينات (Phase 1).
 *
 * مسؤولية محدودة وحيدة: تحويل OrderItem موثوق (تم إنشاؤه مسبقًا بسعر مُتحقَّق منه فعليًا
 * عبر DomainPricingService/DomainAvailabilityService في الطبقة الأعلى) إلى بيانات/سجلات
 * InvoiceItem متّسقة الوصف والمبلغ.
 *
 * هذه الخدمة لا تُعيد حساب السعر، ولا تتحقق من التوفر، ولا تُنشئ Invoice، ولا تحسب
 * subtotal_cents/discount_cents/tax_cents/total_cents — كل ذلك يبقى بالكامل من مسؤولية
 * الكود المستدعي (CheckoutController). هي فقط "مترجم عرض" لبند فاتورة مطابق تمامًا لما
 * تم إنشاؤه فعلاً في order_items (نفس price_cents، بلا تقريب أو إعادة اشتقاق).
 *
 * تستقبل فقط OrderItem موثوقًا (Model) أو Collection منها — لا تقبل مصفوفات خام من Request.
 */
class DomainInvoiceItemBuilder
{
    /**
     * يبني Collection من مصفوفات خصائص InvoiceItem لمجموعة OrderItems موثوقة،
     * دون الكتابة إلى قاعدة البيانات ودون الحاجة لوجود Invoice بعد.
     *
     * الهدف: جعل هذه القيم (وتحديدًا total_cents) مصدر الحقيقة المحاسبي الذي يُشتق
     * منه subtotal الفاتورة — بدلاً من إعادة حساب المجموع من مصفوفة السلة/الـ Request.
     *
     * @param  Collection<int, OrderItem>|array<int, OrderItem> $orderItems
     * @return Collection<int, array>
     */
    public function buildAttributesForItems(Collection|array $orderItems, ?string $locale = null): Collection
    {
        $orderItems = $orderItems instanceof Collection ? $orderItems : collect($orderItems);

        return $orderItems->map(
            fn (OrderItem $orderItem) => $this->buildAttributes($orderItem, $locale)
        )->values();
    }

    /**
     * يُنشئ سجلات InvoiceItem الفعلية من خصائص مُجهَّزة مسبقًا عبر buildAttributesForItems().
     * لا تُعاد بناء الوصف/المبلغ هنا — هذه الدالة تُنفّذ الكتابة فقط، بعد أن يكون الكود
     * المستدعي قد استخدم نفس القيم لاشتقاق subtotal_cents الخاص بالفاتورة.
     *
     * لا تفتح DB::transaction خاصة بها — يجب استدعاؤها من داخل transaction الكود المستدعي
     * حتى تبقى ذرّية مع إنشاء الـ Order/order_items/Invoice.
     *
     * @param  Collection<int, array>|array<int, array> $invoiceItemAttributes
     * @return Collection<int, InvoiceItem>
     */
    public function persistItems(Invoice $invoice, Collection|array $invoiceItemAttributes): Collection
    {
        $invoiceItemAttributes = $invoiceItemAttributes instanceof Collection
            ? $invoiceItemAttributes
            : collect($invoiceItemAttributes);

        return $invoice->items()->createMany($invoiceItemAttributes->all());
    }

    /**
     * يبني مصفوفة خصائص بند فاتورة واحد يمثّل OrderItem دومين موثوق.
     * لا يُنشئ أي سجل في قاعدة البيانات — فقط يُعيد البيانات القابلة للإنشاء.
     * تبقى داخلية الاستخدام (تُستدعى عبر buildAttributesForItems فقط).
     */
    protected function buildAttributes(OrderItem $orderItem, ?string $locale = null): array
    {
        $priceCents = (int) $orderItem->price_cents;

        return [
            'item_type'        => 'domain',
            'reference_id'     => null,
            'description'      => $this->buildDescription($orderItem, $locale),
            'qty'              => 1,
            'unit_price_cents' => $priceCents,
            'total_cents'      => $priceCents,
        ];
    }

    /**
     * يبني وصف بند الفاتورة (مثال: "تسجيل دومين example.com لمدة سنة واحدة") عبر آلية
     * الترجمة الفعلية المستخدمة في المشروع (t()) مع استبدال المتغيرات بـ strtr()، وفق
     * قاعدة CLAUDE.md — t() لا تدعم parameter replacement مباشرة.
     */
    protected function buildDescription(OrderItem $orderItem, ?string $locale = null): string
    {
        $domain = (string) $orderItem->domain;
        $meta   = is_array($orderItem->meta) ? $orderItem->meta : [];
        $years  = max(1, (int) ($meta['years'] ?? 1));
        $option = strtolower(trim((string) $orderItem->item_option));

        $build = function () use ($domain, $years, $option): string {
            $yearsLabel = $this->yearsLabel($years);

            [$key, $default] = match (true) {
                in_array($option, ['register', 'new'], true) => [
                    'site.Invoice_Item_Domain_Register',
                    'تسجيل دومين :domain لمدة :years_label',
                ],
                $option === 'transfer' => [
                    'site.Invoice_Item_Domain_Transfer',
                    'نقل ملكية دومين :domain لمدة :years_label',
                ],
                $option === 'renew' => [
                    'site.Invoice_Item_Domain_Renew',
                    'تجديد دومين :domain لمدة :years_label',
                ],
                default => [
                    'site.Invoice_Item_Domain_Generic',
                    'دومين :domain لمدة :years_label',
                ],
            };

            return strtr(t($key, $default), [
                ':domain'      => $domain,
                ':years_label' => $yearsLabel,
            ]);
        };

        // t() تقرأ دائمًا app()->getLocale() الحالية ولا تقبل locale صراحةً؛ إن طُلبت لغة
        // مغايرة نُبدّل الـ locale مؤقتًا فقط أثناء بناء هذا الوصف ثم نُعيده كما كان.
        if ($locale !== null && $locale !== app()->getLocale()) {
            $original = app()->getLocale();
            app()->setLocale($locale);

            try {
                return $build();
            } finally {
                app()->setLocale($original);
            }
        }

        return $build();
    }

    protected function yearsLabel(int $years): string
    {
        return match (true) {
            $years === 1 => t('site.Invoice_Item_Years_One', 'سنة واحدة'),
            $years === 2 => t('site.Invoice_Item_Years_Two', 'سنتين'),
            $years >= 3 && $years <= 10 => strtr(t('site.Invoice_Item_Years_Few', ':years سنوات'), [':years' => $years]),
            default => strtr(t('site.Invoice_Item_Years_Many', ':years سنة'), [':years' => $years]),
        };
    }
}
