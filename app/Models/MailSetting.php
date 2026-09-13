<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed SMTP mail configuration (Mail Settings module — Phase 1).
 *
 * Single-row configuration, following the same encrypted-secret pattern as
 * PaymentGateway: `password` is cast `'encrypted'` (Laravel's native
 * AES-256-CBC cast, keyed off APP_KEY) and listed in $hidden so it is never
 * serialized to JSON/array output. This model only persists/decrypts the
 * row — runtime resolution and the config/.env fallback behavior live in
 * App\Support\Mail\MailSettingsManager, not here.
 *
 * @property int         $id
 * @property bool        $enabled
 * @property string      $mailer
 * @property string|null $host
 * @property int|null    $port
 * @property string|null $username
 * @property string|null $password      Decrypted on access; never serialized.
 * @property string|null $encryption    Maps to mail.mailers.smtp.scheme ('smtp'|'smtps'|null).
 * @property string|null $from_address
 * @property string|null $from_name
 */
class MailSetting extends Model
{
    protected $table = 'mail_settings';

    protected $fillable = [
        'enabled',
        'mailer',
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'from_address',
        'from_name',
    ];

    protected $casts = [
        'enabled'  => 'boolean',
        'port'     => 'integer',
        'password' => 'encrypted',
    ];

    /**
     * Never expose the SMTP password in serialized output (logs, API
     * responses, array/JSON casts, etc.) — same guarantee as
     * PaymentGateway::$hidden for secret_key/webhook_secret.
     */
    protected $hidden = [
        'password',
    ];
}
