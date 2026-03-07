<?php

use App\Http\Controllers\Admin\MenuController;
use App\Models\Header;
use App\Models\HeaderItem;
use App\Models\HeaderItemTranslation;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

test('menus index auto creates a default menu when none exists', function (): void {
    expect(Header::query()->count())->toBe(0);

    $request = Request::create('/admin/menus', 'GET');
    $view = app(MenuController::class)->index($request);

    expect($view->name())->toBe('dashboard.header');
    $this->assertDatabaseHas('headers', [
        'name' => 'Main Menu',
        'slug' => 'main-menu',
        'location_key' => 'header_primary',
        'is_active' => 1,
    ]);
});

test('menu store creates unique slug for duplicate names', function (): void {
    Header::query()->create([
        'name' => 'Main Menu',
        'slug' => 'main-menu',
        'location_key' => 'header_primary',
        'is_active' => true,
    ]);

    $response = $this->post(route('dashboard.menus.store'), [
        'menu_name' => 'Main Menu',
        'menu_location' => 'footer_primary',
    ]);

    $menu = Header::query()->where('slug', 'main-menu-2')->first();

    expect($menu)->not->toBeNull();
    $response->assertRedirect(route('dashboard.menus', ['menu' => $menu->id]));
    $this->assertDatabaseHas('headers', [
        'id' => $menu->id,
        'name' => 'Main Menu',
        'slug' => 'main-menu-2',
        'location_key' => 'footer_primary',
    ]);
});

test('store page item syncs translations from active languages only', function (): void {
    Language::query()->create([
        'name' => 'English',
        'native' => 'English',
        'code' => 'en',
        'is_rtl' => false,
        'is_active' => true,
    ]);
    Language::query()->create([
        'name' => 'Arabic',
        'native' => 'العربية',
        'code' => 'ar',
        'is_rtl' => true,
        'is_active' => true,
    ]);
    Language::query()->create([
        'name' => 'French',
        'native' => 'Francais',
        'code' => 'fr',
        'is_rtl' => false,
        'is_active' => false,
    ]);

    $menu = Header::query()->create([
        'name' => 'Primary',
        'slug' => 'primary',
        'location_key' => 'header_primary',
        'is_active' => true,
    ]);

    $page = Page::query()->create([
        'context' => 'marketing',
        'is_active' => true,
        'is_home' => false,
    ]);

    PageTranslation::query()->create([
        'page_id' => $page->id,
        'locale' => 'en',
        'slug' => 'home',
        'title' => 'Home',
    ]);
    PageTranslation::query()->create([
        'page_id' => $page->id,
        'locale' => 'ar',
        'slug' => 'al-rayysyyh',
        'title' => 'الرئيسية',
    ]);
    PageTranslation::query()->create([
        'page_id' => $page->id,
        'locale' => 'fr',
        'slug' => 'accueil',
        'title' => 'Accueil',
    ]);

    $response = $this->post(route('dashboard.menus.items.store', $menu), [
        'type' => 'page',
        'order' => 3,
        'page_id' => $page->id,
    ]);

    $item = HeaderItem::query()->where('header_id', $menu->id)->first();

    expect($item)->not->toBeNull();
    expect($item->type)->toBe('page');
    expect((int) $item->page_id)->toBe((int) $page->id);

    $response->assertRedirect(route('dashboard.menus', ['menu' => $menu->id]));
    $this->assertDatabaseHas('header_item_translations', [
        'header_item_id' => $item->id,
        'locale' => 'en',
        'label' => 'Home',
        'url' => '/home',
    ]);
    $this->assertDatabaseHas('header_item_translations', [
        'header_item_id' => $item->id,
        'locale' => 'ar',
        'label' => 'الرئيسية',
        'url' => '/al-rayysyyh',
    ]);
    $this->assertDatabaseMissing('header_item_translations', [
        'header_item_id' => $item->id,
        'locale' => 'fr',
    ]);
});

