<?php

namespace App\Providers;

use App\Models\GeneralSetting;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use App\Models\Sections\SectionDefinition;
use App\Models\Sections\SectionDefinitionField;
use App\Policies\SectionDefinitionFieldPolicy;
use App\Policies\SectionDefinitionPolicy;
use App\Support\AdminBrand\AdminBrandCssGenerator;
use App\Support\Blocks\HeroBlock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\DomainProvider;
use App\Models\DomainTld;
use App\Models\Server;
use App\Models\Media;
use App\Models\Service;
use App\Models\TranslationValue;
use App\Models\Section;
use App\Policies\LanguagePolicy;
use App\Policies\PlanPolicy;
use App\Policies\PlanCategoryPolicy;
use App\Policies\DomainProviderPolicy;
use App\Policies\DomainTldPolicy;
use App\Policies\ServerPolicy;
use App\Policies\MediaPolicy;
use App\Policies\ServicePolicy;
use App\Policies\TranslationValuePolicy;
use App\Policies\SectionPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('abilities', function () {
            return include base_path('data/abilities.php');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Gate::policy(SectionDefinition::class, SectionDefinitionPolicy::class);
        Gate::policy(SectionDefinitionField::class, SectionDefinitionFieldPolicy::class);
        Gate::policy(Section::class, SectionPolicy::class);
        Gate::policy(Language::class, LanguagePolicy::class);
        Gate::policy(Plan::class, PlanPolicy::class);
        Gate::policy(PlanCategory::class, PlanCategoryPolicy::class);
        Gate::policy(DomainProvider::class, DomainProviderPolicy::class);
        Gate::policy(DomainTld::class, DomainTldPolicy::class);
        Gate::policy(Server::class, ServerPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(TranslationValue::class, TranslationValuePolicy::class);
        // 'is_admin' column does not exist — the correct column is 'super_admin'.
        Gate::define('access-dashboard', fn($user) => (bool)$user->super_admin);
        Gate::define('manage-reviews', fn($user) => (bool)$user->super_admin);

        //Authouration
        Gate::before(function ($user, $ability) {
            if ($user instanceof User) {
                if ($user->super_admin) {
                    return true;
                }
            }
        });
        view()->composer('*', function ($view) {
            $currentLocale = app()->getLocale();

            $currentLanguage = Cache::remember(
                "lang_{$currentLocale}",
                3600,
                fn () => Language::where('code', $currentLocale)->first()
            );

            $languages = Cache::remember(
                'active_languages',
                3600,
                fn () => Language::where('is_active', true)->get()
            );

            // ADR-005 Wave 1: eager-load media relations so resolved*Path() helpers
            // work without extra queries when called from any view.
            $settings = Cache::remember(
                'general_settings',
                3600,
                fn () => GeneralSetting::with([
                    'logoMedia',
                    'darkLogoMedia',
                    'stickyLogoMedia',
                    'darkStickyLogoMedia',
                    'adminLogoMedia',
                    'adminDarkLogoMedia',
                    'faviconMedia',
                ])->first()
            );

            $view->with([
                'currentLocale'   => $currentLocale,
                'currentLanguage' => $currentLanguage,
                'languages'       => $languages,
                'settings'        => $settings,
            ]);
        });
        View::composer('*', function ($view) {
            if ($view->getData()['page'] ?? false) {
                $page = $view->getData()['page'];
                $view->with('currentPage', $page);
            }
        });

        // Admin Brand Theme — generate CSS file on first boot if absent.
        // If the file already exists this is a near-zero-cost exists() check.
        // Wrapped in booted() so the DB is available; errors are suppressed
        // because var() fallbacks in app.css handle a missing file gracefully.
        $this->app->booted(function () {
            AdminBrandCssGenerator::generateIfMissing();
        });
    }
}
