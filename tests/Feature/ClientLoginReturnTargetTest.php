<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * TLD-3F.2A — Client Login Return Target.
 *
 * Covers the fix to the successful `client/login` POST branch in routes/client.php: it now
 * resolves its redirect target via the same $resolveClientRedirectTarget($request) closure
 * already used by the failure branch and by client/logout, instead of the narrower inline
 * check that only ever accepted a value starting with "/" (and therefore silently rejected the
 * absolute checkout URL / url.intended URL this app actually sends as redirect_to).
 *
 * These tests exercise the real POST route (no actingAs()) so that Auth::attempt(), session
 * regeneration, and the redirect-target resolution itself are all genuinely tested end to end.
 */
class ClientLoginReturnTargetTest extends TestCase
{
    use DatabaseMigrations;

    private const PASSWORD = 'correct-horse-battery-staple';

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    /* ====================== A — direct login, no redirect_to ====================== */

    public function test_direct_login_with_no_redirect_to_lands_on_client_home(): void
    {
        $client = $this->makeClient();

        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
        ]);

        $response->assertRedirect(route('client.home'));
        $this->assertAuthenticatedAs($client, 'client');
    }

    /* ====================== B — checkout absolute same-host URL ====================== */

    public function test_login_with_same_host_absolute_checkout_url_returns_to_checkout(): void
    {
        $client = $this->makeClient();
        $checkoutUrl = url('/checkout?template_id=0&plan_id=1&plan_sub_type=monthly');

        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
            'redirect_to' => $checkoutUrl,
        ]);

        $response->assertRedirect($checkoutUrl);
    }

    /* ====================== C — safe relative internal path ====================== */

    public function test_login_with_safe_relative_path_returns_there(): void
    {
        $client = $this->makeClient();

        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
            'redirect_to' => '/checkout',
        ]);

        $response->assertRedirect('/checkout');
    }

    /* ====================== D — cross-host absolute URL rejected ====================== */

    public function test_login_with_cross_host_redirect_to_falls_back_to_client_home(): void
    {
        $client = $this->makeClient();

        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
            'redirect_to' => 'https://evil.example/checkout',
        ]);

        $response->assertRedirect(route('client.home'));
    }

    /* ====================== E — protocol-relative URL rejected ====================== */

    public function test_login_with_protocol_relative_redirect_to_falls_back_to_client_home(): void
    {
        $client = $this->makeClient();

        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
            'redirect_to' => '//evil.example/checkout',
        ]);

        $response->assertRedirect(route('client.home'));
    }

    /* ====================== F — CRLF-containing target rejected ====================== */

    public function test_login_with_crlf_in_redirect_to_falls_back_to_client_home(): void
    {
        $client = $this->makeClient();

        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
            'redirect_to' => "/checkout\r\nSet-Cookie: evil=1",
        ]);

        $response->assertRedirect(route('client.home'));
    }

    /* ====================== G/H/I — denylisted path prefixes rejected ====================== */

    public function test_login_with_assets_path_redirect_to_falls_back_to_client_home(): void
    {
        $client = $this->makeClient();

        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
            'redirect_to' => '/assets/app.css',
        ]);

        $response->assertRedirect(route('client.home'));
    }

    public function test_login_with_build_path_redirect_to_falls_back_to_client_home(): void
    {
        $client = $this->makeClient();

        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
            'redirect_to' => '/build/app.js',
        ]);

        $response->assertRedirect(route('client.home'));
    }

    public function test_login_with_storage_path_redirect_to_falls_back_to_client_home(): void
    {
        $client = $this->makeClient();

        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
            'redirect_to' => '/storage/avatars/x.png',
        ]);

        $response->assertRedirect(route('client.home'));
    }

    /* ====================== J — cart session survives a real login ====================== */

    public function test_cart_session_data_survives_a_real_login_post_and_session_regeneration(): void
    {
        $client = $this->makeClient();

        $this->withSession([
            'palgoals_cart_domains' => [[
                'domain' => 'survives-login.com',
                'item_option' => 'register',
                'price_cents' => 1234,
                'currency' => 'USD',
            ]],
        ]);

        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
            'redirect_to' => url('/checkout'),
        ]);

        $response->assertRedirect(url('/checkout'));
        $this->assertAuthenticatedAs($client, 'client');
        $this->assertSame(
            'survives-login.com',
            session('palgoals_cart_domains.0.domain')
        );
    }

    /* ====================== K — real guest-middleware intended-URL round trip ====================== */

    public function test_guest_redirected_from_a_protected_page_returns_there_after_login(): void
    {
        $client = $this->makeClient();

        // 1) Guest hits a protected client route directly.
        $guestAttempt = $this->get(route('client.domains.search'));
        $guestAttempt->assertRedirect(route('client.login'));

        // 2) The login page renders, with Laravel's own url.intended fed into redirect_to —
        //    read the actual rendered value rather than fabricating it.
        $loginPage = $this->get(route('client.login'));
        $loginPage->assertOk();

        $matched = preg_match(
            '/name="redirect_to"\s+value="([^"]*)"/s',
            $loginPage->getContent(),
            $m
        );
        $this->assertSame(1, $matched, 'Could not find the redirect_to hidden field on the login page.');
        $intendedUrl = html_entity_decode($m[1]);
        $this->assertSame(route('client.domains.search'), $intendedUrl);

        // 3) Submit the real login POST carrying that real intended value forward.
        $response = $this->post(route('client.login.store'), [
            'email' => $client->email,
            'password' => self::PASSWORD,
            'redirect_to' => $intendedUrl,
        ]);

        $response->assertRedirect(route('client.domains.search'));
    }

    /* ====================== Helpers ====================== */

    private function makeClient(): Client
    {
        return Client::query()->create([
            'first_name' => 'Login',
            'last_name' => 'Return',
            'email' => uniqid('login_return_', true) . '@example.test',
            'password' => bcrypt(self::PASSWORD),
            'company_name' => 'Login Return Target Test',
            'can_login' => true,
        ]);
    }
}
