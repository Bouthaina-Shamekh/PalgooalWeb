<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed Mail Settings — Phase 1 (SMTP only).
 *
 * Single-row configuration table (like `general_settings`), mirroring the
 * `payment_gateways` encrypted-secret pattern: `password` is stored as
 * `text` (ciphertext is far longer than plaintext) and decrypted
 * transparently by MailSetting's `encrypted` cast. Only ever expected to
 * hold one row for this phase.
 *
 * Provider-specific APIs (SES/Mailgun/Resend) are intentionally NOT
 * modeled here yet — see App\Support\Mail\MailSettingsManager.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();

            // Master switch: when false, runtime config/.env is left
            // completely untouched (see MailSettingsManager).
            $table->boolean('enabled')->default(false);

            // Phase 1 only supports the 'smtp' mailer/transport.
            $table->string('mailer')->default('smtp');

            $table->string('host')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('username')->nullable();

            // Encrypted at rest — see MailSetting::$casts['password'].
            $table->text('password')->nullable();

            // Maps to Laravel 12's mail.mailers.smtp.scheme (Symfony
            // EsmtpTransportFactory only supports 'smtp' and 'smtps').
            $table->string('encryption')->nullable();

            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
