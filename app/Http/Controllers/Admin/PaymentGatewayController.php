<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ADR-007 Phase 5A — Gateway Configuration Management
 *
 * Manages the payment gateway settings from the admin dashboard.
 * Routes:
 *   GET  /admin/settings/payments          → index()
 *   POST /admin/settings/payments/{id}     → update()
 *   POST /admin/settings/payments/{id}/activate → activate()
 */
class PaymentGatewayController extends Controller
{
    /**
     * List all registered gateways with their current configuration.
     */
    public function index(): View
    {
        $gateways      = PaymentGateway::orderBy('is_active', 'desc')->orderBy('name')->get();
        $gatewayMap    = config('payment.gateways', []);

        return view('dashboard.settings.payments.index', compact('gateways', 'gatewayMap'));
    }

    /**
     * Show the edit form for a single gateway.
     */
    public function edit(PaymentGateway $paymentGateway): View
    {
        return view('dashboard.settings.payments.edit', [
            'gateway'    => $paymentGateway,
            'gatewayMap' => config('payment.gateways', []),
        ]);
    }

    /**
     * Update gateway credentials and mode.
     * Secret fields are only overwritten when the user provides a non-empty value
     * (empty = "keep existing") so masked fields don't accidentally clear the keys.
     */
    public function update(Request $request, PaymentGateway $paymentGateway): RedirectResponse
    {
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'mode'           => 'required|in:sandbox,live',
            'public_key'     => 'nullable|string|max:500',
            'secret_key'     => 'nullable|string|max:500',
            'webhook_secret' => 'nullable|string|max:500',
        ]);

        // Only overwrite key fields if the user actually typed something.
        $payload = ['name' => $data['name'], 'mode' => $data['mode']];

        if (!empty($data['public_key'])) {
            $payload['public_key'] = $data['public_key'];
        }
        if (!empty($data['secret_key'])) {
            $payload['secret_key'] = $data['secret_key'];
        }
        if (!empty($data['webhook_secret'])) {
            $payload['webhook_secret'] = $data['webhook_secret'];
        }

        $paymentGateway->update($payload);

        return redirect()
            ->route('dashboard.settings.payments.index')
            ->with('ok', t('dashboard.Payment_Gateway_Updated', 'تم حفظ إعدادات بوابة الدفع.'));
    }

    /**
     * Activate a gateway (deactivates all others atomically).
     */
    public function activate(Request $request, PaymentGateway $paymentGateway): RedirectResponse
    {
        DB::transaction(function () use ($paymentGateway) {
            PaymentGateway::query()->update(['is_active' => false]);
            $paymentGateway->update(['is_active' => true]);
        });

        return redirect()
            ->route('dashboard.settings.payments.index')
            ->with('ok', t('dashboard.Payment_Gateway_Activated', 'تم تفعيل بوابة الدفع: :name', [':name' => $paymentGateway->name]));
    }

    /**
     * Deactivate all gateways (disables live payment processing).
     */
    public function deactivate(PaymentGateway $paymentGateway): RedirectResponse
    {
        PaymentGateway::query()->update(['is_active' => false]);

        return redirect()
            ->route('dashboard.settings.payments.index')
            ->with('ok', t('dashboard.Payment_Gateway_Deactivated', 'تم إيقاف جميع بوابات الدفع.'));
    }
}
