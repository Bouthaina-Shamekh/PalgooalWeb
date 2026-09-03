<?php

namespace Tests\Feature;

use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * TLD-3A.3 — Manual Sale Price Persistence Fix.
 *
 * Locks in the fixes for the two confirmed root causes from the TLD-3A.2 audit:
 *
 * P1a — DomainTldController::saveAll()'s form (#saveAllForm) used to have a per-row delete
 * <form> nested inside it (one per visible TLD row). Per the HTML5 parsing spec, a nested <form>
 * start tag is ignored and its matching end tag instead closes the *outer* form early, so
 * #saveAllForm and its "حفظ الكتالوج وأسعار البيع" submit button ended up outside any real form
 * element in the rendered DOM — clicking Save silently submitted nothing. Fixed by moving each
 * row's delete <form> out of #saveAllForm entirely (rendered independently, `hidden`), with the
 * visible delete <button> inside the table row referencing it via `form="row-delete-{id}"`.
 *
 * P1b — saveAll() decided "clear the sale" by comparing $data['sale'] === '' , but Laravel's
 * default ConvertEmptyStringsToNull middleware already turns a submitted "" into null before the
 * controller runs, so that comparison never matched and (float) null == 0.0 was persisted instead
 * of NULL. Fixed by treating null and '' identically as "clear it", and by skipping (not
 * touching) any row whose `sale` sub-key is absent entirely.
 *
 * Tests A-H exercise saveAll() directly over HTTP (matching the real <form method="post"> field
 * names: items[{price_id}][id] / items[{price_id}][sale]) — this is deliberately NOT a unit test
 * of the controller method, so it goes through the full middleware stack (ConvertEmptyStringsToNull
 * included), the same way the real form submission does.
 *
 * Tests I/J/K assert the *rendered* admin HTML's actual DOM structure using PHP's built-in
 * DOMDocument/DOMXPath (ext-dom, already part of any standard PHP install — no new package), since
 * a textual <form> tag count cannot distinguish "balanced" from "nested" (this is exactly what let
 * P1a go unnoticed through every prior static review in this project).
 */
