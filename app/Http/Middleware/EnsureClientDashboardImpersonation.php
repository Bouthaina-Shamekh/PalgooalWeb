<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureClientDashboardImpersonation
{
    public function handle(Request $request, Closure $next)
    {
        $clientGuard = Auth::guard('client');
        $adminGuard = Auth::guard('web');

        if (!$clientGuard->check()) {
            return $next($request);
        }

        $isImpersonated = $this->sessionBoolean($request, 'client_impersonated_by_admin');
        $impersonatorAdminId = (int) $request->session()->get('client_impersonator_admin_id', 0);
        $currentAdminId = (int) $adminGuard->id();

        if ($isImpersonated && $impersonatorAdminId > 0 && $currentAdminId === $impersonatorAdminId) {
            return $next($request);
        }

        $this->logoutClient($request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Client dashboard access is available only through the admin panel.',
            ], 403);
        }

        return redirect()
            ->route('frontend.home')
            ->with('warning', 'Client dashboard access is available only through the admin panel.');
    }

    private function logoutClient(Request $request): void
    {
        Auth::guard('client')->logout();
        $request->session()->forget([
            'client_impersonated_by_admin',
            'client_impersonator_admin_id',
        ]);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }

    private function sessionBoolean(Request $request, string $key): bool
    {
        return in_array($request->session()->get($key), [true, 1, '1', 'true', 'on', 'yes'], true);
    }
}
