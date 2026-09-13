<?php

namespace Tests\Feature;

use App\Models\MailSetting;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Mail Settings module — Phase 2 (Send Test Email).
 *
 * Covers the POST /admin/settings/mail/test action:
 *  - authorized admin can trigger a test send; a non-admin cannot (same
 *    `can:access-dashboard` gate as the rest of this module)
 *  - an invalid recipient is rejected by normal Laravel validation
 *  - disabled / missing / incomplete saved settings are rejected with a
 *    safe, specific message instead of attempting to send
 *  - the saved SMTP password is never present in any response the browser
 *    sees, on either the success or failure path
 *  - a real transport failure (unreachable host) is caught, logged
 *    server-side, and surfaced only as the generic safe message -- no
 *    stack trace, no SMTP response body, no secret
 *  - the action never writes to the mail_settings row
 *
 * "Send Test Email" only ever uses the already-saved MailSetting/runtime
 * config; it never accepts SMTP credentials from the test request itself.
 */
class MailSettingsSendTestEmailTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    private function admin(): User
    {
        return User::factory()->create(['super_admin' => true]);
    }

    private function nonAdmin(): User
    {
        return User::factory()->create(['super_admin' => false]);
    }

    private function enabledMailSetting(array $overrides = []): MailSetting
    {
        return MailSetting::query()->create(array_merge([
            'enabled'      => true,
            'mailer'       => 'smtp',
            'host'         => 'smtp.example.com',
            'port'         => 587,
            'username'     => 'no-reply@example.com',
            'password'     => 'Sup3rSecret!',
            'encryption'   => 'smtp',
            'from_address' => 'no-reply@example.com',
            'from_name'    => 'Palgoals',
        ], $overrides));
    }

    public function test_authorized_admin_can_send_a_test_email(): void
    {
        Mail::fake();
        $this->enabledMailSetting();

        $response = $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com']);

        $response->assertRedirect(route('dashboard.settings.mail.edit'));
        $response->assertSessionHas('mail_test_ok', 'Test email sent successfully.');
        $response->assertSessionMissing('mail_test_error');
    }

    public function test_unauthorized_user_cannot_invoke_test_send(): void
    {
        Mail::fake();
        $this->enabledMailSetting();

        $this->actingAs($this->nonAdmin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com'])
            ->assertForbidden();
    }

    public function test_invalid_recipient_is_rejected(): void
    {
        Mail::fake();
        $this->enabledMailSetting();

        $response = $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'not-an-email']);

        $response->assertSessionHasErrors('test_email');
        $response->assertSessionMissing('mail_test_ok');
    }

    public function test_disabled_mail_settings_are_rejected(): void
    {
        Mail::fake();
        $this->enabledMailSetting(['enabled' => false]);

        $response = $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com']);

        $response->assertSessionHas('mail_test_error', 'Mail sending is currently disabled in Mail Settings.');
    }

    public function test_missing_or_incomplete_mail_settings_are_rejected_safely(): void
    {
        Mail::fake();

        // No row at all.
        $response = $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com']);
        $response->assertSessionHas('mail_test_error', 'No mail settings have been saved yet.');

        // Enabled but no host/port.
        $mailSetting = $this->enabledMailSetting(['host' => null, 'port' => null]);
        $response = $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com']);
        $response->assertSessionHas(
            'mail_test_error',
            'SMTP host and port must be configured before sending a test email.'
        );

        // Host/port present, but no from address.
        $mailSetting->update(['host' => 'smtp.example.com', 'port' => 587, 'from_address' => null]);
        $response = $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com']);
        $response->assertSessionHas(
            'mail_test_error',
            'A From Address must be configured before sending a test email.'
        );

        // Username set, but no password saved.
        $mailSetting->update(['from_address' => 'no-reply@example.com', 'username' => 'someone', 'password' => null]);
        $response = $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com']);
        $response->assertSessionHas(
            'mail_test_error',
            'An SMTP username is configured but no password has been saved.'
        );
    }

    public function test_saved_password_is_never_exposed_in_the_response(): void
    {
        // Real (non-faked) attempt against an address nothing listens on,
        // so it fails for real -- proving the failure path never leaks the
        // password, not just the success path.
        $this->enabledMailSetting(['host' => '127.0.0.1', 'port' => 1]);

        $response = $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com']);

        $response->assertSessionHas('mail_test_error');
        $response->assertDontSee('Sup3rSecret!');

        $edit = $this->actingAs($this->admin())->get(route('dashboard.settings.mail.edit'));
        $edit->assertOk();
        $edit->assertDontSee('Sup3rSecret!');
    }

    public function test_successful_send_uses_mail_fake_and_does_not_touch_real_transport(): void
    {
        Mail::fake();
        $this->enabledMailSetting();

        $response = $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com']);

        $response->assertSessionHas('mail_test_ok');
        // MailFake::raw() is an intentional no-op (nothing to assertSent on
        // for raw mail), so the meaningful assertion here is behavioral:
        // the controller reported success without ever touching a real
        // SMTP transport, which Mail::fake() guarantees by swapping the
        // whole mail manager before the request runs.
    }

    public function test_transport_exception_returns_safe_error_and_does_not_expose_secret(): void
    {
        // No Mail::fake() here -- a real send attempt against a host/port
        // combination that refuses the connection immediately (nothing
        // listens on 127.0.0.1:1), so Mail::raw() genuinely throws.
        $this->enabledMailSetting(['host' => '127.0.0.1', 'port' => 1]);

        $response = $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com']);

        $response->assertRedirect(route('dashboard.settings.mail.edit'));
        $response->assertSessionHas(
            'mail_test_error',
            'Test email could not be sent. Please verify the SMTP settings.'
        );

        // Assert against the actual flashed value itself (not the redirect
        // response's HTML body, which legitimately contains the app's own
        // "http://127.0.0.1:8001/..." redirect target and would make a
        // body-wide assertDontSee('127.0.0.1') a false positive unrelated
        // to the SMTP transport/exception details this test cares about).
        $flashedError = session('mail_test_error');
        $this->assertStringNotContainsString('Sup3rSecret!', $flashedError);
        $this->assertStringNotContainsString('127.0.0.1', $flashedError);

        $response->assertDontSee('Sup3rSecret!');
    }

    public function test_test_action_does_not_modify_stored_mail_settings(): void
    {
        Mail::fake();
        $mailSetting = $this->enabledMailSetting();
        $before = $mailSetting->refresh()->getAttributes();

        $this->actingAs($this->admin())
            ->post(route('dashboard.settings.mail.test'), ['test_email' => 'someone@example.com']);

        $after = MailSetting::query()->first();
        $this->assertSame($before['host'], $after->getRawOriginal('host'));
        $this->assertSame($before['port'], $after->getRawOriginal('port'));
        $this->assertSame($before['username'], $after->getRawOriginal('username'));
        $this->assertSame($before['password'], $after->getRawOriginal('password'));
        $this->assertSame($before['encryption'], $after->getRawOriginal('encryption'));
        $this->assertSame($before['from_address'], $after->getRawOriginal('from_address'));
        $this->assertSame($before['from_name'], $after->getRawOriginal('from_name'));
        $this->assertSame($before['enabled'], $after->getRawOriginal('enabled'));
        $this->assertSame($before['updated_at'], $after->getRawOriginal('updated_at'));
    }
}
