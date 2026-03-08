<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureClientCanLogin
{
    public function handle(Request $request, Closure $next)
    {
        $guard = Auth::guard('client');

        if ($guard->check() && !$guard->user()?->can_login && !$this->isAdminImpersonationSession($request)) {
            $guard->logout();
            $request->session()->forget([
                'client_impersonated_by_admin',
                'client_impersonator_admin_id',
            ]);
            $request->session()->regenerate();
            $request->session()->regenerateToken();
        }

        return $next($request);
    }

    private function isAdminImpersonationSession(Request $request): bool
    {
        return $this->sessionBoolean($request, 'client_impersonated_by_admin')
            && (int) $request->session()->get('client_impersonator_admin_id', 0) > 0
            && (int) Auth::guard('web')->id() === (int) $request->session()->get('client_impersonator_admin_id', 0);
    }

    private function sessionBoolean(Request $request, string $key): bool
    {
        return in_array($request->session()->get($key), [true, 1, '1', 'true', 'on', 'yes'], true);
    }
}
