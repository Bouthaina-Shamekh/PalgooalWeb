<?php

namespace App\Providers;

use App\Models\GeneralSetting;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use App\Support\Blocks\HeroBlock;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Support\Sections\SectionRegistry;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind('abilities', function() {
            return include base_path('data/abilities.php');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Gate::define('access-dashboard', fn($user) => (bool)$user->is_admin);
        Gate::define('manage-reviews', fn($user) => (bool)$user->is_admin);

        //Authouration
        Gate::before(function ($user, $ability) {
            if($user instanceof User) {
                if($user->super_admin) {
                    return true;
                }
            }
        });
        view()->composer('*', function ($view) {
            $currentLocale = app()->getLocale();
            $currentLanguage = Language::where('code', $currentLocale)->first();
            $languages = Language::where('is_active', true)->get();
            $settings  = GeneralSetting::first();
            $view->with([
                'currentLocale' => $currentLocale,
                'currentLanguage' => $currentLanguage,
                'languages' => $languages,
                'settings' => $settings,
            ]);
        });
        View::composer('*', function ($view) {
            // إذا تم تمرير المتغير 'page' داخل أي view رئيسي
            if ($view->getData()['page'] ?? false) {
                $page = $view->getData()['page'];
                $view->with('currentPage', $page);
            }
        });
        // Hero section
        SectionRegistry::register('hero', [
            // Blade view used to render this section
            'view'  => 'components.template.sections.hero',

            // Optional metadata (for UI/builders if تحتاج مستقبلاً)
            'label' => 'Hero',
            'group' => 'hero',
        ]);

        // Features section (اللي اشتغلنا عليه الآن)
        SectionRegistry::register('features', [
            'view'  => 'components.template.sections.features',
            'label' => 'Features',
            'group' => 'content',
        ]);

        // تقدر تسجل باقي الأنواع بنفس الفكرة:
        SectionRegistry::register('services', [
            'view'  => 'components.template.sections.services',
            'label' => 'Services',
            'group' => 'content',
        ]);

        SectionRegistry::register('templates', [
            'view'  => 'components.template.sections.templates',
            'label' => 'Templates',
            'group' => 'content',
        ]);
    }
}
