<?php

namespace Tests\Feature;

use App\Models\MailSetting;
use App\Models\User;
use App\Support\Mail\MailSettingsManager;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Mail Settings module — Phase 1 (SMTP only).
 *
 * Covers:
 *  - admin (super_admin) can view the settings form; a non-admin cannot
 *    view or update it (same `can:access-dashboard` gate as Payment
 *    Settings)
 *  - settings can be saved
 *  - the SMTP password is encrypted at rest (raw column != plaintext,
 *    decrypts back to the original value) and never appears in the
 *    rendered HTML
 *  - a blank password on update keeps the existing encrypted credential
 *    (same convention as PaymentGatewayController::update())
 *  - MailSettingsManager overrides runtime mail config when enabled, and
 *    leaves config/.env completely untouched when disabled/absent
 *  - a missing mail_settings table never breaks resolution (fail-safe)
 *
 * "Send Test Email", queued mail, and provider-specific mailers are out of
 * scope for this phase and are not covered here.
 */
class MailSettingsTest extends TestCase
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

    public function test_authorized_admin_can_view_mail_settings_page(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('dashboard.settings.mail.edit'));

        $response->assertOk();
        $response->assertSee('name="host"', false);
        $response->assertSee('name="password"', false);
    }

    public function test_unauthorized_user_cannot_view_or_update_mail_settings(): void
    {
        $user = $this->nonAdmin();

        $this->actingAs($user)
            ->get(route('dashboard.settings.mail.edit'))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('dashboard.settings.mail.update'), [
                'mailer' => 'smtp',
                'host'   => 'smtp.example.com',
            ])
            ->assertForbidden();

        $this->assertSame(0, MailSetting::query()->count());
    }

    public function test_settings_can_be_saved(): void
    {
        $response = $this->actingAs($this->admin())->put(route('dashboard.settings.mail.update'), [
            'enabled'      => '1',
            'mailer'       => 'smtp',
            'host'         => 'smtp.example.com',
            'port'         => '587',
            'username'     => 'no-reply@example.com',
            'password'     => 'Sup3rSecret!',
            'encryption'   => 'smtp',
            'from_address' => 'no-reply@example.com',
            'from_name'    => 'Palgoals',
        ]);

        $response->assertRedirect(route('dashboard.settings.mail.edit'));
        $response->assertSessionHas('ok');

        $mailSetting = MailSetting::query()->first();
        $this->assertNotNull($mailSetting);
        $this->assertTrue($mailSetting->enabled);
        $this->assertSame('smtp', $mailSetting->mailer);
        $this->assertSame('smtp.example.com', $mailSetting->host);
        $this->assertSame(587, $mailSetting->port);
        $this->assertSame('no-reply@example.com', $mailSetting->username);
        $this->assertSame('smtp', $mailSetting->encryption);
        $this->assertSame('no-reply@example.com', $mailSetting->from_address);
        $this->assertSame('Palgoals', $mailSetting->from_name);
    }

    public function test_password_is_encrypted_at_rest(): void
    {
        $this->actingAs($this->admin())->put(route('dashboard.settings.mail.update'), [
            'mailer'   => 'smtp',
            'password' => 'Sup3rSecret!',
        ]);

        $rawPassword = DB::table('mail_settings')->value('password');

        $this->assertNotNull($rawPassword);
        $this->assertNotSame('Sup3rSecret!', $rawPassword);
        $this->assertStringNotContainsString('Sup3rSecret!', $rawPassword);
        $this->assertSame('Sup3rSecret!', Crypt::decryptString($rawPassword));

        // Decrypted transparently via the model's `encrypted` cast.
        $this->assertSame('Sup3rSecret!', MailSetting::query()->first()->password);
    }

    public function test_password_is_not_shown_in_returned_html(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('dashboard.settings.mail.update'), [
            'mailer'   => 'smtp',
            'password' => 'Sup3rSecret!',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard.settings.mail.edit'));

        $response->assertOk();
        $response->assertDontSee('Sup3rSecret!');
        // The "configured" indicator, not the value.
        $response->assertSee(t('dashboard.Configured', 'مُكوَّنة'));
    }

    public function test_blank_password_update_preserves_existing_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('dashboard.settings.mail.update'), [
            'mailer'   => 'smtp',
            'host'     => 'smtp.example.com',
            'password' => 'InitialPass1',
        ]);

        // Submit again with an empty password and a changed host.
        $this->actingAs($admin)->put(route('dashboard.settings.mail.update'), [
            'mailer'   => 'smtp',
            'host'     => 'smtp2.example.com',
            'password' => '',
        ]);

        $mailSetting = MailSetting::query()->first();
        $this->assertSame('smtp2.example.com', $mailSetting->host);
        $this->assertSame('InitialPass1', $mailSetting->password);
    }

    public function test_enabled_mail_settings_override_runtime_mail_config(): void
    {
        MailSetting::query()->create([
            'enabled'      => true,
            'mailer'       => 'smtp',
            'host'         => 'smtp.example.com',
            'port'         => 2525,
            'username'     => 'no-reply@example.com',
            'password'     => 'Sup3rSecret!',
            'encryption'   => 'smtps',
            'from_address' => 'no-reply@example.com',
            'from_name'    => 'Palgoals',
        ]);

        $manager = app(MailSettingsManager::class);
        $manager->forgetCache(); // discard the "no row yet" snapshot cached at boot
        $manager->applyRuntimeConfig();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.example.com', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('no-reply@example.com', config('mail.mailers.smtp.username'));
        $this->assertSame('Sup3rSecret!', config('mail.mailers.smtp.password'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
        $this->assertSame('no-reply@example.com', config('mail.from.address'));
        $this->assertSame('Palgoals', config('mail.from.name'));
    }

    public function test_disabled_or_absent_mail_settings_fall_back_to_config(): void
    {
        $defaultMailer = config('mail.default');
        $defaultHost   = config('mail.mailers.smtp.host');

        // No row at all.
        app(MailSettingsManager::class)->applyRuntimeConfig();
        $this->assertSame($defaultMailer, config('mail.default'));
        $this->assertSame($defaultHost, config('mail.mailers.smtp.host'));

        // A row exists but is disabled.
        MailSetting::query()->create([
            'enabled' => false,
            'mailer'  => 'smtp',
            'host'    => 'smtp.example.com',
        ]);

        $manager = app(MailSettingsManager::class);
        $manager->forgetCache();
        $manager->applyRuntimeConfig();

        $this->assertSame($defaultMailer, config('mail.default'));
        $this->assertSame($defaultHost, config('mail.mailers.smtp.host'));
    }

    public function test_missing_mail_settings_table_does_not_break_application_boot(): void
    {
        Schema::drop('mail_settings');

        $manager = app(MailSettingsManager::class);
        $manager->forgetCache();

        // Must not throw despite the table being gone.
        $manager->applyRuntimeConfig();

        $this->assertFalse(Schema::hasTable('mail_settings'));
        $this->assertSame('array', config('mail.default'));

        // A normal request still boots and responds instead of 500ing --
        // deliberately the mail settings page itself (rather than an
        // unrelated dashboard route) so this assertion stays scoped to
        // this module's own failure mode.
        $response = $this->actingAs($this->admin())->get(route('dashboard.settings.mail.edit'));
        $response->assertStatus(200);
    }
}
