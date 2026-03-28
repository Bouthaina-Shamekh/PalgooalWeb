<?php

namespace App\Http\Controllers\Tenancy;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\Tenancy\DomainVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainVerificationProbeController extends Controller
{
    public function __invoke(Request $request, DomainVerificationService $verification): JsonResponse
    {
        $host = strtolower($request->getHost());
        $subscription = $verification->resolveProbeSubscription($host);

        abort_unless($subscription !== null, 404);
        abort_unless($subscription->requiresDomainVerification(), 404);
        abort_unless(
            ! $subscription->plan || $subscription->plan->plan_type === Plan::TYPE_MULTI_TENANT,
            404
        );

        $expectedSubscriptionId = (int) $request->integer('subscription');

        if ($expectedSubscriptionId > 0) {
            abort_unless($expectedSubscriptionId === (int) $subscription->getKey(), 404);
        }

        return response()->json([
            'status' => 'ok',
        ]);
    }
}
