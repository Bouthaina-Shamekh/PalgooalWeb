<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FRAUD     = 'fraud';

    /**
     * ملاحظة: أبعدنا order_number من fillable كي لا يُعدَّل بالـ mass assignment.
     * يتم توليده تلقائيًا في booted().
     */
    protected $fillable = [
        'client_id',
        'status',
        'type',
        'notes',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'status'    => 'string',
        'type'      => 'string',
        'notes'     => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            // توليد رقم الطلب إذا لم يُمرَّر
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
            }

            // تعيين الحالة الافتراضية
            if (empty($order->status)) {
                $order->status = self::STATUS_PENDING;
            } else {
                // توحيد القيم لضمان صحة الحالة
                $allowed = [
                    self::STATUS_PENDING,
                    self::STATUS_ACTIVE,
                    self::STATUS_CANCELLED,
                    self::STATUS_FRAUD,
                ];
                if (! in_array($order->status, $allowed, true)) {
                    $order->status = self::STATUS_PENDING;
                }
            }
        });

        // منع تعديل رقم الطلب بعد الإنشاء (حماية إضافية)
        static::updating(function (Order $order) {
            if ($order->isDirty('order_number')) {
                $order->order_number = $order->getOriginal('order_number');
            }
        });
    }

    // في حال حاول أحدهم set للخاصية مباشرةً بعد الإنشاء
    public function setOrderNumberAttribute($value): void
    {
        if ($this->exists && $this->getOriginal('order_number')) {
            // تجاهل أي محاولة لتغيير رقم الطلب بعد وجود قيمة أصلية
            return;
        }
        $this->attributes['order_number'] = $value;
    }

    public function client(): BelongsTo
    {
        // client_id الآن Nullable
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * مساعد لحساب إجمالي السنتات من بنود الطلب.
     */
    public function subtotalCents(): int
    {
        return (int) $this->items()->sum('price_cents');
    }

    /**
     * Accessor اختياري إن حبيت تستخدم $order->total_cents مباشرة.
     */
    public function getTotalCentsAttribute(): int
    {
        return $this->subtotalCents();
    }

    /**
     * سكوب مريح للتصفية بالحالة.
     */
    public function scopeStatus($query, string $status)
    {
        $allowed = [
            self::STATUS_PENDING,
            self::STATUS_ACTIVE,
            self::STATUS_CANCELLED,
            self::STATUS_FRAUD,
        ];
        if (in_array($status, $allowed, true)) {
            $query->where('status', $status);
        }
        return $query;
    }
}
