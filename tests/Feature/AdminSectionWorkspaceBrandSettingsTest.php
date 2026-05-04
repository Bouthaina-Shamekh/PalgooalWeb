<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SectionController;
use App\Models\Page;
use App\Models\Tenancy\Subscription;
use Tests\TestCase;

class AdminSectionWorkspaceBrandSettingsTest extends TestCase
{
    public function test_resolves_canonical_tenant_subscription_for_admin_workspace_brand_settings(): void
    {
        $subscription = $this->makeSubscription(10);
        $page = $this->makePage([
            'context' => 'tenant',
            'tenant_id' => $subscription->id,
        ]);
        $page->setRelation('tenant', $subscription);

        $this->assertSame($subscription, $this->controller()->resolve($page));
    }

    public function test_resolves_legacy_subscription_link_for_admin_workspace_brand_settings(): void
    {
        $subscription = $this->makeSubscription(11);
        $page = $this->makePage([
            'context' => 'tenant',
            'subscription_id' => $subscription->id,
        ]);
        $page->setRelation('subscription', $subscription);

        $this->assertSame($subscription, $this->controller()->resolve($page));
    }

    public function test_marketing_pages_do_not_get_brand_settings_context(): void
    {
        $page = $this->makePage([
            'context' => 'marketing',
            'subscription_id' => null,
            'tenant_id' => null,
        ]);

        $this->assertNull($this->controller()->resolve($page));
    }

    protected function controller(): object
    {
        return new class extends SectionController
        {
            public function resolve(Page $page): ?Subscription
            {
                return $this->resolveActiveThemeSubscription($page);
            }
        };
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function makePage(array $attributes): Page
    {
        $page = new Page(array_merge([
            'context' => 'marketing',
            'subscription_id' => null,
            'tenant_id' => null,
            'is_active' => true,
            'is_home' => false,
        ], $attributes));
        $page->id = 1;
        $page->setRelation('tenant', null);
        $page->setRelation('subscription', null);

        return $page;
    }

    protected function makeSubscription(int $id): Subscription
    {
        $subscription = new Subscription([
            'status' => 'active',
            'theme_settings' => [
                'brand_name' => 'Tenant Brand',
            ],
        ]);
        $subscription->id = $id;

        return $subscription;
    }
}
