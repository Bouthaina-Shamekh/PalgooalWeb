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
 * وإصلاح route الحذف الجماعي (bulk-destroy).
 * TLD-2B: يضيف تغطية لـ sync_summary المُهيكل (success/warning/error)، فشل جزئي/كامل
 * للمزوّد، silent skips في Namecheap كـ issues، حد أقصى للقائمة الصريحة، وفلاتر index()
 * (q/enabled/in_catalog). لا اتصال حقيقي بأي مزوّد في هذا الملف:
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
    // TLD-2B — Structured Sync Summary + Filters + Safe Provider Failure
    // =====================================================================

    // -------------------------------------------------------------------
    // 1. مزامنة ناجحة بالكامل تنتج summary بحالة success
    // -------------------------------------------------------------------
    public function test_full_successful_sync_produces_success_summary(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();
        $this->fakeNamecheapPricing();

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id, 'tlds' => 'com,net'])
            ->assertRedirect();

        $summary = session('sync_summary');
        $this->assertNotNull($summary, 'sync_summary يجب أن يُخزَّن في الجلسة بعد نجاح sync().');
        $this->assertSame('success', $summary['status']);
        $this->assertSame(0, $summary['issues_count']);
        $this->assertSame([], $summary['issues']);
        $this->assertSame('namecheap', $summary['provider']['type']);
    }

    // -------------------------------------------------------------------
    // 2. الـsummary يميّز requested_tlds عن price_rows_added/updated
    // -------------------------------------------------------------------
    public function test_summary_distinguishes_requested_tlds_from_price_row_counters(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();
        $this->fakeNamecheapPricing();

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id, 'tlds' => 'com,net,org'])
            ->assertRedirect();

        $summary = session('sync_summary');
        $this->assertSame(3, $summary['requested_tlds'], 'requested_tlds يمثّل عدد TLDs، وليس عدد صفوف الأسعار.');
        $this->assertSame(9, $summary['price_rows_added'], '3 TLDs × 3 أفعال (register/renew/transfer) = 9 صفوف سعر.');
        $this->assertNotSame($summary['requested_tlds'], $summary['price_rows_added'], 'يجب ألا يُعرض العدّادان كأنهما نفس الشيء.');
    }

    // -------------------------------------------------------------------
    // 3. فشل جزئي في بعض TLDs/actions ينتج summary بحالة warning
    // -------------------------------------------------------------------
    public function test_partial_provider_failure_produces_warning_summary(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();
        $this->fakeNamecheapPricingWithProviderErrorFor(['net']);

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id, 'tlds' => 'com,net'])
            ->assertRedirect();

        $summary = session('sync_summary');
        $this->assertSame('warning', $summary['status'], 'نجاح جزئي + issues يجب أن يُصنَّف warning، وليس success أخضر غير مشروط.');
        $this->assertGreaterThan(0, $summary['price_rows_added']);
        $this->assertGreaterThan(0, $summary['issues_count']);
    }

    // -------------------------------------------------------------------
    // 4. فشل كامل/انقطاع اتصال بالمزوّد لا يُسقط الطلب بخطأ 500 خام
    // -------------------------------------------------------------------
    public function test_total_provider_transport_failure_does_not_return_raw_500(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeEnomProvider();
        $this->fakeEnomClientThrowing();

        $response = $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id, 'tlds' => 'com,net']);

        $response->assertStatus(302);

        $summary = session('sync_summary');
        $this->assertSame('error', $summary['status']);
        $this->assertSame(0, $summary['price_rows_added']);
        $this->assertSame(0, $summary['price_rows_updated']);
        $this->assertGreaterThan(0, $summary['issues_count'], 'فشل النقل الكامل يجب أن يظهر كـ issues مُهيكلة، لا يُخفى بصمت.');
    }

    // -------------------------------------------------------------------
    // 5. issues مُهيكلة (tld/action/reason)، وليست نص مُلصَق
    // -------------------------------------------------------------------
    public function test_issues_are_structured_with_tld_action_reason(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();
        $this->fakeNamecheapPricingWithProviderErrorFor(['net']);

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id, 'tlds' => 'com,net'])
            ->assertRedirect();

        $summary = session('sync_summary');
        $this->assertNotEmpty($summary['issues']);
        foreach ($summary['issues'] as $issue) {
            $this->assertIsArray($issue);
            $this->assertArrayHasKey('tld', $issue);
            $this->assertArrayHasKey('action', $issue);
            $this->assertArrayHasKey('reason', $issue);
        }
        $netIssues = array_filter($summary['issues'], fn ($i) => $i['tld'] === 'net');
        $this->assertNotEmpty($netIssues, 'يجب أن تظهر مشكلة محدَّدة لـ TLD net.');
    }

    // -------------------------------------------------------------------
    // 6. silent skip السابق في Namecheap ("empty products") يظهر الآن كـ issue
    // -------------------------------------------------------------------
    public function test_namecheap_empty_products_silent_skip_is_represented_as_issue(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();
        $this->fakeNamecheapPricingWithEmptyProductsFor(['net']);

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id, 'tlds' => 'com,net'])
            ->assertRedirect();

        $summary = session('sync_summary');
        $emptyProductIssues = array_filter(
            $summary['issues'],
            fn ($i) => $i['tld'] === 'net' && str_contains((string) $i['reason'], 'no_products_returned')
        );
        $this->assertNotEmpty($emptyProductIssues, '"empty products" كانت تمر بصمت — يجب أن تظهر الآن كـ issue.');
    }

    // -------------------------------------------------------------------
    // 9. فلتر q (بحث TLD) يعمل
    // -------------------------------------------------------------------
    public function test_q_filter_searches_by_tld(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();
        $this->makeCatalogTld($provider, 'shop');
        $this->makeCatalogTld($provider, 'rocks');

        $this->actingAs($admin)
            ->get(route('dashboard.domain_tlds.index', ['q' => 'sho']))
            ->assertOk()
            ->assertViewHas('rows', function ($rows) {
                return collect($rows->items())->pluck('tld')->all() === ['shop'];
            });
    }

    // -------------------------------------------------------------------
    // 10. فلتر enabled يعمل
    // -------------------------------------------------------------------
    public function test_enabled_filter_shows_only_matching_rows(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();
        $this->makeCatalogTld($provider, 'shop');
        DomainTld::query()->create([
            'provider_id' => $provider->id, 'provider' => $provider->type, 'tld' => 'off',
            'currency' => 'USD', 'enabled' => false, 'in_catalog' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.domain_tlds.index', ['enabled' => '1']))
            ->assertOk()
            ->assertViewHas('rows', function ($rows) {
                return collect($rows->items())->pluck('tld')->all() === ['shop'];
            });
    }

    // -------------------------------------------------------------------
    // 11. فلتر in_catalog يعمل
    // -------------------------------------------------------------------
    public function test_in_catalog_filter_shows_only_matching_rows(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();
        $this->makeCatalogTld($provider, 'shop');
        DomainTld::query()->create([
            'provider_id' => $provider->id, 'provider' => $provider->type, 'tld' => 'out',
            'currency' => 'USD', 'enabled' => true, 'in_catalog' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard.domain_tlds.index', ['in_catalog' => '0']))
            ->assertOk()
            ->assertViewHas('rows', function ($rows) {
                return collect($rows->items())->pluck('tld')->all() === ['out'];
            });
    }

    // -------------------------------------------------------------------
    // 12. الفلاتر تعمل مع provider_id الحالي (تركيب وليس استبدال)
    // -------------------------------------------------------------------
    public function test_filters_combine_with_provider_id(): void
    {
        $admin = $this->makeAdmin();
        $p1 = $this->makeNamecheapProvider();
        $p2 = $this->makeEnomProvider();
        $this->makeCatalogTld($p1, 'shop');
        $this->makeCatalogTld($p2, 'shop');

        $this->actingAs($admin)
            ->get(route('dashboard.domain_tlds.index', ['provider_id' => $p1->id, 'q' => 'sho']))
            ->assertOk()
            ->assertViewHas('rows', function ($rows) use ($p1) {
                $items = collect($rows->items());
                return $items->count() === 1 && (int) $items->first()->provider_id === (int) $p1->id;
            });
    }

    // -------------------------------------------------------------------
    // 13. حد القائمة الصريحة (50) يُطبَّق فعليًا
    // -------------------------------------------------------------------
    public function test_explicit_tld_limit_is_enforced(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeNamecheapProvider();
        $this->fakeNamecheapPricing();

        $many = collect(range(1, 60))->map(fn ($i) => "tld{$i}")->implode(',');

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id, 'tlds' => $many])
            ->assertRedirect();

        $requested = $this->capturedNamecheapProductNames();
        $this->assertCount(50, $requested, 'يجب الاقتصار على أول 50 TLD فقط من القائمة الصريحة.');

        $summary = session('sync_summary');
        $this->assertSame(50, $summary['requested_tlds']);
        $this->assertTrue(
            collect($summary['issues'])->contains(fn ($i) => str_contains((string) ($i['reason'] ?? ''), 'تم تجاهل')),
            'يجب تسجيل عدد TLDs التي تم تجاهلها بسبب الحد الأقصى كـ issue مرئي.'
        );
    }

    // -------------------------------------------------------------------
    // 14. أولوية cold-start (explicit > catalog > bootstrap) تبقى كما هي بعد TLD-2B
    // -------------------------------------------------------------------
    public function test_cold_start_precedence_unchanged_after_tld2b(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeEnomProvider();
        $this->makeCatalogTld($provider, 'net');

        $calledTlds = [];
        $this->fakeEnomClient($calledTlds);

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.sync'), ['provider_id' => $provider->id])
            ->assertRedirect();

        $unique = array_values(array_unique($calledTlds));
        $this->assertSame(['net'], $unique, 'أولوية catalog > bootstrap يجب أن تبقى كما هي بعد TLD-2B.');
    }

    // -------------------------------------------------------------------
    // 15. عقد الحذف الجماعي (bulk destroy) لم يتغيّر — راجع أيضًا الاختبارين H وI أعلاه
    //     (bulkDestroy() لم يُلمَس إطلاقًا في تعديلات TLD-2B).
    // -------------------------------------------------------------------
    public function test_bulk_destroy_contract_unchanged_after_tld2b(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeEnomProvider();
        $tld = $this->makeCatalogTld($provider, 'com');

        $this->actingAs($admin)
            ->post(route('dashboard.domain_tlds.bulk-destroy'), ['delete_ids' => [$tld->id]])
            ->assertStatus(302)
            ->assertSessionHas('ok');

        $this->assertNull(DomainTld::find($tld->id));
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

    /** TLD-2B: بناء XML نجاح موحّد لأي ProductName — يستخدمه فاكات النجاح/الفشل الجزئي أدناه. */
    private function namecheapSuccessXmlFor(string $tldUpper): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>'
            . '<ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">'
            . '<CommandResponse Type="namecheap.users.getPricing"><UserGetPricingResult>'
            . '<ProductType Name="DOMAIN"><ProductCategory Name="register">'
            . '<Product Name="' . $tldUpper . '">'
            . '<Price Duration="1" DurationType="YEAR" Price="10.00" YourPrice="10.00" Currency="USD"/>'
            . '</Product></ProductCategory></ProductType>'
            . '</UserGetPricingResult></CommandResponse></ApiResponse>';
    }

    /**
     * TLD-2B: يموّه Namecheap بحيث تفشل TLDs محدَّدة (Status=ERROR من المزوّد) في كل الأفعال،
     * بينما تنجح البقية بشكل طبيعي — لاختبار partial failure/warning summary.
     */
    private function fakeNamecheapPricingWithProviderErrorFor(array $failingTlds): void
    {
        $failingTlds = array_map('strtolower', $failingTlds);
        Http::fake([
            'fake-namecheap.test/*' => function ($request) use ($failingTlds) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
                $tldUpper = strtoupper((string) ($q['ProductName'] ?? 'XXX'));
                $tldLower = strtolower($tldUpper);

                if (in_array($tldLower, $failingTlds, true)) {
                    $xml = '<?xml version="1.0" encoding="utf-8"?>'
                        . '<ApiResponse Status="ERROR" xmlns="http://api.namecheap.com/xml.response">'
                        . '<Errors><Error Number="1">Simulated provider error</Error></Errors>'
                        . '</ApiResponse>';
                    return Http::response($xml, 200, ['Content-Type' => 'application/xml']);
                }

                return Http::response($this->namecheapSuccessXmlFor($tldUpper), 200, ['Content-Type' => 'application/xml']);
            },
        ]);
    }

    /**
     * TLD-2B: يموّه Namecheap بحيث تعيد TLDs محدَّدة استجابة Status=OK لكن بدون أي Product
     * (المسار الذي كان "empty products" يمر منه بصمت سابقًا) — لاختبار تمثيله كـ issue.
     */
    private function fakeNamecheapPricingWithEmptyProductsFor(array $emptyTlds): void
    {
        $emptyTlds = array_map('strtolower', $emptyTlds);
        Http::fake([
            'fake-namecheap.test/*' => function ($request) use ($emptyTlds) {
                parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $q);
                $tldUpper = strtoupper((string) ($q['ProductName'] ?? 'XXX'));
                $tldLower = strtolower($tldUpper);

                if (in_array($tldLower, $emptyTlds, true)) {
                    $xml = '<?xml version="1.0" encoding="utf-8"?>'
                        . '<ApiResponse Status="OK" xmlns="http://api.namecheap.com/xml.response">'
                        . '<CommandResponse Type="namecheap.users.getPricing"><UserGetPricingResult>'
                        . '<ProductType Name="DOMAIN"><ProductCategory Name="register">'
                        . '</ProductCategory></ProductType>'
                        . '</UserGetPricingResult></CommandResponse></ApiResponse>';
                    return Http::response($xml, 200, ['Content-Type' => 'application/xml']);
                }

                return Http::response($this->namecheapSuccessXmlFor($tldUpper), 200, ['Content-Type' => 'application/xml']);
            },
        ]);
    }

    /**
     * TLD-2B: يموّه EnomClient بحيث يرمي استثناء نقل (transport exception) حقيقي عند أي نداء —
     * لاختبار أن sync() لا تسقط بخطأ 500 خام (DomainTldController::syncFromEnom() يلتقطه محليًا).
     */
    private function fakeEnomClientThrowing(string $message = 'Connection timed out'): void
    {
        $mock = Mockery::mock(EnomClient::class);
        $mock->shouldReceive('getAnyPrice')
            ->andThrow(new \Illuminate\Http\Client\ConnectionException($message));
        $this->app->instance(EnomClient::class, $mock);
    }
}
