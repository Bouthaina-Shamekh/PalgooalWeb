<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $request = request();

        if ($request->is('dashboard/*')) {
            Config::set('fortify.guard', 'web');
            Config::set('fortify.password', 'users');
            Config::set('fortify.prefix', 'dashboard');
            Config::set('fortify.home', '/dashboard/home');
            Config::set('fortify.views', true);
        }

        if ($request->is('client/*')) {
            Config::set('fortify.guard', 'client');
            Config::set('fortify.password', 'clinents');
            Config::set('fortify.prefix', 'client');
            Config::set('fortify.home', '/client/home');
            Config::set('fortify.views', true);
        }

        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                if (Config::get('fortify.guard') == 'web') {
                    return redirect()->intended('/dashboard/home');
                } elseif (Config::get('fortify.guard') == 'client') {
                    return redirect()->route('client.home');
                } else {
                    return redirect()->intended('/');
                }
            }
        });
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
            public function toResponse($request)
            {
                if (Config::get('fortify.guard') == 'web') {
                    return redirect()->intended('/login');
                }
                if (Config::get('fortify.guard') == 'client') {
                    return redirect()->intended('/client/login');
                }
                return redirect('/');
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::loginView(function () {
            if (Config::get('fortify.guard') == 'client') {
                return view('auth.client.login');
            }
            return view('auth.login');
        });

        Fortify::registerView(function () {
            if (Config::get('fortify.guard') == 'client') {
                return view('auth.client.register');
            }
        });
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
