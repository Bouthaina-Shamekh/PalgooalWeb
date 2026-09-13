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

    public function test_shared_dashboard_head_renders_one_zoomable_viewport(): void
    {
        $this->withoutVite();
        $html = view('dashboard.layouts.partials.head', ['settings' => null])->render();
        preg_match_all('/<meta\s+name="viewport"\s+content="([^"]*)"/i', $html, $matches);
        $this->assertSame(['width=device-width, initial-scale=1'], $matches[1]);
        $this->assertDoesNotMatchRegularExpression('/user-scalable|maximum-scale|minimum-scale/i', $html);
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
