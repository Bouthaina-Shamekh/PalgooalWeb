<?php

namespace App\Providers;

use App\Models\Language;
use App\Models\Page;
use App\Models\User;
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
            $view->with([
                'currentLocale' => $currentLocale,
                'currentLanguage' => $currentLanguage,
                'languages' => $languages,
            ]);
        });
        View::composer('*', function ($view) {
            // إذا تم تمرير المتغير 'page' داخل أي view رئيسي
            if ($view->getData()['page'] ?? false) {
                $page = $view->getData()['page'];
                $view->with('currentPage', $page);
            }
        });
    }
}
