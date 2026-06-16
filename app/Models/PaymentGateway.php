<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * ADR-007 Phase 5A — Gateway Configuration Management
 *
 * Stores payment gateway credentials in the database, encrypted at rest.
 * The active gateway is determined by is_active = true.
 * Only one row should be active at a time.
 *
 * Encrypted columns: public_key, secret_key, webhook_secret
 *   - Stored via Laravel Crypt (AES-256-CBC, APP_KEY required)
 *   - Never logged, never exposed in JSON serialization
 *
 * @property int         $id
 * @property string      $name           Human-readable, e.g. "Lahza"
 * @property string      $driver         Machine key, e.g. 'lahza', 'mock'
 * @property bool        $is_active
 * @property string      $mode           'sandbox' | 'live'
 * @property string|null $public_key     Decrypted on access
 * @property string|null $secret_key     Decrypted on access
 * @property string|null $webhook_secret Decrypted on access
 * @property array|null  $settings       JSON extra config
 */
class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'is_active',
        'mode',
        'public_key',
        'secret_key',
        'webhook_secret',
        'settings',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'public_key'     => 'encrypted',
        'secret_key'     => 'encrypted',
        'webhook_secret' => 'encrypted',
        'settings'       => 'array',
    ];

    /**
     * Never expose API keys in serialized output (logs, API responses, etc.)
     */
    protected $hidden = [
        'public_key',
        'secret_key',
        'webhook_secret',
    ];

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Only active gateways.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Whether this gateway is currently active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Whether the gateway is in sandbox / test mode.
     */
    public function isSandbox(): bool
    {
        return $this->mode === 'sandbox';
    }

    /**
     * Whether the gateway is in live / production mode.
     */
    public function isLive(): bool
    {
        return $this->mode === 'live';
    }

    /**
     * Retrieve a value from the JSON settings bag.
     *
     * @param  string $key
     * @param  mixed  $default
     * @return mixed
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    // -------------------------------------------------------------------------
    // Static helpers
    // -------------------------------------------------------------------------

    /**
     * Return the currently active gateway row, or null.
     */
    public static function active(): ?static
    {
        return static::where('is_active', true)->first();
    }
}
