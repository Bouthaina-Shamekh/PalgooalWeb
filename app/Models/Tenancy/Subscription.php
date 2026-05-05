<?php

namespace App\Models\Tenancy;

use App\Models\Client;
use App\Models\Coupon;
use App\Models\Domain;
use App\Models\InvoiceItem;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Subscription extends Model
{
    use HasFactory, SoftDeletes;

    public const PROVISIONING_PENDING = 'pending';
    public const PROVISIONING_IN_PROGRESS = 'provisioning';
    public const PROVISIONING_ACTIVE = 'active';
    public const PROVISIONING_FAILED = 'failed';

    public const DOMAIN_VERIFICATION_PENDING = 'pending';
    public const DOMAIN_VERIFICATION_DNS_PENDING = 'dns_pending';
    public const DOMAIN_VERIFICATION_SSL_PENDING = 'ssl_pending';
    public const DOMAIN_VERIFICATION_ACTIVE = 'active';
    public const DOMAIN_VERIFICATION_FAILED = 'failed';

    protected $fillable = [
        'client_id',
        'plan_id',
        'template_id',
        'status',
        'provisioning_status',
        'provisioned_at',
        'last_sync_message',
        'price',
        'billing_cycle',
        'engine',
        'username',
        'cpanel_username',
        'cpanel_password',
        'cpanel_url',
        'server_id',
        'server_package',
        'next_due_date',
        'last_synced_at',
        'starts_at',
        'ends_at',
        'domain_option',
        'domain_name',
        'subdomain',
        'domain_id',
        'domain_verification_status',
        'domain_last_checked_at',
        'domain_verified_at',
        'domain_verification_error',
        'settings',
        'theme_settings',
    ];

    protected $casts = [
        'price' => 'float',
        'next_due_date' => 'date',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'provisioned_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'domain_last_checked_at' => 'datetime',
        'domain_verified_at' => 'datetime',
        'settings' => 'array',
        'theme_settings' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Server::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Canonical tenant pages cloned into the shared Page + Section system.
     */
    public function canonicalPages(): HasMany
    {
        return $this->hasMany(Page::class, 'tenant_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'reference_id')
            ->where('item_type', 'subscription');
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class)
            ->withTimestamps();
    }

    public function normalizedDomainName(): ?string
    {
        $domain = trim(strtolower((string) $this->domain_name), ". \t\n\r\0\x0B");

        return $domain !== '' ? $domain : null;
    }

    public function requiresDomainVerification(): bool
    {
        $this->logDomainBindingMismatchIfNeeded();

        $domain = $this->normalizedDomainName();

        if ($domain === null) {
            return false;
        }

        if ($this->domain_option === 'subdomain') {
            return false;
        }

        return ! is_platform_tenant_host($domain);
    }

    public function customDomainHost(): ?string
    {
        return $this->requiresDomainVerification()
            ? $this->normalizedDomainName()
            : null;
    }

    public function fallbackSiteHost(): ?string
    {
        $subdomain = trim(strtolower((string) $this->subdomain), ". \t\n\r\0\x0B");

        if ($subdomain !== '') {
            return tenant_fqdn($subdomain);
        }

        $domain = $this->normalizedDomainName();

        if ($domain !== null && ($this->domain_option === 'subdomain' || is_platform_tenant_host($domain))) {
            return $domain;
        }

        return null;
    }

    public function effectiveDomainVerificationStatus(): string
    {
        if (! $this->requiresDomainVerification()) {
            return self::DOMAIN_VERIFICATION_ACTIVE;
        }

        $status = trim((string) $this->domain_verification_status);

        return $status !== '' ? $status : self::DOMAIN_VERIFICATION_PENDING;
    }

    public function customDomainIsReady(): bool
    {
        return $this->isCustomDomainVerified();
    }

    public function isCustomDomainVerified(): bool
    {
        return $this->requiresDomainVerification()
            && $this->effectiveDomainVerificationStatus() === self::DOMAIN_VERIFICATION_ACTIVE;
    }

    public function activeSiteHost(): ?string
    {
        if ($this->customDomainIsReady()) {
            return $this->customDomainHost();
        }

        $fallback = $this->fallbackSiteHost();

        if ($fallback !== null) {
            return $fallback;
        }

        if (! $this->requiresDomainVerification()) {
            return $this->normalizedDomainName();
        }

        return null;
    }

    public function activeSiteUrl(?string $scheme = null): ?string
    {
        $host = $this->activeSiteHost();

        return $host !== null ? tenant_url($host, $scheme) : null;
    }

    public function logDomainBindingMismatchIfNeeded(): void
    {
        if (! $this->domain_id) {
            return;
        }

        $subscriptionDomain = $this->normalizedDomainName();

        if ($subscriptionDomain === null) {
            return;
        }

        static $checked = [];

        $cacheKey = implode(':', [
            (string) ($this->getKey() ?? 'new'),
            (string) $this->domain_id,
            $subscriptionDomain,
        ]);

        if (isset($checked[$cacheKey])) {
            return;
        }

        $checked[$cacheKey] = true;

        $domain = $this->relationLoaded('domain')
            ? $this->getRelation('domain')
            : $this->domain()->select(['id', 'domain_name'])->first();

        if (! $domain instanceof Domain) {
            return;
        }

        $linkedDomain = trim(strtolower((string) $domain->domain_name), ". \t\n\r\0\x0B");

        if ($linkedDomain === '' || $linkedDomain === $subscriptionDomain) {
            return;
        }

        Log::warning('Subscription domain binding mismatch detected', [
            'subscription_id' => $this->getKey(),
            'domain_id' => $this->domain_id,
            'subscription_domain_name' => $subscriptionDomain,
            'linked_domain_name' => $linkedDomain,
        ]);
    }
}
