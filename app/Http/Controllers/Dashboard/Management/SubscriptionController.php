<?php

namespace App\Http\Controllers\Dashboard\Management;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Client;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index()
    {
        $subscriptions = Subscription::with(['client', 'plan'])->latest()->paginate(20);
        return view('dashboard.management.subscriptions.index', compact('subscriptions'));
    }

    public function create()
    {
        $clients = Client::all();
        $plans = Plan::all();
        return view('dashboard.management.subscriptions.create', compact('clients', 'plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', Rule::in(['pending','active','suspended','cancelled'])],
            'price' => ['required', 'numeric', 'min:0'],
            'username' => ['nullable', 'string', 'max:255'],
            'server_id' => ['nullable', 'integer', 'exists:servers,id'],
            'next_due_date' => ['nullable', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'domain_option' => ['required', Rule::in(['new','subdomain','existing'])],
            'domain_name' => ['required_if:domain_option,new,existing'],
        ]);
        Subscription::create($data);
        return redirect()->route('dashboard.subscriptions.index')->with('ok', 'تم إضافة الاشتراك بنجاح');
    }

    public function edit(Subscription $subscription)
    {
        $clients = Client::all();
        $plans = Plan::all();
        return view('dashboard.management.subscriptions.edit', compact('subscription', 'clients', 'plans'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['required', Rule::in(['pending','active','suspended','cancelled'])],
            'price' => ['required', 'numeric', 'min:0'],
            'username' => ['nullable', 'string', 'max:255'],
            'server_id' => ['nullable', 'integer', 'exists:servers,id'],
            'next_due_date' => ['nullable', 'date'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'domain_option' => ['required', Rule::in(['new','subdomain','existing'])],
            'domain_name' => ['required_if:domain_option,new,existing'],
        ]);
        $subscription->update($data);
        return redirect()->route('dashboard.subscriptions.index')->with('ok', 'تم تحديث الاشتراك بنجاح');
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();
        return redirect()->route('dashboard.subscriptions.index')->with('ok', 'تم حذف الاشتراك');
    }
}
