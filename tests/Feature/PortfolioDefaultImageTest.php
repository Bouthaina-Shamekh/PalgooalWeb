<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PortfolioController;
use App\Models\Language;
use App\Models\Media;
use App\Models\Portfolio;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PortfolioDefaultImageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Dedicated, disposable SQLite connection: never migrate or touch project data.
        config([
            'database.default' => 'portfolio_image_test',
            'database.connections.portfolio_image_test' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'cache.default' => 'array',
            'session.driver' => 'array',
            'app.translation_auto_create' => false,
        ]);
        DB::purge('portfolio_image_test');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->string('file_type')->default('image');
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });
        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->string('default_image')->nullable();
            $table->foreignId('default_image_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->json('images')->nullable();
            $table->date('delivery_date');
            $table->integer('order');
            $table->integer('implementation_period_days')->nullable();
            $table->string('client')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('portfolio_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained('portfolios');
            $table->string('locale');
            foreach (['title', 'type', 'materials', 'link', 'status', 'description'] as $field) {
                $table->text($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('native');
            $table->boolean('is_rtl')->default(false);
            $table->boolean('is_active');
            $table->timestamps();
        });
        Schema::create('translation_values', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('locale');
            $table->text('value')->nullable();
            $table->timestamps();
        });
        Language::create(['code' => 'en', 'native' => 'English', 'is_active' => true]);

        // Render the real form/component without unrelated dashboard settings composers.
        $this->app['events']->forget('composing: *');
        $this->app['view']->share('errors', new ViewErrorBag());
        $this->app['request']->setLaravelSession($this->app['session']->driver());
    }

    public function test_create_submits_media_id_and_saves_both_columns(): void
    {
        $media = Media::create(['file_path' => 'media/new.png']);
        $form = $this->defaultImageField(new Portfolio(), (string) $media->id);
        $this->assertSame((string) $media->id, $form['value']);
        $this->assertSame(asset('storage/'.$media->file_path), $form['preview']);

        $response = $this->controller()->store($this->request($form['value']));
        $this->assertSame(route('dashboard.portfolios.index'), $response->getTargetUrl());
        $saved = Portfolio::sole();
        $this->assertSame($media->id, (int) $saved->default_image_media_id);
        $this->assertSame($media->file_path, $saved->default_image);
    }

    public function test_title_only_edit_keeps_current_media_id_and_path(): void
    {
        $media = Media::create(['file_path' => 'media/current.png']);
        $portfolio = $this->portfolio($media->file_path, $media->id);
        $form = $this->defaultImageField($portfolio);
        $this->assertSame((string) $media->id, $form['value']);
        $this->assertSame(asset('storage/'.$media->file_path), $form['preview']);

        $response = $this->controller()->update($this->request($form['value']), $portfolio->id);
        $this->assertSame(route('dashboard.portfolios.index'), $response->getTargetUrl());
        $portfolio->refresh();
        $this->assertSame($media->id, (int) $portfolio->default_image_media_id);
        $this->assertSame($media->file_path, $portfolio->default_image);
        $this->assertSame('Updated title', $portfolio->translations->sole()->title);
    }

    public function test_unrelated_edit_preserves_an_existing_legacy_status(): void
    {
        $portfolio = $this->portfolio('');
        $portfolio->translations()->sole()->update(['status' => 'Archived legacy']);
        $field = $this->statusField($portfolio->fresh(), 'Archived legacy');
        $this->assertSame('Archived legacy', $field['value']);
        $this->assertStringContainsString('Legacy', $field['label']);
        $request = $this->request(null);
        $translations = $request->input('translations');
        $translations[0]['status'] = 'Archived legacy';
        $request->merge(['translations' => $translations]);

        $this->controller()->update($request, $portfolio->id);

        $this->assertSame('Archived legacy', $portfolio->translations()->sole()->fresh()->status);
    }

    public function test_canonical_status_can_be_preserved_and_intentionally_changed(): void
    {
        $portfolio = $this->portfolio('');
        $portfolio->translations()->sole()->update(['status' => 'Active']);

        foreach (['Active', 'Completed'] as $status) {
            $request = $this->request(null);
            $translations = $request->input('translations');
            $translations[0]['status'] = $status;
            $request->merge(['translations' => $translations]);
            $this->controller()->update($request, $portfolio->id);
            $this->assertSame($status, $portfolio->translations()->sole()->fresh()->status);
        }
    }

    public function test_legacy_status_can_be_replaced_with_a_supported_status(): void
    {
        $portfolio = $this->portfolio('');
        $portfolio->translations()->sole()->update(['status' => 'Archived legacy']);
        $request = $this->request(null);
        $translations = $request->input('translations');
        $translations[0]['status'] = 'Inactive';
        $request->merge(['translations' => $translations]);

        $this->controller()->update($request, $portfolio->id);

        $this->assertSame('Inactive', $portfolio->translations()->sole()->fresh()->status);
    }

    public function test_forged_unsupported_status_is_rejected_on_create_and_update(): void
    {
        $portfolio = $this->portfolio('');
        $portfolio->translations()->sole()->update(['status' => 'Archived legacy']);

        foreach ([null, $portfolio] as $editing) {
            $request = $this->request(null);
            $translations = $request->input('translations');
            $translations[0]['status'] = 'Forged new value';
            $request->merge(['translations' => $translations]);
            try {
                $editing
                    ? $this->controller()->update($request, $editing->id)
                    : $this->controller()->store($request);
                $this->fail('A new unsupported status must fail validation.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('translations.0.status', $exception->errors());
            }
        }
        $this->assertSame('Archived legacy', $portfolio->translations()->sole()->fresh()->status);
    }

    public function test_status_old_input_and_legacy_value_survive_unrelated_validation_failure(): void
    {
        $portfolio = $this->portfolio('');
        $portfolio->translations()->sole()->update(['status' => 'Archived legacy']);
        $request = $this->request(null);
        $translations = $request->input('translations');
        $translations[0]['status'] = 'Archived legacy';
        $request->merge(['translations' => $translations, 'delivery_date' => null]);

        try {
            $this->controller()->update($request, $portfolio->id);
            $this->fail('Expected an unrelated validation error.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('delivery_date', $exception->errors());
            $this->assertArrayNotHasKey('translations.0.status', $exception->errors());
            $this->app[\Illuminate\Contracts\Debug\ExceptionHandler::class]->render($request, $exception);
        }

        $this->assertSame('Archived legacy', old('translations.0.status'));
        $this->assertSame('Archived legacy', $this->statusField($portfolio->fresh(), 'Archived legacy')['value']);
        $this->assertSame('Archived legacy', $portfolio->translations()->sole()->fresh()->status);
    }

    public function test_omitted_status_does_not_clear_a_legacy_value_during_update(): void
    {
        $portfolio = $this->portfolio('');
        $portfolio->translations()->sole()->update(['status' => 'Archived legacy']);

        $this->controller()->update($this->request(null), $portfolio->id);

        $this->assertSame('Archived legacy', $portfolio->translations()->sole()->fresh()->status);
    }

    public function test_supported_status_old_input_is_restored_after_validation_failure(): void
    {
        $portfolio = $this->portfolio('');
        $portfolio->translations()->sole()->update(['status' => 'Active']);
        $request = $this->request(null);
        $translations = $request->input('translations');
        $translations[0]['status'] = 'Completed';
        $request->merge(['translations' => $translations, 'delivery_date' => null]);

        try {
            $this->controller()->update($request, $portfolio->id);
            $this->fail('Expected an unrelated validation error.');
        } catch (ValidationException $exception) {
            $this->app[\Illuminate\Contracts\Debug\ExceptionHandler::class]->render($request, $exception);
        }

        $this->assertSame('Completed', $this->statusField($portfolio->fresh(), 'Active')['value']);
        $this->assertSame('Active', $portfolio->translations()->sole()->fresh()->status);
    }

    public function test_index_uses_semantic_status_styles_and_neutral_legacy_style(): void
    {
        $statuses = ['Active', 'Inactive', 'Completed', 'Archived legacy'];
        foreach ($statuses as $index => $status) {
            $portfolio = Portfolio::create([
                'order' => $index, 'delivery_date' => '2026-09-13', 'slug' => 'status-'.$index,
            ]);
            $portfolio->translations()->create([
                'locale' => 'en', 'title' => 'Portfolio '.$index, 'type' => 'Website', 'status' => $status,
            ]);
        }
        $view = $this->controller()->index(Request::create('/admin/portfolios', 'GET'));
        $data = $view->getData();
        $template = str_replace(['<x-dashboard-layout>', '</x-dashboard-layout>'], '', file_get_contents(resource_path('views/dashboard/portfolios/index.blade.php')));
        $html = \Illuminate\Support\Facades\Blade::render($template, $data);

        $this->assertMatchesRegularExpression('/bg-emerald-50[^>]*>\s*Active/s', $html);
        $this->assertMatchesRegularExpression('/bg-gray-100[^>]*>\s*Inactive/s', $html);
        $this->assertMatchesRegularExpression('/bg-blue-50[^>]*>\s*Completed/s', $html);
        $this->assertMatchesRegularExpression('/border-gray-200[^>]*title="[^"]+"[^>]*>\s*Archived legacy/s', $html);
    }

    #[DataProvider('legacyMediaMatches')]
    public function test_legacy_path_is_previewed_and_preserved_without_backfill(bool $matchingMedia): void
    {
        $path = 'legacy/portfolio.png';
        if ($matchingMedia) {
            Media::create(['file_path' => $path]);
        }
        $portfolio = $this->portfolio($path);
        $form = $this->defaultImageField($portfolio);
        $this->assertSame('', $form['value']);
        $this->assertSame(asset('storage/'.$path), $form['preview']);

        // Empty browser input is converted to null by Laravel's normal middleware.
        $response = $this->controller()->update($this->request(null), $portfolio->id);
        $this->assertSame(route('dashboard.portfolios.index'), $response->getTargetUrl());
        $portfolio->refresh();
        $this->assertNull($portfolio->default_image_media_id);
        $this->assertSame($path, $portfolio->default_image);
        $this->assertSame($path, $portfolio->resolvedDefaultImagePath());
        $this->assertSame('Updated title', $portfolio->translations->sole()->title);
        $this->assertSame(asset('storage/'.$path), $this->defaultImageField($portfolio, '')['preview']);
    }

    public static function legacyMediaMatches(): array
    {
        return ['no matching media' => [false], 'matching media exists' => [true]];
    }

    #[DataProvider('legacyMediaMatches')]
    public function test_replacing_an_existing_or_legacy_image_updates_both_columns(bool $linked): void
    {
        $current = Media::create(['file_path' => 'media/current.png']);
        $replacement = Media::create(['file_path' => 'media/replacement.png']);
        $portfolio = $this->portfolio($current->file_path, $linked ? $current->id : null);
        $form = $this->defaultImageField($portfolio, (string) $replacement->id);
        $this->assertSame((string) $replacement->id, $form['value']);
        $this->assertSame(asset('storage/'.$replacement->file_path), $form['preview']);

        $this->controller()->update($this->request($form['value']), $portfolio->id);
        $portfolio->refresh();
        $this->assertSame($replacement->id, (int) $portfolio->default_image_media_id);
        $this->assertSame($replacement->file_path, $portfolio->default_image);
    }

    public function test_explicit_empty_value_retains_existing_server_clear_behavior_for_linked_image(): void
    {
        $media = Media::create(['file_path' => 'media/current.png']);
        $portfolio = $this->portfolio($media->file_path, $media->id);
        $form = $this->defaultImageField($portfolio, '');
        $this->assertSame('', $form['value']);
        $this->assertNull($form['preview']);

        $this->controller()->update($this->request(null), $portfolio->id);
        $portfolio->refresh();
        $this->assertNull($portfolio->default_image_media_id);
        $this->assertNull($portfolio->default_image);
    }

    public function test_path_input_is_not_rendered_as_an_id_and_is_still_rejected_by_validation(): void
    {
        $portfolio = $this->portfolio('legacy/portfolio.png');
        $form = $this->defaultImageField($portfolio, 'legacy/portfolio.png');
        $this->assertSame('', $form['value']);
        $this->assertSame(asset('storage/legacy/portfolio.png'), $form['preview']);

        try {
            $this->controller()->update($this->request('legacy/portfolio.png'), $portfolio->id);
            $this->fail('Image paths must not pass Media ID validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('default_image', $exception->errors());
        }
        $this->assertSame('legacy/portfolio.png', $portfolio->fresh()->default_image);
    }

    public function test_gallery_create_persists_a_single_json_array_and_reads_an_array(): void
    {
        $this->seedGalleryMedia();
        $this->controller()->store($this->galleryRequest('7,12'));
        $portfolio = Portfolio::sole();
        $this->assertSame('[7,12]', $portfolio->getRawOriginal('images'));
        $this->assertSame([7, 12], $portfolio->images);
        $this->assertSame([asset('storage/media/7.png'), asset('storage/media/12.png')], $portfolio->resolvedGalleryImages());
    }

    public function test_gallery_update_preserves_order_and_single_encoding(): void
    {
        $this->seedGalleryMedia();
        $portfolio = $this->portfolio('');
        $portfolio->update(['images' => [7, 12]]);
        $this->controller()->update($this->galleryRequest('12,7'), $portfolio->id);
        $portfolio->refresh();
        $this->assertSame('[12,7]', $portfolio->getRawOriginal('images'));
        $this->assertSame([12, 7], $portfolio->images);
        $this->assertSame([12, 7], $portfolio->toArray()['images']);
        $this->assertSame([asset('storage/media/12.png'), asset('storage/media/7.png')], $portfolio->resolvedGalleryImages());
        $this->assertSame('array', $portfolio->getCasts()['images']);
    }

    #[DataProvider('emptyGalleryRequests')]
    public function test_empty_gallery_is_a_single_encoded_empty_array_on_create_and_update(?string $input): void
    {
        $this->controller()->store($this->galleryRequest($input));
        $portfolio = Portfolio::sole();
        $this->assertSame('[]', $portfolio->getRawOriginal('images'));
        $this->assertSame([], $portfolio->images);
        $this->assertSame([], $portfolio->resolvedGalleryImages());

        $portfolio->update(['images' => [7, 12]]);
        $this->controller()->update($this->galleryRequest($input), $portfolio->id);
        $portfolio->refresh();
        $this->assertSame('[]', $portfolio->getRawOriginal('images'));
        $this->assertSame([], $portfolio->images);
    }

    public static function emptyGalleryRequests(): array
    {
        return ['null from middleware' => [null], 'empty string' => ['']];
    }

    #[DataProvider('historicalGalleryRows')]
    public function test_historical_gallery_reads_without_rewriting_raw_data(?string $raw, array $expected, array $paths): void
    {
        $this->seedGalleryMedia();
        $portfolio = $this->portfolio('');
        // Seed the raw column directly, bypassing casts to represent historical storage.
        DB::table('portfolios')->where('id', $portfolio->id)->update(['images' => $raw]);
        $portfolio->refresh();
        $this->assertSame($expected, $portfolio->images);
        $this->assertSame($expected, $portfolio->toArray()['images']);
        $this->assertSame(array_map(fn ($path) => asset('storage/'.$path), $paths), $portfolio->resolvedGalleryImages());
        $this->assertSame($raw, DB::table('portfolios')->where('id', $portfolio->id)->value('images'));

        // A request editing unrelated fields must preserve even legacy raw representations.
        $this->controller()->update($this->request(null), $portfolio->id);
        $this->assertSame($raw, $portfolio->fresh()->getRawOriginal('images'));
        $this->assertSame('Updated title', $portfolio->fresh()->translations->sole()->title);
    }

    public static function historicalGalleryRows(): array
    {
        return [
            'correct ID array' => ['[7,12]', [7, 12], ['media/7.png', 'media/12.png']],
            'double ID array' => [json_encode('[12,7]'), [12, 7], ['media/12.png', 'media/7.png']],
            'string IDs' => ['["7","12"]', ['7', '12'], ['media/7.png', 'media/12.png']],
            'legacy paths' => ['["legacy/one.png","legacy/two.png"]', ['legacy/one.png', 'legacy/two.png'], ['legacy/one.png', 'legacy/two.png']],
            'double legacy paths' => [json_encode('["legacy/one.png"]'), ['legacy/one.png'], ['legacy/one.png']],
            'SQL null' => [null, [], []],
            'JSON null' => ['null', [], []],
            'empty text' => ['', [], []],
            'JSON empty string' => ['""', [], []],
            'empty array' => ['[]', [], []],
            'double empty array' => [json_encode('[]'), [], []],
        ];
    }

    #[DataProvider('unexpectedGalleryRows')]
    public function test_unexpected_gallery_data_is_not_silently_coerced_or_overwritten(string $raw): void
    {
        $portfolio = $this->portfolio('');
        DB::table('portfolios')->where('id', $portfolio->id)->update(['images' => $raw]);
        $portfolio->refresh();
        $this->assertNotSame([], $portfolio->images);
        $this->assertTrue($this->galleryField($portfolio)['blocked']);
        $this->controller()->update($this->request(null), $portfolio->id);
        $this->assertSame($raw, $portfolio->fresh()->getRawOriginal('images'));
    }

    public static function unexpectedGalleryRows(): array
    {
        return [
            'malformed JSON' => ['[broken'],
            'mixed IDs and paths' => ['[7,"legacy/one.png"]'],
            'object' => ['{"id":7}'],
            'extra encoding beyond supported historical layer' => [json_encode(json_encode('[7,12]'))],
        ];
    }

    public function test_gallery_persistence_keeps_unique_ids_in_selection_order(): void
    {
        $this->seedGalleryMedia();
        $this->controller()->store($this->galleryRequest('12,7,12'));
        $portfolio = Portfolio::sole();
        $this->assertSame('[12,7]', $portfolio->getRawOriginal('images'));
        $this->assertSame([12, 7], $portfolio->images);
    }

    #[DataProvider('languageValidationErrors')]
    public function test_language_errors_open_first_affected_tab_and_preserve_input(array $invalidLocales, string $activeLocale): void
    {
        Language::create(['code' => 'ar', 'native' => 'العربية', 'is_active' => true]);
        Language::create(['code' => 'fr', 'native' => 'Français', 'is_active' => false]);
        $languages = collect(['ar', 'en', 'fr'])->map(fn ($code) => Language::where('code', $code)->first());
        $translations = $languages->map(fn ($lang) => [
            'locale' => $lang->code,
            'title' => in_array($lang->code, $invalidLocales) ? str_repeat('x', 501) : ($lang->is_active ? 'Entered '.$lang->code : null),
            'type' => $lang->is_active ? 'Website' : null,
            'materials' => $lang->is_active ? 'Laravel' : null,
        ])->all();
        $request = $this->request(null);
        $request->merge(['translations' => $translations]);
        try {
            $this->controller()->store($request);
            $this->assertSame([], $invalidLocales);
        } catch (ValidationException $exception) {
            $this->assertCount(count($invalidLocales), $exception->errors());
            $response = $this->app[\Illuminate\Contracts\Debug\ExceptionHandler::class]->render($request, $exception);
            $this->assertSame(302, $response->getStatusCode());
            $this->app['view']->share('errors', $this->app['session']->driver()->get('errors'));
            $this->assertSame($translations, old('translations'));
        }
        $html = view('dashboard.portfolios._form', [
            'portfolio' => new Portfolio(), 'portfolioTranslations' => [],
            'languages' => $languages, 'typeSuggestions' => [], 'statusSuggestions' => [],
        ])->render();
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        foreach ($languages as $index => $language) {
            $code = $language->code;
            $tab = $xpath->query('//*[@id="lang-tab-'.$code.'"]')->item(0);
            $this->assertSame($code === $activeLocale ? 'true' : 'false', $tab->getAttribute('aria-selected'));
            $this->assertSame(in_array($code, $invalidLocales) ? 'true' : 'false', $tab->getAttribute('data-validation-error'));
            $this->assertSame(in_array($code, $invalidLocales) ? 1 : 0, $xpath->query('//*[@id="lang-tab-'.$code.'"]/*[@role="img"]')->length);
            $panel = $xpath->query('//*[@id="lang-panel-'.$code.'"]')->item(0);
            $this->assertSame($code !== $activeLocale, in_array('hidden', explode(' ', $panel->getAttribute('class'))));
            foreach (['title_', 'type_input_', 'materials_'] as $prefix) {
                $field = $xpath->query('//*[@id="'.$prefix.$code.'"]')->item(0);
                $this->assertSame((bool) $language->is_active, $field->hasAttribute('required'));
            }
            if ($invalidLocales) {
                $this->assertSame($translations[$index]['title'] ?? '', $xpath->query('//*[@id="title_'.$code.'"]')->item(0)->getAttribute('value'));
            }
            $this->assertFalse($xpath->query('//*[@id="link_'.$code.'"]')->item(0)->hasAttribute('required'));
        }
    }

    public static function languageValidationErrors(): array
    {
        return [
            'English error' => [['en'], 'en'],
            'Arabic error' => [['ar'], 'ar'],
            'both errors follow language order' => [['en', 'ar'], 'ar'],
            'valid with empty inactive language' => [[], 'ar'],
        ];
    }

    #[DataProvider('saveFailureContexts')]
    public function test_exceptional_save_restores_safe_input_and_renders_generic_error(bool $editing): void
    {
        $this->seedGalleryMedia();
        $portfolio = $editing ? $this->portfolio('legacy/unchanged.png') : new Portfolio();
        $url = $editing ? route('dashboard.portfolios.edit', $portfolio->id) : route('dashboard.portfolios.create');
        $this->app['session']->driver()->setPreviousUrl($url);
        $request = $this->galleryRequest('12,7');
        $request->merge([
            'default_image' => '7', 'client' => 'Entered client', 'implementation_period_days' => 14,
            '_token' => 'secret-token', '_method' => 'PUT', 'password' => 'secret-password',
        ]);
        $translations = $request->input('translations');
        $translations[0]['description'] = 'Entered description';
        $translations[0]['password'] = 'nested-secret';
        $request->merge(['translations' => $translations]);
        $exception = new \RuntimeException('Private SQL /internal/path exception detail');
        \Illuminate\Support\Facades\Log::spy();
        // Throw after the portfolio write, exercising rollback as well as feedback.
        $dispatcher = \App\Models\PortfolioTranslation::getEventDispatcher();
        \App\Models\PortfolioTranslation::setEventDispatcher(clone $dispatcher);
        try {
            \App\Models\PortfolioTranslation::saving(function () use ($exception) { throw $exception; });
            $response = $editing
                ? $this->controller()->update($request, $portfolio->id)
                : $this->controller()->store($request);
        } finally {
            \App\Models\PortfolioTranslation::setEventDispatcher($dispatcher);
        }
        $this->assertSame($url, $response->getTargetUrl());
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('7', old('default_image'));
        $this->assertSame('12,7', old('images'));
        $this->assertSame('Entered client', old('client'));
        $this->assertSame(14, old('implementation_period_days'));
        $this->assertSame('Updated title', old('translations.0.title'));
        $this->assertSame('Entered description', old('translations.0.description'));
        foreach (['_token', '_method', 'password', 'translations.0.password'] as $key) {
            $this->assertNull(old($key));
        }
        $this->assertFalse(session()->has('errors'));
        $this->assertSame(t('dashboard.Portfolio_Error', 'An error occurred while saving. Please try again.'), session('error'));
        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->once()->withArgs(
            fn ($message, $context) => str_contains($message, $exception->getMessage()) && $context['exception'] === $exception
        );
        $this->assertSame($editing ? 1 : 0, Portfolio::count());
        if ($editing) {
            $this->assertSame('Original title', $portfolio->fresh()->translations->sole()->title);
            $this->assertSame('legacy/unchanged.png', $portfolio->fresh()->default_image);
        }
        $form = $this->galleryField($portfolio);
        $this->assertSame('12,7', $form['value']);
        $this->assertFalse($form['blocked']);
        $html = view('dashboard.portfolios._form', [
            'portfolio' => $portfolio, 'portfolioTranslations' => [],
            'languages' => Language::all(), 'typeSuggestions' => [], 'statusSuggestions' => [],
            'portfolioMedia' => Media::query()->get()->keyBy('id'),
        ])->render();
        $this->assertStringContainsString('id="portfolio-save-error"', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('bg-red-100', $html);
        $this->assertStringContainsString(e(session('error')), $html);
        $this->assertStringNotContainsString($exception->getMessage(), $html);
        $this->assertStringContainsString('value="Entered client"', $html);
        $this->assertStringContainsString(asset('storage/media/7.png'), $html);
        $this->assertMatchesRegularExpression('/id="default_image_picker"[^>]*value="7"/s', $html);
    }

    public static function saveFailureContexts(): array
    {
        return ['create' => [false], 'edit' => [true]];
    }

    public function test_success_has_no_exception_flash_or_alert(): void
    {
        $response = $this->controller()->store($this->request(null));
        $this->assertSame(route('dashboard.portfolios.index'), $response->getTargetUrl());
        $this->assertNotNull(session('ok'));
        $this->assertNull(session('error'));
        $html = view('dashboard.portfolios._form', [
            'portfolio' => new Portfolio(), 'portfolioTranslations' => [],
            'languages' => Language::all(), 'typeSuggestions' => [], 'statusSuggestions' => [],
        ])->render();
        $this->assertStringNotContainsString('portfolio-save-error', $html);
    }

    public function test_media_picker_renders_a_named_focusable_dialog(): void
    {
        $html = view('dashboard.partials.media-picker')->render();
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('aria-labelledby="media-picker-title"', $html);
        $this->assertStringContainsString('id="media-picker-title"', $html);
        $this->assertStringContainsString('tabindex="-1"', $html);
    }

    public function test_language_panels_derive_direction_dynamically_for_any_language_count(): void
    {
        Language::query()->where('code', 'en')->update(['is_rtl' => false]);
        Language::create(['code' => 'fa', 'native' => 'فارسی', 'is_rtl' => true, 'is_active' => true]);
        Language::create(['code' => 'fr', 'native' => 'Français', 'is_rtl' => false, 'is_active' => true]);
        $languages = Language::query()->orderBy('id')->get();

        $html = view('dashboard.portfolios._form', [
            'portfolio' => new Portfolio(), 'portfolioTranslations' => [],
            'languages' => $languages, 'typeSuggestions' => [],
            'statusSuggestions' => ['en' => [], 'fa' => [], 'fr' => []],
        ])->render();
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);

        $this->assertSame(3, $xpath->query('//*[@role="tab"]')->length);
        $this->assertSame(3, $xpath->query('//*[@role="tabpanel"]')->length);
        foreach (['en' => 'ltr', 'fa' => 'rtl', 'fr' => 'ltr'] as $code => $direction) {
            $panel = $xpath->query('//*[@id="lang-panel-'.$code.'"]')->item(0);
            $this->assertSame($code, $panel->getAttribute('lang'));
            $this->assertSame($direction, $panel->getAttribute('dir'));
            $this->assertSame('', $xpath->query('.//*[@id="title_'.$code.'"]', $panel)->item(0)->getAttribute('dir'));
            $this->assertSame('ltr', $xpath->query('.//*[@id="link_'.$code.'"]', $panel)->item(0)->getAttribute('dir'));
        }
    }

    #[DataProvider('dashboardLanguageDirections')]
    public function test_media_picker_uses_dashboard_locale_direction_and_translated_runtime_data(string $locale, bool $isRtl): void
    {
        Language::create(['code' => $locale, 'native' => strtoupper($locale), 'is_rtl' => $isRtl, 'is_active' => true]);
        $title = $locale.' picker title "quoted" <b>text</b>';
        $loadMore = $locale.' load more';
        DB::table('translation_values')->insert([
            ['key' => 'dashboard.Media_Picker_Title', 'locale' => $locale, 'value' => $title],
            ['key' => 'dashboard.Media_Picker_Load_More', 'locale' => $locale, 'value' => $loadMore],
        ]);
        app()->setLocale($locale);

        $html = view('dashboard.partials.media-picker')->render();
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $modal = $xpath->query('//*[@id="media-picker-modal"]')->item(0);

        $this->assertSame($locale, $modal->getAttribute('lang'));
        $this->assertSame($isRtl ? 'rtl' : 'ltr', $modal->getAttribute('dir'));
        $this->assertSame($loadMore, $modal->getAttribute('data-load-more-label'));
        $this->assertSame($title, trim($xpath->query('//*[@id="media-picker-title"]')->item(0)->textContent));
        $this->assertSame(0, $xpath->query('//*[@id="media-picker-title"]//b')->length);

        $contentLanguage = Language::create([
            'code' => $isRtl ? 'es' : 'fa',
            'native' => $isRtl ? 'Español' : 'فارسی',
            'is_rtl' => ! $isRtl,
            'is_active' => true,
        ]);
        $formHtml = view('dashboard.portfolios._form', [
            'portfolio' => new Portfolio(), 'portfolioTranslations' => [],
            'languages' => collect([$contentLanguage]), 'typeSuggestions' => [],
            'statusSuggestions' => [$contentLanguage->code => []],
        ])->render();
        $this->assertStringContainsString(
            'lang="'.$contentLanguage->code.'" dir="'.($isRtl ? 'ltr' : 'rtl').'"',
            preg_replace('/\s+/', ' ', $formHtml)
        );
    }

    public static function dashboardLanguageDirections(): array
    {
        return ['RTL dashboard locale' => ['ur', true], 'LTR dashboard locale' => ['de', false]];
    }

    public function test_shared_dashboard_head_renders_one_zoomable_viewport(): void
    {
        $this->withoutVite();
        $html = view('dashboard.layouts.partials.head', ['settings' => null])->render();
        preg_match_all('/<meta\s+name="viewport"\s+content="([^"]*)"/i', $html, $matches);
        $this->assertSame(['width=device-width, initial-scale=1'], $matches[1]);
        $this->assertDoesNotMatchRegularExpression('/user-scalable|maximum-scale|minimum-scale/i', $html);
    }

    public function test_portfolio_error_relationships_are_unique_and_resolvable(): void
    {
        Language::create(['code' => 'ar', 'native' => 'العربية', 'is_active' => true]);
        $messages = [];
        foreach (['order', 'delivery_date', 'implementation_period_days', 'client', 'default_image', 'images'] as $field) {
            $messages[$field] = 'Invalid '.$field;
        }
        foreach ([0, 1] as $index) {
            foreach (['title', 'type', 'materials', 'link', 'status', 'description'] as $field) {
                $messages['translations.'.$index.'.'.$field] = 'Invalid '.$field;
            }
        }
        foreach ([true, false] as $withErrors) {
            $bag = new ViewErrorBag();
            $bag->put('default', new \Illuminate\Support\MessageBag($withErrors ? $messages : []));
            $this->app['view']->share('errors', $bag);
            $html = view('dashboard.portfolios._form', [
                'portfolio' => new Portfolio(), 'portfolioTranslations' => [],
                'languages' => Language::all(), 'typeSuggestions' => [], 'statusSuggestions' => [],
            ])->render();
            $html .= view('dashboard.partials.media-picker')->render();
            $document = new DOMDocument();
            $previous = libxml_use_internal_errors(true);
            try { $document->loadHTML('<?xml encoding="UTF-8">'.$html); }
            finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
            $xpath = new DOMXPath($document);
            $ids = [];
            foreach ($xpath->query('//*[@id]') as $element) {
                $id = $element->getAttribute('id');
                $this->assertArrayNotHasKey($id, $ids);
                $ids[$id] = true;
            }
            foreach (['aria-describedby', 'aria-controls', 'aria-labelledby'] as $attribute) {
                foreach ($xpath->query('//*[@'.$attribute.']') as $element) {
                    foreach (preg_split('/\s+/', trim($element->getAttribute($attribute))) as $id) {
                        $this->assertArrayHasKey($id, $ids, $attribute.' must reference a rendered ID');
                    }
                }
            }
            $this->assertSame($withErrors ? 18 : 0, $xpath->query('//*[@aria-invalid="true"]')->length);
            $this->assertSame(2, $xpath->query('//*[@role="combobox"][@aria-expanded="false"]')->length);
            $this->assertSame(2, $xpath->query('//*[@role="listbox"]')->length);
            $this->assertSame(5, $xpath->query('//*[@role="status"]')->length);
        }
    }

    public function test_portfolio_index_controls_have_names_independent_of_placeholders(): void
    {
        // Render the actual page content without unrelated dashboard shell dependencies.
        $template = str_replace(['<x-dashboard-layout>', '</x-dashboard-layout>'], '', file_get_contents(resource_path('views/dashboard/portfolios/index.blade.php')));
        $html = \Illuminate\Support\Facades\Blade::render($template, [
            'portfolios' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
            'search' => '', 'perPage' => 10,
        ]);
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try { $document->loadHTML('<?xml encoding="UTF-8">'.$html); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $xpath = new DOMXPath($document);
        $this->assertNotSame('', $xpath->query('//input[@name="search"]')->item(0)->getAttribute('aria-label'));
        $select = $xpath->query('//select[@name="per_page"]')->item(0);
        $this->assertSame('portfolio-per-page', $select->getAttribute('id'));
        $this->assertSame(1, $xpath->query('//label[@for="portfolio-per-page"]')->length);
    }

    public function test_portfolio_delete_confirmation_is_rendered_as_escaped_data_for_each_form(): void
    {
        $message = <<<'TEXT'
Don't delete "Alpha" <img src=x onerror=alert(1)> C:\portfolio\'quoted'
TEXT;
        DB::table('translation_values')->insert([
            'key' => 'dashboard.Confirm_Delete_Portfolio',
            'locale' => app()->getLocale(),
            'value' => $message,
        ]);
        cache()->forget('translation.'.app()->getLocale().'.dashboard.Confirm_Delete_Portfolio');
        $first = $this->portfolio('');
        $second = Portfolio::create([
            'default_image' => null, 'images' => [], 'order' => 1,
            'delivery_date' => '2026-09-13', 'slug' => 'second-portfolio',
        ]);
        $second->translations()->create([
            'locale' => 'en', 'title' => 'Second', 'type' => 'Website', 'materials' => 'Laravel',
        ]);
        $source = file_get_contents(resource_path('views/dashboard/portfolios/index.blade.php'));
        preg_match('/@can\(\'delete\', \$portfolio\)([\s\S]*?)@endcan/', $source, $deleteBlock);
        $this->assertArrayHasKey(1, $deleteBlock);
        $html = collect([$first, $second])
            ->map(fn (Portfolio $portfolio) => \Illuminate\Support\Facades\Blade::render($deleteBlock[1], compact('portfolio')))
            ->implode('');

        $this->assertStringNotContainsString('onsubmit=', $html);
        $this->assertStringNotContainsString("confirm('{{", $html);
        $this->assertStringContainsString('&lt;img', $html);
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try { $document->loadHTML('<?xml encoding="UTF-8">'.$html); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $xpath = new DOMXPath($document);
        $forms = $xpath->query('//form[contains(concat(" ", normalize-space(@class), " "), " portfolio-delete-form ")]');
        $this->assertCount(2, $forms);
        foreach ([$first, $second] as $index => $portfolio) {
            $form = $forms->item($index);
            $this->assertSame($message, $form->getAttribute('data-confirm'));
            $this->assertSame(route('dashboard.portfolios.destroy', $portfolio->id), $form->getAttribute('action'));
            $this->assertSame('POST', $form->getAttribute('method'));
            $this->assertSame('DELETE', $xpath->query('.//input[@name="_method"]', $form)->item(0)->getAttribute('value'));
            $this->assertSame(1, $xpath->query('.//input[@name="_token"]', $form)->length);
            $this->assertSame(1, $xpath->query('.//button[@type="submit"]', $form)->length);
        }
        $this->assertStringContainsString('@csrf', $deleteBlock[1]);
    }

    public function test_explicit_legacy_removal_survives_validation_and_clears_only_when_submitted(): void
    {
        $portfolio = $this->portfolio('legacy/image.png');
        $request = $this->request(null);
        $request->merge(['remove_default_image' => '1', 'delivery_date' => null]);
        try {
            $this->controller()->update($request, $portfolio->id);
            $this->fail('Expected date validation error');
        } catch (ValidationException $exception) {
            $this->app[\Illuminate\Contracts\Debug\ExceptionHandler::class]->render($request, $exception);
        }
        $this->assertSame('1', old('remove_default_image'));
        $this->assertSame('legacy/image.png', $portfolio->fresh()->default_image);
        $html = view('dashboard.portfolios._form', [
            'portfolio' => $portfolio, 'portfolioTranslations' => [],
            'languages' => Language::all(), 'typeSuggestions' => [], 'statusSuggestions' => [],
            'portfolioMedia' => Media::query()->get()->keyBy('id'),
        ])->render();
        $this->assertStringNotContainsString(asset('storage/legacy/image.png'), $html);
        $this->assertStringContainsString('data-remove-input="portfolio-remove-default-image"', $html);
        $request = $this->request(null);
        $request->merge(['remove_default_image' => old('remove_default_image')]);
        $this->controller()->update($request, $portfolio->id);
        $this->assertNull($portfolio->fresh()->default_image);
        $this->assertNull($portfolio->fresh()->default_image_media_id);
    }

    public function test_explicit_empty_gallery_can_replace_legacy_paths_with_single_encoded_empty_array(): void
    {
        $portfolio = $this->portfolio('legacy/default.png');
        $portfolio->update(['images' => ['legacy/one.png', 'legacy/two.png']]);
        $this->controller()->update($this->galleryRequest(''), $portfolio->id);
        $this->assertSame('[]', $portfolio->fresh()->getRawOriginal('images'));
        $this->assertSame([], $portfolio->fresh()->images);
        $this->assertSame('legacy/default.png', $portfolio->fresh()->default_image);
    }

    #[DataProvider('invalidPortfolioMedia')]
    public function test_non_image_or_missing_media_rejects_the_whole_submission(string $field, string $type, bool $editing): void
    {
        $this->seedGalleryMedia();
        if ($type !== 'missing') {
            Media::create(['id' => 19, 'file_path' => 'misleading.png', 'file_type' => $type, 'mime_type' => $type === 'video' ? 'video/mp4' : 'application/pdf']);
            // ID is guarded by the model, so identify the newly created record explicitly.
            $invalidId = Media::where('file_type', $type)->value('id');
        } else {
            $invalidId = 999;
        }
        $portfolio = $editing ? $this->portfolio('legacy/keep.png') : null;
        if ($portfolio) $portfolio->update(['images' => [7, 12]]);
        $request = $this->galleryRequest('7,12');
        $value = $field === 'images' ? '7,12,'.$invalidId : (string) $invalidId;
        $request->merge([$field => $value]);
        try {
            $editing ? $this->controller()->update($request, $portfolio->id) : $this->controller()->store($request);
            $this->fail('Forged invalid media must fail server validation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($field, $exception->errors());
            $this->app[\Illuminate\Contracts\Debug\ExceptionHandler::class]->render($request, $exception);
            $this->assertSame($value, old($field));
            $this->app['view']->share('errors', $this->app['session']->driver()->get('errors'));
        }
        $this->assertSame($editing ? 1 : 0, Portfolio::count());
        if ($portfolio) {
            $this->assertSame([7, 12], $portfolio->fresh()->images);
            $this->assertSame('legacy/keep.png', $portfolio->fresh()->default_image);
        }
        $html = view('dashboard.portfolios._form', [
            'portfolio' => $portfolio ?? new Portfolio(), 'portfolioTranslations' => [],
            'languages' => Language::all(), 'typeSuggestions' => [], 'statusSuggestions' => [],
        ])->render();
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="'.$field.'_picker_error"', $html);
        $this->assertStringContainsString('id="'.$field.'_picker_error"', $html);
        $this->assertSame(2, substr_count($html, 'data-accepted-type="image"'));
    }

    public static function invalidPortfolioMedia(): array
    {
        $cases = [];
        foreach (['default_image', 'images'] as $field) {
            foreach (['missing', 'video', 'document', 'other'] as $type) {
                foreach ([false, true] as $editing) $cases[$field.' '.$type.' '.($editing ? 'update' : 'create')] = [$field, $type, $editing];
            }
        }
        return $cases;
    }

    private function controller(): PortfolioController
    {
        // Image contract tests exercise real validation/persistence, not authorization.
        $controller = Mockery::mock(PortfolioController::class)->makePartial();
        $controller->shouldReceive('authorize')->andReturnNull();

        return $controller;
    }

    #[DataProvider('gallerySelections')]
    public function test_gallery_selection_survives_unrelated_create_validation_failure(string $selection): void
    {
        $this->seedGalleryMedia();
        $this->flashGalleryValidationFailure($selection);
        $this->assertSame($selection, old('images'));
        $form = $this->galleryField(new Portfolio());
        $this->assertSame($selection, $form['value']);
        $this->assertCount(count(explode(',', $selection)), $form['previews']);
        $this->assertFalse($form['blocked']);

        $this->controller()->store($this->galleryRequest($form['value']));
        $this->assertSame(json_encode(array_map('intval', explode(',', $selection))), DB::table('portfolios')->sole()->images);
    }

    public static function gallerySelections(): array
    {
        return ['one image' => ['7'], 'multiple images' => ['7,12'], 'selection order' => ['12,7']];
    }

    public function test_changed_gallery_survives_edit_validation_failure_instead_of_reverting_to_saved_images(): void
    {
        $this->seedGalleryMedia();
        $portfolio = $this->portfolio('');
        $portfolio->update(['images' => [7]]);
        $before = $portfolio->getRawOriginal('images');
        $this->flashGalleryValidationFailure('12,7', $portfolio);

        $form = $this->galleryField($portfolio->fresh());
        $this->assertSame('12,7', $form['value']);
        $this->assertSame([asset('storage/media/12.png'), asset('storage/media/7.png')], $form['previews']);
        $this->assertSame($before, $portfolio->fresh()->getRawOriginal('images'));

        $this->controller()->update($this->galleryRequest($form['value']), $portfolio->id);
        $this->assertSame(json_encode([12, 7]), $portfolio->fresh()->getRawOriginal('images'));
    }

    #[DataProvider('storedGalleryRepresentations')]
    public function test_existing_gallery_loads_without_changing_storage(mixed $stored): void
    {
        $this->seedGalleryMedia();
        $portfolio = $this->portfolio('');
        $portfolio->update(['images' => $stored]);
        $before = $portfolio->getRawOriginal('images');
        $form = $this->galleryField($portfolio->fresh());
        $this->assertSame('12,7', $form['value']);
        $this->assertSame([asset('storage/media/12.png'), asset('storage/media/7.png')], $form['previews']);
        $this->assertFalse($form['blocked']);
        $this->assertSame($before, $portfolio->fresh()->getRawOriginal('images'));
    }

    public static function storedGalleryRepresentations(): array
    {
        return ['array cast' => [[12, 7]], 'existing JSON string after cast' => ['[12,7]']];
    }

    #[DataProvider('compatibleGalleryInput')]
    public function test_compatible_old_gallery_input_normalizes_to_csv(mixed $input, string $expected): void
    {
        $this->seedGalleryMedia();
        $portfolio = $this->portfolio('');
        $portfolio->update(['images' => [12]]);
        $this->app['session']->driver()->flashInput(['images' => $input]);
        $form = $this->galleryField($portfolio);
        $this->assertSame($expected, $form['value']);
        $this->assertFalse($form['blocked']);
    }

    public static function compatibleGalleryInput(): array
    {
        return [
            'single CSV' => ['7', '7'],
            'multiple CSV' => ['7,12', '7,12'],
            'JSON numbers' => ['[7,12]', '7,12'],
            'JSON strings' => ['["7","12"]', '7,12'],
            'PHP integers' => [[7, 12], '7,12'],
            'PHP strings' => [['7', '12'], '7,12'],
            'scalar integer' => [7, '7'],
            'empty string' => ['', ''],
            'null' => [null, ''],
            'empty PHP array' => [[], ''],
            'empty JSON array' => ['[]', ''],
            'whitespace' => ['  ', ''],
            'ordered unique IDs' => ['12,7,12,7', '12,7'],
            'whitespace and leading zeroes' => [' 007, 12,7 ', '7,12'],
            'unresolved ID is retained, not existence-validated here' => ['7,99', '7,99'],
        ];
    }

    #[DataProvider('malformedGalleryInput')]
    public function test_malformed_old_input_preserves_known_saved_gallery(mixed $input): void
    {
        $this->seedGalleryMedia();
        $portfolio = $this->portfolio('');
        $portfolio->update(['images' => [12, 7]]);
        $this->app['session']->driver()->flashInput(['images' => $input]);
        $form = $this->galleryField($portfolio);
        $this->assertSame('12,7', $form['value']);
        $this->assertFalse($form['blocked']);

        $this->controller()->update($this->galleryRequest($form['value']), $portfolio->id);
        // Save receives the known-safe IDs, never an empty selection caused by parsing.
        $this->assertSame(json_encode([12, 7]), $portfolio->fresh()->getRawOriginal('images'));
    }

    public static function malformedGalleryInput(): array
    {
        return [
            'broken JSON' => ['[7,12'],
            'mixed CSV' => ['7,bad,12'],
            'empty CSV token' => ['7,,12'],
            'object JSON' => ['{"id":7}'],
            'nested array' => [[[7], 12]],
            'associative array' => [['id' => 7]],
            'boolean' => [true],
            'float' => [7.5],
            'negative' => ['-7'],
            'zero' => ['0'],
            'overflow' => ['999999999999999999999999999'],
            'markup' => ['<img src=x onerror=alert(1)>'],
        ];
    }

    public function test_unresolvable_saved_gallery_blocks_submission_and_keeps_legacy_previews(): void
    {
        $portfolio = $this->portfolio('');
        $portfolio->update(['images' => ['legacy/one.png', 'legacy/two.png']]);
        $before = $portfolio->getRawOriginal('images');
        $form = $this->galleryField($portfolio);
        $this->assertSame('', $form['value']);
        $this->assertTrue($form['blocked']);
        $this->assertSame([asset('storage/legacy/one.png'), asset('storage/legacy/two.png')], $form['previews']);
        $this->assertSame($before, $portfolio->fresh()->getRawOriginal('images'));

        $this->seedGalleryMedia();
        $this->app['session']->driver()->flashInput(['images' => '7,12']);
        $reselected = $this->galleryField($portfolio);
        $this->assertSame('7,12', $reselected['value']);
        $this->assertFalse($reselected['blocked']);
    }

    public function test_malformed_create_input_has_a_safe_empty_fallback(): void
    {
        $this->app['session']->driver()->flashInput(['images' => '[broken']);
        $form = $this->galleryField(new Portfolio());
        $this->assertSame('', $form['value']);
        $this->assertSame([], $form['previews']);
        $this->assertFalse($form['blocked']);
        $this->assertSame(0, Portfolio::count());
    }

    public function test_gallery_preview_reconstructs_unordered_query_results_in_stored_id_order(): void
    {
        $this->seedOrderedGalleryMedia();
        $requested = [12, 3, 9];
        $this->assertSame([3, 9, 12], Media::whereIn('id', $requested)->get()->pluck('id')->all());

        $portfolio = $this->portfolio('');
        $portfolio->update(['images' => $requested]);

        $expected = array_map(fn ($id) => asset("storage/media/$id.png"), $requested);
        $this->assertSame($expected, $this->galleryField($portfolio)['previews']);
        $this->assertSame($expected, $portfolio->fresh()->resolvedGalleryImages());
    }

    public function test_index_eager_loading_removes_default_media_n_plus_one_queries(): void
    {
        foreach ([3, 9, 12] as $id) {
            $media = Media::create(['id' => $id, 'file_path' => "media/$id.png"]);
            $portfolio = Portfolio::create([
                'default_image_media_id' => $media->id, 'default_image' => $media->file_path,
                'images' => [], 'order' => $id, 'delivery_date' => '2026-09-13', 'slug' => "portfolio-$id",
            ]);
            $portfolio->translations()->create(['locale' => 'en', 'title' => "Portfolio $id"]);
        }

        DB::enableQueryLog();
        DB::flushQueryLog();
        $before = Portfolio::with('translations')->paginate(10);
        foreach ($before as $portfolio) {
            $portfolio->resolvedDefaultImagePath();
        }
        $beforeCount = count(DB::getQueryLog());

        DB::flushQueryLog();
        $after = Portfolio::with(['translations', 'defaultImageMedia'])->paginate(10);
        foreach ($after as $portfolio) {
            $portfolio->resolvedDefaultImagePath();
        }
        $afterCount = count(DB::getQueryLog());

        $this->assertSame(6, $beforeCount);
        $this->assertSame(4, $afterCount);
    }

    public function test_edit_prepares_default_and_gallery_media_in_one_query_and_create_skips_it(): void
    {
        $this->seedGalleryMedia();
        $portfolio = $this->portfolio('media/7.png', 7);
        $portfolio->update(['images' => [12, 7]]);

        DB::enableQueryLog();
        DB::flushQueryLog();
        $editView = $this->controller()->edit($portfolio->id);
        $editQueries = DB::getQueryLog();

        $this->assertCount(5, $editQueries);
        $this->assertSame([7, 12], $editView->getData()['portfolioMedia']->keys()->sort()->values()->all());
        $this->assertCount(1, array_filter($editQueries, fn ($query) => str_contains(strtolower($query['query']), 'from "media"')));

        DB::flushQueryLog();
        $createView = $this->controller()->create();
        $createQueries = DB::getQueryLog();

        $this->assertCount(2, $createQueries);
        $this->assertTrue($createView->getData()['portfolioMedia']->isEmpty());
        $this->assertCount(0, array_filter($createQueries, fn ($query) => str_contains(strtolower($query['query']), 'from "media"')));
    }

    public function test_gallery_preview_preserves_a_different_stored_order(): void
    {
        $this->seedOrderedGalleryMedia();
        $portfolio = $this->portfolio('');
        $portfolio->update(['images' => [9, 12, 3]]);

        $expected = [9, 12, 3];
        $this->assertSame(
            array_map(fn ($id) => asset("storage/media/$id.png"), $expected),
            $this->galleryField($portfolio)['previews']
        );
    }

    public function test_gallery_preview_skips_missing_media_without_reordering_survivors(): void
    {
        DB::table('media')->insert([
            ['id' => 9, 'file_path' => 'media/9.png'],
            ['id' => 12, 'file_path' => 'media/12.png'],
        ]);
        $portfolio = $this->portfolio('');
        $portfolio->update(['images' => [12, 3, 9]]);

        $expected = [asset('storage/media/12.png'), asset('storage/media/9.png')];
        $this->assertSame($expected, $this->galleryField($portfolio)['previews']);
        $this->assertSame($expected, $portfolio->fresh()->resolvedGalleryImages());
    }

    public function test_gallery_preview_deduplicates_by_first_occurrence_and_restores_old_input_order(): void
    {
        $this->seedOrderedGalleryMedia();
        $portfolio = $this->portfolio('');
        $portfolio->update(['images' => [3, 9]]);

        $this->app['session']->driver()->flashInput(['images' => '12,3,12,9']);
        $form = $this->galleryField($portfolio);

        $this->assertSame('12,3,9', $form['value']);
        $this->assertSame([
            asset('storage/media/12.png'),
            asset('storage/media/3.png'),
            asset('storage/media/9.png'),
        ], $form['previews']);
    }

    public function test_create_and_update_success_preserve_whitelisted_list_context(): void
    {
        $create = $this->request(null);
        $create->merge(['return_search' => 'website', 'return_page' => 3, 'return_per_page' => 25]);
        $createdResponse = $this->controller()->store($create);
        $this->assertSame(
            route('dashboard.portfolios.index', ['search' => 'website', 'page' => 3, 'per_page' => 25]),
            $createdResponse->getTargetUrl()
        );

        $portfolio = Portfolio::sole();
        $update = $this->request(null);
        $update->merge(['return_search' => 'website', 'return_page' => 4, 'return_per_page' => 50]);
        $updatedResponse = $this->controller()->update($update, $portfolio->id);
        $this->assertSame(
            route('dashboard.portfolios.index', ['search' => 'website', 'page' => 4, 'per_page' => 50]),
            $updatedResponse->getTargetUrl()
        );
    }

    public function test_arbitrary_return_url_and_unsupported_context_are_not_propagated(): void
    {
        $request = $this->request(null);
        $request->merge(['return_url' => 'https://evil.example', 'unexpected' => 'value']);
        $response = $this->controller()->store($request);

        $this->assertSame(route('dashboard.portfolios.index'), $response->getTargetUrl());
        $this->assertStringNotContainsString('evil.example', $response->getTargetUrl());
    }

    private function seedOrderedGalleryMedia(): void
    {
        DB::table('media')->insert([
            ['id' => 3, 'file_path' => 'media/3.png'],
            ['id' => 9, 'file_path' => 'media/9.png'],
            ['id' => 12, 'file_path' => 'media/12.png'],
        ]);
    }

    private function seedGalleryMedia(): void
    {
        DB::table('media')->insert([
            ['id' => 7, 'file_path' => 'media/7.png'],
            ['id' => 12, 'file_path' => 'media/12.png'],
        ]);
    }

    private function galleryRequest(mixed $images, bool $invalidDate = false): Request
    {
        $request = $this->request(null);
        $request->merge(['images' => $images, 'delivery_date' => $invalidDate ? null : '2026-09-13']);

        return $request;
    }

    private function flashGalleryValidationFailure(string $selection, ?Portfolio $portfolio = null): void
    {
        $request = $this->galleryRequest($selection, true);
        try {
            $portfolio
                ? $this->controller()->update($request, $portfolio->id)
                : $this->controller()->store($request);
            $this->fail('An unrelated date validation failure was expected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('delivery_date', $exception->errors());
            $this->assertArrayNotHasKey('images', $exception->errors());
            // Use Laravel's actual exception handler to flash the original request input.
            $response = $this->app[\Illuminate\Contracts\Debug\ExceptionHandler::class]->render($request, $exception);
            $this->assertSame(302, $response->getStatusCode());
        }
    }

    private function galleryField(Portfolio $portfolio): array
    {
        $html = view('dashboard.portfolios._form', [
            'portfolio' => $portfolio, 'portfolioTranslations' => [],
            'languages' => Language::all(), 'typeSuggestions' => [], 'statusSuggestions' => [],
            'portfolioMedia' => Media::query()->get()->keyBy('id'),
        ])->render();
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $input = $xpath->query('//input[@id="images_picker"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('images', $input->getAttribute('name'));
        $previews = [];
        foreach ($xpath->query('//*[@id="images_picker_preview"]//img') as $image) {
            $previews[] = $image->getAttribute('src');
        }

        return [
            'value' => $input->getAttribute('value'),
            'previews' => $previews,
            'blocked' => $xpath->query('//button[@id="portfolio-save"]')->item(0)->hasAttribute('disabled'),
        ];
    }

    private function statusField(Portfolio $portfolio, string $storedStatus): array
    {
        $html = view('dashboard.portfolios._form', [
            'portfolio' => $portfolio,
            'portfolioTranslations' => ['en' => [
                'locale' => 'en', 'title' => 'Original title', 'type' => 'Website',
                'materials' => 'Laravel', 'status' => $storedStatus,
            ]],
            'languages' => Language::all(),
            'typeSuggestions' => [],
            'statusSuggestions' => ['en' => ['Active', 'Inactive', 'Completed']],
        ])->render();
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $option = $xpath->query('//select[@id="status_en"]/option[@selected]')->item(0);

        return [
            'value' => $option?->getAttribute('value') ?? '',
            'label' => trim($option?->textContent ?? ''),
        ];
    }

    private function request(?string $image): Request
    {
        $request = Request::create('/admin/portfolios', 'POST', [
            'default_image' => $image,
            'order' => 0,
            'delivery_date' => '2026-09-13',
            'translations' => [[
                'locale' => 'en', 'title' => 'Updated title',
                'type' => 'Website', 'materials' => 'Laravel',
            ]],
        ]);
        $request->setLaravelSession($this->app['session']->driver());

        return $request;
    }

    private function portfolio(string $path, ?int $mediaId = null): Portfolio
    {
        $portfolio = Portfolio::create([
            'default_image' => $path, 'default_image_media_id' => $mediaId,
            'order' => 0, 'delivery_date' => '2026-09-13', 'slug' => 'original-title',
        ]);
        $portfolio->translations()->create([
            'locale' => 'en', 'title' => 'Original title', 'type' => 'Website', 'materials' => 'Laravel',
        ]);

        return $portfolio;
    }

    private function defaultImageField(Portfolio $portfolio, ?string $oldImage = null): array
    {
        $this->app['session']->driver()->flashInput($oldImage === null ? [] : ['default_image' => $oldImage]);
        $html = view('dashboard.portfolios._form', [
            'portfolio' => $portfolio, 'portfolioTranslations' => [],
            'languages' => Language::all(), 'typeSuggestions' => [], 'statusSuggestions' => [],
            'portfolioMedia' => Media::query()->get()->keyBy('id'),
        ])->render();
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $input = $xpath->query('//input[@id="default_image_picker"]')->item(0);
        $this->assertNotNull($input);
        $this->assertSame('default_image', $input->getAttribute('name'));
        $preview = $xpath->query('//*[@id="default_image_picker_preview"]//img')->item(0);

        return ['value' => $input->getAttribute('value'), 'preview' => $preview?->getAttribute('src')];
    }
}
