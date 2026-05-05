<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

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
                // Candidate only — real collision guard is in createWithUniqueNumber().
                $order->order_number = static::generateCandidateNumber();
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

    /**
     * Generate a candidate order number — no DB check.
     * Format: ORD-YYYYMMDD-XXXXXXXX (8 random chars ≈ 208 billion combinations per day).
     */
    public static function generateCandidateNumber(): string
    {
        return 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));
    }

    /**
     * Create an Order, retrying on unique order_number constraint violations (SQLSTATE 23000).
     * Use this instead of Order::create() whenever order_number must be auto-generated.
     *
     * @param  array<string, mixed>  $attributes  Must NOT include 'order_number'.
     * @param  int                   $maxAttempts
     * @return static
     */
    public static function createWithUniqueNumber(array $attributes, int $maxAttempts = 5): static
    {
        $lastException = null;

        for ($i = 0; $i < $maxAttempts; $i++) {
            try {
                $attributes['order_number'] = static::generateCandidateNumber();
                return static::create($attributes);
            } catch (\Illuminate\Database\QueryException $e) {
                if ($i < $maxAttempts - 1 && str_contains($e->getMessage(), '23000')) {
                    $lastException = $e;
                    continue;
                }
                throw $e;
            }
        }

        throw $lastException;
    }

    public function client(): BelongsTo
    {
        // client_id is nullable (set to NULL on client delete to preserve accounting records).
        // withDefault() prevents null-pointer errors when accessing $order->client->name.
        return $this->belongsTo(Client::class)->withDefault([
            'first_name' => '—',
            'last_name'  => '',
            'email'      => '—',
            'phone'      => '—',
        ]);
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
        // Use the already-loaded collection to avoid an extra DB round-trip.
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum('price_cents');
        }
        return (int) $this->items()->sum('price_cents');
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
