<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MailSetting;
use App\Support\Mail\MailSettingsManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

/**
 * Admin Mail Settings — Phase 1 (SMTP only) + Phase 2 (Send Test Email).
 *
 * Single-row configuration (like GeneralSetting), with the same
 * "blank password = keep existing encrypted credential" update convention
 * as PaymentGatewayController::update().
 *
 * Routes:
 *   GET       /admin/settings/mail       -> edit()
 *   PUT|PATCH /admin/settings/mail       -> update()
 *   POST      /admin/settings/mail/test  -> sendTest()
 */
class MailSettingsController extends Controller
{
    /**
     * Show the mail settings form. Creates an in-memory (unsaved) default
     * row when none exists yet, so the form always has a model to bind to
     * — mirrors the GeneralSetting singleton-row convention. Nothing is
     * persisted until the admin actually submits the form.
     */
    public function edit(): View
    {
        return view('dashboard.settings.mail.edit', [
            'mailSetting' => $this->firstRowOrNewDefault(),
        ]);
    }

    /**
     * Fetch the single mail_settings row, or a fresh in-memory (unsaved)
     * default when none exists yet -- including when the table itself has
     * not been migrated yet. A missing table is treated exactly like "no
     * settings configured yet" so this page keeps rendering instead of
     * 500ing; it never creates the table or runs migrations.
     */
    private function firstRowOrNewDefault(): MailSetting
    {
        try {
            $mailSetting = Schema::hasTable('mail_settings')
                ? MailSetting::query()->first()
                : null;
        } catch (Throwable) {
            $mailSetting = null;
        }

        return $mailSetting ?? new MailSetting([
            'enabled' => false,
            'mailer'  => 'smtp',
        ]);
    }

    /**
     * Update the mail settings row and invalidate the cached runtime
     * config snapshot so the next request picks up the change.
     *
     * The password field is only overwritten when the admin actually typed
     * a new value — an empty submission means "keep the existing encrypted
     * credential", identical to PaymentGatewayController::update(). This is
     * also why the password is never bound back into `old()`/the view: a
     * masked placeholder is shown instead (see the edit view).
     */
    public function update(Request $request, MailSettingsManager $mailSettingsManager): RedirectResponse
    {
        $data = $request->validate([
            'enabled'      => 'nullable|boolean',
            'mailer'       => 'required|string|in:smtp',
            'host'         => 'nullable|string|max:255',
            'port'         => 'nullable|integer|min:1|max:65535',
            'username'     => 'nullable|string|max:255',
            'password'     => 'nullable|string|max:500',
            // Symfony Mailer's EsmtpTransportFactory (used by Laravel 12's
            // MailManager::createSmtpTransport()) only ever supports the
            // 'smtp' (STARTTLS) and 'smtps' (implicit TLS) schemes — no
            // other "encryption" value is meaningful here.
            'encryption'   => 'nullable|string|in:smtp,smtps',
            'from_address' => 'nullable|email|max:255',
            'from_name'    => 'nullable|string|max:255',
        ]);

        $mailSetting = MailSetting::query()->first() ?? new MailSetting();

        $payload = [
            'enabled'      => (bool) ($data['enabled'] ?? false),
            'mailer'       => $data['mailer'],
            'host'         => $data['host'] ?? null,
            'port'         => $data['port'] ?? null,
            'username'     => $data['username'] ?? null,
            'encryption'   => $data['encryption'] ?? null,
            'from_address' => $data['from_address'] ?? null,
            'from_name'    => $data['from_name'] ?? null,
        ];

        // Only overwrite the password if the admin actually typed a new
        // value; blank submission keeps the existing encrypted credential.
        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $mailSetting->fill($payload)->save();

        $mailSettingsManager->forgetCache();

        return redirect()
            ->route('dashboard.settings.mail.edit')
            ->with('ok', t('dashboard.Mail_Settings_Updated', 'تم حفظ إعدادات البريد.'));
    }

    /**
     * Send a one-off test email using ONLY the already-saved MailSetting /
     * runtime mail config -- never SMTP credentials from this request.
     *
     * This never persists anything: the recipient address is used once and
     * discarded, and MailSetting is never written to here.
     *
     * Runtime mailer freshness: Laravel's MailManager caches each resolved
     * mailer/transport instance in $this->mailers[$name] once built
     * (see MailManager::mailer()/get()/resolve() in this project's
     * vendor/laravel/framework/src/Illuminate/Mail/MailManager.php). If an
     * "smtp" mailer had already been resolved earlier in this process with
     * different config, a later config() change alone would NOT rebuild
     * it -- Mail::send() would keep using the already-built transport. So
     * after re-applying the freshest DB settings, we explicitly call
     * Mail::purge() (MailManager::purge(), the framework's own method for
     * this) to discard any cached "smtp" mailer instance before sending,
     * guaranteeing the transport is rebuilt from the config we just set.
     */
    public function sendTest(Request $request, MailSettingsManager $mailSettingsManager): RedirectResponse
    {
        $data = $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        $reason = $mailSettingsManager->sendableOrReason();

        if ($reason !== null) {
            return redirect()
                ->route('dashboard.settings.mail.edit')
                ->with('mail_test_error', $reason);
        }

        // Reload + reapply fresh so the test always reflects exactly what
        // is saved right now rather than a cached snapshot, then purge any
        // already-resolved mailer instance so it rebuilds from this config.
        $mailSettingsManager->forgetCache();
        $mailSettingsManager->applyRuntimeConfig();
        Mail::purge(config('mail.default'));

        // Read once, kept only in memory for redacting the exception
        // message below if the send fails -- never logged in full, never
        // returned to the browser, never persisted.
        $password = MailSetting::query()->first()?->password;

        try {
            Mail::raw(
                'This is a test email sent from the Palgoals mail settings panel.',
                function ($message) use ($data) {
                    $message->to($data['test_email'])->subject('Palgoals Mail Test');
                }
            );
        } catch (Throwable $e) {
            $safeMessage = $e->getMessage();

            if (! empty($password)) {
                $safeMessage = str_replace($password, '[redacted]', $safeMessage);
            }

            Log::error('Mail settings test email failed to send.', [
                'exception' => get_class($e),
                'message'   => $safeMessage,
            ]);

            return redirect()
                ->route('dashboard.settings.mail.edit')
                ->with('mail_test_error', 'Test email could not be sent. Please verify the SMTP settings.');
        }

        return redirect()
            ->route('dashboard.settings.mail.edit')
            ->with('mail_test_ok', 'Test email sent successfully.');
    }
}