class DomainTldSaveAllTest extends TestCase
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->artisan('migrate:fresh');
        $this->app[Kernel::class]->setArtisan(null);
    }

    // ====================== A-D: sale persistence + cost untouched ======================

    public function test_a_existing_sale_is_updated_to_a_new_value(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider();
        $tld = $this->makeTld($provider, 'com');
        $price = $this->makePrice($tld, 'register', 1, sale: 16.20, cost: 9.00);

        $this->actingAs($admin)->post(route('dashboard.domain_tlds.save-all'), [
            'provider_id' => $provider->id,
            'visible_ids' => [$tld->id],
            'items' => [
                $price->id => ['id' => $price->id, 'sale' => '19.99'],
            ],
        ])->assertRedirect();

        $fresh = $price->fresh();
        $this->assertEquals(19.99, (float) $fresh->sale);
        $this->assertEquals(9.00, (float) $fresh->cost); // D — cost untouched
    }

    public function test_b_null_sale_is_set_to_a_new_value(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider();
        $tld = $this->makeTld($provider, 'com');
        $price = $this->makePrice($tld, 'register', 1, sale: null, cost: 9.00);

        $this->actingAs($admin)->post(route('dashboard.domain_tlds.save-all'), [
            'provider_id' => $provider->id,
            'visible_ids' => [$tld->id],
            'items' => [
                $price->id => ['id' => $price->id, 'sale' => '19.99'],
            ],
        ])->assertRedirect();

        $fresh = $price->fresh();
        $this->assertEquals(19.99, (float) $fresh->sale);
        $this->assertEquals(9.00, (float) $fresh->cost); // D — cost untouched
    }

    public function test_c_submitting_empty_sale_persists_null_not_zero(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider();
        $tld = $this->makeTld($provider, 'com');
        $price = $this->makePrice($tld, 'register', 1, sale: 19.99, cost: 9.00);

        $this->actingAs($admin)->post(route('dashboard.domain_tlds.save-all'), [
            'provider_id' => $provider->id,
            'visible_ids' => [$tld->id],
            'items' => [
                $price->id => ['id' => $price->id, 'sale' => ''],
            ],
        ])->assertRedirect();

        $fresh = $price->fresh();
        // The exact P1b regression: this used to persist as 0.00, not NULL.
        $this->assertNull($fresh->sale);
        $this->assertEquals(9.00, (float) $fresh->cost); // D — cost untouched
    }

    // ====================== E: rows absent from the request are left alone ======================

    public function test_e_a_price_row_not_present_in_the_request_items_is_left_unchanged(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider();
        $tld = $this->makeTld($provider, 'com');
        $included = $this->makePrice($tld, 'register', 1, sale: 10.00, cost: 5.00);
        $omittedEntirely = $this->makePrice($tld, 'renew', 1, sale: 12.00, cost: 6.00);
        $idOnlyNoSaleKey = $this->makePrice($tld, 'transfer', 1, sale: 14.00, cost: 7.00);

        $this->actingAs($admin)->post(route('dashboard.domain_tlds.save-all'), [
            'provider_id' => $provider->id,
            'visible_ids' => [$tld->id],
            'items' => [
                $included->id => ['id' => $included->id, 'sale' => '99.99'],
                // 'sale' sub-key deliberately absent — must not be touched (P1b's
                // "field not submitted" branch).
                $idOnlyNoSaleKey->id => ['id' => $idOnlyNoSaleKey->id],
                // $omittedEntirely's price id does not appear in items[] at all.
            ],
        ])->assertRedirect();

        $this->assertEquals(99.99, (float) $included->fresh()->sale);
        $this->assertEquals(12.00, (float) $omittedEntirely->fresh()->sale);
        $this->assertEquals(14.00, (float) $idOnlyNoSaleKey->fresh()->sale);
    }

    // ====================== F: another provider's row is unaffected ======================

    public function test_f_a_different_providers_row_is_unaffected(): void
    {
        $admin = $this->makeAdmin();
        $providerA = $this->makeProvider('namecheap');
        $providerB = $this->makeProvider('enom');
        $tldA = $this->makeTld($providerA, 'com');
        $tldB = $this->makeTld($providerB, 'com');
        $priceA = $this->makePrice($tldA, 'register', 1, sale: 10.00, cost: 5.00);
        $priceB = $this->makePrice($tldB, 'register', 1, sale: 20.00, cost: 8.00);

        $this->actingAs($admin)->post(route('dashboard.domain_tlds.save-all'), [
            'provider_id' => $providerA->id,
            'visible_ids' => [$tldA->id],
            'items' => [
                $priceA->id => ['id' => $priceA->id, 'sale' => '11.00'],
            ],
        ])->assertRedirect();

        $this->assertEquals(11.00, (float) $priceA->fresh()->sale);
        $this->assertEquals(20.00, (float) $priceB->fresh()->sale);
    }

    // ====================== G: Register/Renew/Transfer ids never mix up ======================

    public function test_g_register_renew_transfer_price_ids_are_not_mixed_up(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider();
        $tld = $this->makeTld($provider, 'com');
        $reg = $this->makePrice($tld, 'register', 1, sale: 10.00, cost: 5.00);
        $ren = $this->makePrice($tld, 'renew', 1, sale: 12.00, cost: 6.00);
        $tra = $this->makePrice($tld, 'transfer', 1, sale: 14.00, cost: 7.00);

        $this->actingAs($admin)->post(route('dashboard.domain_tlds.save-all'), [
            'provider_id' => $provider->id,
            'visible_ids' => [$tld->id],
            'items' => [
                $reg->id => ['id' => $reg->id, 'sale' => '111.00'],
                $ren->id => ['id' => $ren->id, 'sale' => '222.00'],
                $tra->id => ['id' => $tra->id, 'sale' => '333.00'],
            ],
        ])->assertRedirect();

        $this->assertEquals(111.00, (float) $reg->fresh()->sale);
        $this->assertEquals(222.00, (float) $ren->fresh()->sale);
        $this->assertEquals(333.00, (float) $tra->fresh()->sale);
    }

    // ====================== H: save-all route contract is unchanged ======================

    public function test_h_save_all_route_contract_is_unchanged(): void
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider();
        $tld = $this->makeTld($provider, 'com', inCatalog: false);
        $price = $this->makePrice($tld, 'register', 1, sale: 10.00, cost: 5.00);

        $response = $this->actingAs($admin)->post(route('dashboard.domain_tlds.save-all'), [
            'provider_id' => $provider->id,
            'visible_ids' => [$tld->id],
            'catalog' => [$tld->id => '1'],
            'items' => [
                $price->id => ['id' => $price->id, 'sale' => '15.00'],
            ],
        ]);

        $response->assertRedirect(route('dashboard.domain_tlds.index', ['provider_id' => $provider->id]));
        $response->assertSessionHas('ok', 'تم حفظ الكتالوج وأسعار البيع لهذه الصفحة.');
        $this->assertTrue((bool) $tld->fresh()->in_catalog);
    }

    // ====================== I/J/K: rendered admin HTML — real DOM structure ======================

    public function test_i_rendered_admin_html_has_no_form_nested_inside_save_all_form(): void
    {
        [$admin, , $tld1, $tld2] = $this->seedTwoRowsForRendering();

        $xpath = $this->renderedDomainTldsXPath($admin);

        $saveAllForm = $xpath->query('//form[@id="saveAllForm"]')->item(0);
        $this->assertNotNull($saveAllForm, 'saveAllForm must exist in rendered HTML.');

        $nestedForms = $xpath->query('.//form', $saveAllForm);
        $this->assertSame(0, $nestedForms->length, 'No <form> may be nested inside #saveAllForm.');
    }

    public function test_j_save_button_is_actually_inside_save_all_form(): void
    {
        [$admin] = $this->seedTwoRowsForRendering();

        $xpath = $this->renderedDomainTldsXPath($admin);

        $saveAllForm = $xpath->query('//form[@id="saveAllForm"]')->item(0);
        $this->assertNotNull($saveAllForm);

        $submitButtons = $xpath->query(
            './/button[@type="submit" and contains(., "حفظ الكتالوج وأسعار البيع")]',
            $saveAllForm
        );
        $this->assertGreaterThan(
            0,
            $submitButtons->length,
            'The "حفظ الكتالوج وأسعار البيع" button must be a real descendant of #saveAllForm.'
        );
    }

    public function test_k_row_delete_buttons_reference_correct_independent_delete_forms(): void
    {
        [$admin, $provider, $tld1, $tld2] = $this->seedTwoRowsForRendering();

        $xpath = $this->renderedDomainTldsXPath($admin);
        $saveAllForm = $xpath->query('//form[@id="saveAllForm"]')->item(0);
        $this->assertNotNull($saveAllForm);

        foreach ([$tld1, $tld2] as $tld) {
            // The visible delete button lives inside the table (inside #saveAllForm) but is a
            // plain <button>, not a <form> — it targets its form purely via the form="" attribute.
            $deleteButtons = $xpath->query(".//button[@form='row-delete-{$tld->id}']", $saveAllForm);
            $this->assertSame(
                1,
                $deleteButtons->length,
                "Delete button for tld {$tld->id} must exist inside the table, referencing its external form."
            );

            // The matching delete <form> must exist as a TOP-LEVEL form — not nested inside
            // #saveAllForm — and must carry the exact same route/CSRF/DELETE semantics as before.
            $deleteForm = $xpath->query("//form[@id='row-delete-{$tld->id}']")->item(0);
            $this->assertNotNull($deleteForm, "row-delete-{$tld->id} form must exist in the rendered HTML.");

            $isNestedInSaveAllForm = $xpath->query(".//form[@id='row-delete-{$tld->id}']", $saveAllForm)->length > 0;
            $this->assertFalse($isNestedInSaveAllForm, "row-delete-{$tld->id} must NOT be nested inside #saveAllForm.");

            $this->assertSame(
                route('dashboard.domain_tlds.destroy', $tld),
                $deleteForm->getAttribute('action'),
                'Delete form action must be the unchanged destroy route.'
            );

            $methodInput = $xpath->query(".//input[@name='_method']", $deleteForm)->item(0);
            $this->assertNotNull($methodInput, 'DELETE method-spoofing input must be present.');
            $this->assertSame('DELETE', $methodInput->getAttribute('value'));

            $csrfInput = $xpath->query(".//input[@name='_token']", $deleteForm)->item(0);
            $this->assertNotNull($csrfInput, 'CSRF token input must be present.');
        }
    }

    /* ====================== Helpers ====================== */

    /** @return array{0: User, 1: DomainProvider, 2: DomainTld, 3: DomainTld} */
    private function seedTwoRowsForRendering(): array
    {
        $admin = $this->makeAdmin();
        $provider = $this->makeProvider();
        $tld1 = $this->makeTld($provider, 'com');
        $this->makePrice($tld1, 'register', 1, sale: 10.00, cost: 5.00);
        $tld2 = $this->makeTld($provider, 'net');
        $this->makePrice($tld2, 'register', 1, sale: 12.00, cost: 6.00);

        return [$admin, $provider, $tld1, $tld2];
    }

    private function renderedDomainTldsXPath(User $admin): DOMXPath
    {
        $response = $this->actingAs($admin)->get(route('dashboard.domain_tlds.index'));
        $response->assertOk();
        $html = $response->getContent();

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function makeAdmin(): User
    {
        return User::factory()->create(['super_admin' => true]);
    }

    private function makeProvider(string $type = 'namecheap'): DomainProvider
    {
        return DomainProvider::query()->create([
            'name' => strtoupper($type) . ' ' . uniqid(),
            'type' => $type,
            'mode' => 'test',
            'endpoint' => 'https://' . $type . '.example.test',
            'username' => 'test-user',
            'password' => 'test-password',
            'api_key' => 'test-key',
            'client_ip' => '127.0.0.1',
            'is_active' => true,
        ]);
    }

    private function makeTld(DomainProvider $provider, string $tld, bool $inCatalog = false): DomainTld
    {
        return DomainTld::query()->create([
            'provider_id' => $provider->id,
            'provider' => $provider->type,
            'tld' => $tld,
            'currency' => 'USD',
            'enabled' => true,
            'in_catalog' => $inCatalog,
        ]);
    }

    private function makePrice(
        DomainTld $tld,
        string $action,
        int $years,
        ?float $sale,
        ?float $cost
    ): DomainTldPrice {
        return $tld->prices()->create([
            'action' => $action,
            'years' => $years,
            'sale' => $sale,
            'cost' => $cost,
        ]);
    }
}
