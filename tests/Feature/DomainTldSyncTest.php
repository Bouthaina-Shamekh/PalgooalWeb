<?php

namespace Tests\Feature;

use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use App\Models\User;
use App\Services\Domains\Clients\EnomClient;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * TLD-1: يغطي توحيد سلوك cold-start بين Namecheap وEnom (bootstrap list مشتركة)
 * وإصلاح route الحذف الجماعي (bulk-destroy). لا اتصال حقيقي بأي مزوّد في هذا الملف:
 * Namecheap عبر Http::fake()، Enom عبر EnomClient مُموَّه (mock) بالكامل.
 */
class DomainTldSyncTest extends TestCase
{
    use DatabaseMigrations;

    private const BOOTSTRAP_TLDS = ['com', 'net', 'org', 'shop', 'xyz', 'live', 'news', 'rocks', 'ninja'];

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        // لا طلبات شبكة حقيقية مسموحة في أي اختبار داخل هذا الملف.
        Http::preventStrayRequests();
    }

    // -------------------------------------------------------------------
    // A. Namecheap cold start يستخدم bootstrap list الموحّدة
    // -------------------------------------------------------------------
    public function test_namecheap_cold_start_uses_shared_bootstrap_list_instead_of_returning_early(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();

        $this->assertSame(0, DomainTld::where('provider_id', $provider->id)->count());

        $this->fakeNamecheapPricing();

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id])
            ->assertRedirect();

        $requested = $this->capturedNamecheapProductNames();
        sort($requested);
        $expected = self::BOOTSTRAP_TLDS;
        sort($expected);
        $this->assertSame($expected, $requested, 'يجب أن تصل طلبات فعلية لكل TLD في القائمة البذرية المشتركة.');

        $this->assertSame(
            count(self::BOOTSTRAP_TLDS),
            DomainTld::where('provider_id', $provider->id)->count(),
            'يجب أن تُنشأ صفوف DomainTld فعليًا من نتيجة الـ bootstrap، لا صفر كما كان سابقًا.'
        );
    }

    // -------------------------------------------------------------------
    // B. Enom cold start يستخدم نفس bootstrap list
    // -------------------------------------------------------------------
    public function test_enom_cold_start_uses_the_same_shared_bootstrap_list(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeEnomProvider();

        $this->assertSame(0, DomainTld::where('provider_id', $provider->id)->count());

        $calledTlds = [];
        $this->fakeEnomClient($calledTlds);

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id])
            ->assertRedirect();

        $unique = array_values(array_unique($calledTlds));
        sort($unique);
        $expected = self::BOOTSTRAP_TLDS;
        sort($expected);
        $this->assertSame($expected, $unique);

        $this->assertSame(count(self::BOOTSTRAP_TLDS), DomainTld::where('provider_id', $provider->id)->count());
    }

    // -------------------------------------------------------------------
    // C. الصفوف الموجودة في in_catalog لها أولوية على bootstrap
    // -------------------------------------------------------------------
    public function test_existing_in_catalog_rows_take_priority_over_bootstrap(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();

        $this->makeCatalogTld($provider, 'com');
        $this->makeCatalogTld($provider, 'org');

        $this->fakeNamecheapPricing();

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id])
            ->assertRedirect();

        $requested = $this->capturedNamecheapProductNames();
        sort($requested);
        $this->assertSame(['com', 'org'], $requested, 'يجب الاقتصار على TLDs المعلّمة في الكتالوج فقط، بدون bootstrap.');
    }

    // -------------------------------------------------------------------
    // D. القائمة الصريحة في الطلب لها أولوية على الكتالوج/bootstrap
    // -------------------------------------------------------------------
    public function test_explicit_tld_list_takes_priority_over_catalog_and_bootstrap(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();

        // كتالوج يحتوي TLD مختلف تمامًا عمّا سيُطلب صراحةً
        $this->makeCatalogTld($provider, 'shop');

        $this->fakeNamecheapPricing();

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), [
                'provider_id' => $provider->id,
                'tlds' => 'com,net',
            ])
            ->assertRedirect();

        $requested = $this->capturedNamecheapProductNames();
        sort($requested);
        $this->assertSame(['com', 'net'], $requested, 'يجب استخدام القائمة الصريحة فقط، وتجاهل الكتالوج والـ bootstrap.');
    }

    // -------------------------------------------------------------------
    // E. الحفاظ على sale المضبوط يدويًا أثناء المزامنة
    // -------------------------------------------------------------------
    public function test_sync_updates_cost_only_and_preserves_manually_set_sale(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeEnomProvider();

        $tld = DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => 'com',
            'currency' => 'USD',
            'enabled' => true,
            'in_catalog' => true,
        ]);
        $price = $tld->prices()->create([
            'action' => 'register',
            'years' => 1,
            'cost' => 10.00,
            'sale' => 15.00,
        ]);

        $unused = [];
        $this->fakeEnomClient($unused, newPrice: 11.00);

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id])
            ->assertRedirect();

        $price->refresh();
        $this->assertEqualsWithDelta(11.00, (float) $price->cost, 0.001, 'cost يجب أن يتحدّث للقيمة الجديدة من المزوّد.');
        $this->assertEqualsWithDelta(15.00, (float) $price->sale, 0.001, 'sale المضبوط يدويًا يجب ألا يتغيّر أثناء المزامنة.');
    }

    // -------------------------------------------------------------------
    // F. سعر جديد بالكامل يبقى sale = null
    // -------------------------------------------------------------------
    public function test_newly_created_price_has_null_sale(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeEnomProvider();

        $this->makeCatalogTld($provider, 'com');
        $unused = [];
        $this->fakeEnomClient($unused, newPrice: 12.00);

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id])
            ->assertRedirect();

        $tld = DomainTld::where('provider_id', $provider->id)->where('tld', 'com')->firstOrFail();
        $price = DomainTldPrice::where('domain_tld_id', $tld->id)->where('action', 'register')->where('years', 1)->firstOrFail();

        $this->assertEqualsWithDelta(12.00, (float) $price->cost, 0.001);
        $this->assertNull($price->sale);
    }

    // -------------------------------------------------------------------
    // G. عزل المزوّدين: bootstrap لمزوّد لا يمسّ مزوّدًا آخر
    // -------------------------------------------------------------------
    public function test_namecheap_bootstrap_rows_belong_only_to_the_namecheap_provider(): void
    {
        $admin = $this->makeAdmin();
        $namecheap = $this->makeNamecheapProvider();
        $enom = $this->makeEnomProvider();

        $this->fakeNamecheapPricing();

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $namecheap->id])
            ->assertRedirect();

        $this->assertSame(count(self::BOOTSTRAP_TLDS), DomainTld::where('provider_id', $namecheap->id)->count());
        $this->assertSame(0, DomainTld::where('provider_id', $enom->id)->count(), 'لا يجب أن تتأثر صفوف مزوّد آخر بمزامنة Namecheap.');
    }

    // -------------------------------------------------------------------
    // H. route الحذف الجماعي يصل فعليًا لـ bulkDestroy() بدل method-not-found
    // -------------------------------------------------------------------
    public function test_bulk_destroy_route_resolves_to_the_controller_method(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeEnomProvider();
        $tld = $this->makeCatalogTld($provider, 'com');

        $response = $this->actingAs($admin)->post(route('dashboard.domain_tlds.bulk-destroy'), [
            'delete_ids' => [$tld->id],
        ]);

        // فشل الحل (method-not-found) كان سيُعيد 500 Fatal Error، وليس redirect ناجح.
        $response->assertStatus(302);
        $response->assertSessionHas('ok');
    }

    // -------------------------------------------------------------------
    // I. سلوك الحذف الجماعي نفسه (Regression فقط)
    // -------------------------------------------------------------------
    public function test_bulk_destroy_deletes_selected_rows_and_their_prices(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeEnomProvider();
        $tldToDelete = $this->makeCatalogTld($provider, 'com');
        $tldToKeep = $this->makeCatalogTld($provider, 'net');

        $this->actingAs($admin)->post(route('dashboard.domain_tlds.bulk-destroy'), [
            'delete_ids' => [$tldToDelete->id],
        ])->assertStatus(302);

        $this->assertNull(DomainTld::find($tldToDelete->id));
        $this->assertSame(0, DomainTldPrice::where('domain_tld_id', $tldToDelete->id)->count());
        $this->assertNotNull(DomainTld::find($tldToKeep->id), 'الصفوف غير المحددة يجب ألا تُحذف.');
    }

    // =====================================================================
    // Helpers
    // =====================================================================

    private function makeAdmin(): User
    {
        return User::factory()->create(['super_admin' => true]);
    }

    private function makeNamecheapProvider(): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => 'Namecheap Sandbox ' . uniqid(),
            'type' => 'namecheap',
            'mode' => 'test',
            'endpoint' => 'https://fake-namecheap.test/xml.response',
            'username' => 'test-user',
            'api_key' => 'test-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
        ]);
    }

    private function makeEnomProvider(): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => 'Enom ' . uniqid(),
            'type' => 'enom',
            'mode' => 'live',
            'endpoint' => 'https://fake-enom.test/interface.asp',
            'username' => 'test-user',
            'password' => 'test-password',
            'is_active' => true,
        ]);
    }

    private function makeCatalogTld(DomainProvider $provider, string $tld): DomainTld
    {
        return DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => $tld,
            'currency' => 'USD',
            'enabled' => true,
            'in_catalog' => true,
        ]);
    }

    /** يموّه استجابات Namecheap XML لأي ProductName مطلوب، ويعيد سعرًا ثابتًا صالحًا. */
    private function fakeNamecheapPricing(): void
    {
        Http::fake([
            'fake-namecheap.test/*' => function ($request) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
                $tldUpper = strtoupper((string) ($q['ProductName'] ?? 'XXX'));
                $xml = '<?xml version="1.0" encoding="utf-8"?>'
                    . '<ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">'
                    . '<CommandResponse Type="namecheap.users.getPricing"><UserGetPricingResult>'
                    . '<ProductType Name="DOMAIN"><ProductCategory Name="register">'
                    . '<Product Name="' . $tldUpper . '">'
                    . '<Price Duration="1" DurationType="YEAR" Price="10.00" YourPrice="10.00" Currency="USD"/>'
                    . '</Product></ProductCategory></ProductType>'
                    . '</UserGetPricingResult></CommandResponse></ApiResponse>';

                return Http::response($xml, 200, ['Content-Type' => 'application/xml']);
            },
        ]);
    }

    /** يستخرج كل قيم ProductName التي طُلبت فعليًا من الـ Namecheap fake (TLDs بحروف صغيرة، بدون تكرار). */
    private function capturedNamecheapProductNames(): array
    {
        $names = [];
        foreach (Http::recorded() as [$request, $response]) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
            if (isset($q['ProductName'])) {
                $names[] = strtolower((string) $q['ProductName']);
            }
        }
        return array_values(array_unique($names));
    }

    /**
     * يموّه EnomClient بالكامل (لا اتصال حقيقي)، ويعيد سعرًا ثابتًا صالحًا لكل (tld, action).
     * $calledTlds تُمرَّر بالمرجع (by reference) من المستدعي، وتُملأ فعليًا بكل TLD طُلب أثناء sync().
     */
    private function fakeEnomClient(array &$calledTlds, float $newPrice = 10.00): void
    {
        $mock = Mockery::mock(EnomClient::class);
        $mock->shouldReceive('getAnyPrice')
            ->andReturnUsing(function ($provider, $tld, $action, $years) use (&$calledTlds, $newPrice) {
                $calledTlds[] = $tld;
                return ['ok' => true, 'price' => $newPrice, 'currency' => 'USD'];
            });
        $this->app->instance(EnomClient::class, $mock);
    }
}