test('store link item keeps multilingual labels and urls', function (): void {
    Language::query()->create([
        'name' => 'English',
        'native' => 'English',
        'code' => 'en',
        'is_rtl' => false,
        'is_active' => true,
    ]);
    Language::query()->create([
        'name' => 'Arabic',
        'native' => 'العربية',
        'code' => 'ar',
        'is_rtl' => true,
        'is_active' => true,
    ]);

    $menu = Header::query()->create([
        'name' => 'Primary',
        'slug' => 'primary',
        'location_key' => 'header_primary',
        'is_active' => true,
    ]);

    $response = $this->post(route('dashboard.menus.items.store', $menu), [
        'type' => 'link',
        'order' => 2,
        'translations' => [
            'en' => ['label' => 'Hosting', 'url' => '/hosting'],
            'ar' => ['label' => 'الاستضافة', 'url' => '/ar/hosting'],
        ],
    ]);

    $item = HeaderItem::query()->where('header_id', $menu->id)->first();

    expect($item)->not->toBeNull();
    expect($item->type)->toBe('link');

    $response->assertRedirect(route('dashboard.menus', ['menu' => $menu->id]));
    $this->assertDatabaseHas('header_item_translations', [
        'header_item_id' => $item->id,
        'locale' => 'en',
        'label' => 'Hosting',
        'url' => '/hosting',
    ]);
    $this->assertDatabaseHas('header_item_translations', [
        'header_item_id' => $item->id,
        'locale' => 'ar',
        'label' => 'الاستضافة',
        'url' => '/ar/hosting',
    ]);
});

test('reorder items updates order and rejects foreign ids', function (): void {
    $menuA = Header::query()->create([
        'name' => 'Menu A',
        'slug' => 'menu-a',
        'location_key' => 'header_primary',
        'is_active' => true,
    ]);
    $menuB = Header::query()->create([
        'name' => 'Menu B',
        'slug' => 'menu-b',
        'location_key' => 'footer_primary',
        'is_active' => true,
    ]);

    $item1 = HeaderItem::query()->create([
        'header_id' => $menuA->id,
        'type' => 'link',
        'order' => 0,
    ]);
    $item2 = HeaderItem::query()->create([
        'header_id' => $menuA->id,
        'type' => 'link',
        'order' => 1,
    ]);
    $item3 = HeaderItem::query()->create([
        'header_id' => $menuA->id,
        'type' => 'link',
        'order' => 2,
    ]);
    $foreignItem = HeaderItem::query()->create([
        'header_id' => $menuB->id,
        'type' => 'link',
        'order' => 0,
    ]);

    $ok = $this->postJson(route('dashboard.menus.items.reorder', $menuA), [
        'ids' => [$item3->id, $item1->id, $item2->id],
    ]);

    $ok->assertOk()->assertJson(['ok' => true]);
    expect(HeaderItem::query()->find($item3->id)?->order)->toBe(0);
    expect(HeaderItem::query()->find($item1->id)?->order)->toBe(1);
    expect(HeaderItem::query()->find($item2->id)?->order)->toBe(2);

    $bad = $this->postJson(route('dashboard.menus.items.reorder', $menuA), [
        'ids' => [$item1->id, $foreignItem->id, $item2->id],
    ]);

    $bad->assertStatus(422);
});

test('update item aborts when item does not belong to selected menu', function (): void {
    Language::query()->create([
        'name' => 'English',
        'native' => 'English',
        'code' => 'en',
        'is_rtl' => false,
        'is_active' => true,
    ]);

    $menuA = Header::query()->create([
        'name' => 'Menu A',
        'slug' => 'menu-a',
        'location_key' => 'header_primary',
        'is_active' => true,
    ]);
    $menuB = Header::query()->create([
        'name' => 'Menu B',
        'slug' => 'menu-b',
        'location_key' => 'footer_primary',
        'is_active' => true,
    ]);

    $itemInMenuB = HeaderItem::query()->create([
        'header_id' => $menuB->id,
        'type' => 'link',
        'order' => 0,
    ]);

    $response = $this->patch(route('dashboard.menus.items.update', [$menuA, $itemInMenuB]), [
        'type' => 'link',
        'order' => 0,
        'translations' => [
            'en' => ['label' => 'Docs', 'url' => '/docs'],
        ],
    ]);

    $response->assertNotFound();
});
