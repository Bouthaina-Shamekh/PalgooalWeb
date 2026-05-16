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
use App\Support\Blocks\HeroBlock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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

            $settings = Cache::remember(
                'general_settings',
                3600,
                fn () => GeneralSetting::first()
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
    }
}
