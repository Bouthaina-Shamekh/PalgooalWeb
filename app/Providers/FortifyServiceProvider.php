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
use Laravel\Fortify\Contracts\PasswordResetResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $request = request();

        if ($request->is('dashboard/*')) {
            Config::set('fortify.guard', 'web');
            Config::set('fortify.passwords', 'users');
            Config::set('fortify.prefix', 'admin');
            Config::set('fortify.home', '/admin/home');
            Config::set('fortify.views', true);
        }

        if ($request->is('client/*')) {
            Config::set('fortify.guard', 'client');
            Config::set('fortify.passwords', 'clients');
            Config::set('fortify.prefix', 'client');
            Config::set('fortify.home', '/client/home');
            Config::set('fortify.views', true);
        }

        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                if (Config::get('fortify.guard') == 'web') {
                    return redirect()->intended('/admin/home');
                } elseif (Config::get('fortify.guard') == 'client') {
                    return redirect()->intended('/client/home');
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

        $this->app->instance(PasswordResetResponse::class, new class implements PasswordResetResponse {
            public function toResponse($request)
            {
                if ($request->is('client/*')) {
                    return redirect()->route('client.login')->with('status', __(
                        'Your password has been reset successfully. Please login with your new password.'
                    ));
                }

                return redirect()->to('/login')->with('status', __("Your password has been reset."));
            }
        });
    }

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

            return view('auth.register');
        });

        Fortify::requestPasswordResetLinkView(function () {
            if (Config::get('fortify.guard') === 'client') {
                return view('auth.client.forgot-password');
            }

            return view('auth.forgot-password');
        });

        Fortify::resetPasswordView(function (Request $request) {
            if (Config::get('fortify.guard') === 'client') {
                return view('auth.client.reset-password', ['request' => $request]);
            }

            return view('auth.reset-password', ['request' => $request]);
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(
                Str::lower($request->input(Fortify::username())) . '|' . $request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
