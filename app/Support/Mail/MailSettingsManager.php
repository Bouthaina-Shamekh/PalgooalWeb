<?php

namespace App\Support\Mail;

use App\Models\MailSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Resolves admin-managed SMTP mail settings and, when enabled, overrides
 * Laravel's runtime `mail` config so Mail::/notification mail uses them.
 *
 * Mirrors App\Payments\PaymentManager's DB-first / config-fallback shape:
 *
 *   1. mail_settings row exists, table is migrated, and enabled = true
 *      -> override mail.default, mail.mailers.smtp.*, mail.from.*
 *   2. Anything else (table not migrated yet, no row, enabled = false,
 *      or any DB error) -> leave config/mail.php / .env completely
 *      untouched. This is the "safe fallback" the module is built around.
 *
 * Security note on caching: the non-secret settings (enabled flag, host,
 * port, username, encryption, from_*, and whether a password is set) are
 * cached, but the DECRYPTED password itself is deliberately never written
 * into the cache store. Caching it would mean a plaintext copy of the SMTP
 * password living in whatever cache backend is configured (file/database/
 * redis), which is a weaker guarantee than the `encrypted` column it comes
 * from. Instead, on the (comparatively rare) request where mail is actually
 * enabled, the password is re-read directly from the model — one cheap
 * single-row query — every time it's needed. This is the same reason
 * PaymentManager never caches payment_gateways.secret_key.
 */
class MailSettingsManager
{
    public const CACHE_KEY = 'mail_settings.runtime_config';

    public const CACHE_TTL = 3600;

    /**
     * Apply DB-configured mail settings to the runtime config, if enabled.
     *
     * Safe to call unconditionally (e.g. on every boot): it is a no-op
     * whenever the table doesn't exist yet, no row exists, or the row is
     * disabled, and it never throws — any DB/table problem is swallowed so
     * this can never break a normal request or artisan boot.
     */
    public function applyRuntimeConfig(): void
    {
        $settings = $this->cachedNonSecretSettings();

        if ($settings === null) {
            return;
        }

        config([
            'mail.default'                 => $settings['mailer'],
            'mail.mailers.smtp.transport'  => 'smtp',
            'mail.mailers.smtp.host'       => $settings['host'],
            'mail.mailers.smtp.port'       => $settings['port'],
            'mail.mailers.smtp.username'   => $settings['username'],
            'mail.mailers.smtp.password'   => $settings['has_password'] ? $this->freshPassword() : null,
            'mail.mailers.smtp.scheme'     => $settings['encryption'] ?: null,
            'mail.from.address'            => $settings['from_address'],
            'mail.from.name'               => $settings['from_name'],
        ]);
    }

    /**
     * Clear the cached settings snapshot. Must be called after the admin
     * saves the mail settings form so the next request picks up the change
     * instead of the stale cached snapshot.
     */
    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Whether the saved DB mail settings are currently ready to actually
     * send mail (used by the "Send Test Email" action, Phase 2). Returns
     * null when sendable, or a short, safe, admin-facing reason why not --
     * never includes the host/port/username/password values themselves.
     *
     * Always reads the row fresh (bypassing the cached runtime-config
     * snapshot), since this check must reflect exactly what is saved right
     * now, not a snapshot that may be up to CACHE_TTL seconds stale.
     */
    public function sendableOrReason(): ?string
    {
        $row = $this->firstRowOrNull();

        if ($row === null) {
            return 'No mail settings have been saved yet.';
        }

        if (! $row->enabled) {
            return 'Mail sending is currently disabled in Mail Settings.';
        }

        if (blank($row->host) || blank($row->port)) {
            return 'SMTP host and port must be configured before sending a test email.';
        }

        if (blank($row->from_address)) {
            return 'A From Address must be configured before sending a test email.';
        }

        if (filled($row->username) && blank($row->getRawOriginal('password'))) {
            return 'An SMTP username is configured but no password has been saved.';
        }

        return null;
    }

    /**
     * Read + cache a plain-array, secret-free snapshot of the current
     * settings row. Returns null when overriding runtime config should not
     * happen for any reason (no table, no row, disabled row, DB error).
     *
     * @return array{mailer:string, host:?string, port:?int, username:?string,
     *   has_password:bool, encryption:?string, from_address:?string,
     *   from_name:?string}|null
     */
    protected function cachedNonSecretSettings(): ?array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $row = $this->firstRowOrNull();

            if ($row === null || ! $row->enabled) {
                return null;
            }

            return [
                'mailer'       => (string) $row->mailer,
                'host'         => $row->host,
                'port'         => $row->port,
                'username'     => $row->username,
                'has_password' => filled($row->getRawOriginal('password')),
                'encryption'   => $row->encryption,
                'from_address' => $row->from_address,
                'from_name'    => $row->from_name,
            ];
        });
    }

    /**
     * Re-read the decrypted password directly from the database. Never
     * cached — see the class docblock.
     */
    protected function freshPassword(): ?string
    {
        return $this->firstRowOrNull()?->password;
    }

    /**
     * Fetch the single mail_settings row, or null on missing table, no
     * row, or any DB error. Centralizes the fail-safe try/catch so both
     * the cached lookup and the fresh password re-read behave identically.
     */
    protected function firstRowOrNull(): ?MailSetting
    {
        try {
            if (! Schema::hasTable('mail_settings')) {
                return null;
            }

            return MailSetting::query()->first();
        } catch (Throwable) {
            // Table not migrated yet, connection down, etc. -- never break
            // a normal request or application boot over this.
            return null;
        }
    }
}
