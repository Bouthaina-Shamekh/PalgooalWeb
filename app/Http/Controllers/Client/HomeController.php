<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Tenancy\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HomeController extends Controller
{
    public function index()
    {
        $client = Client::query()
            ->withCount(['domains', 'subscriptions'])
            ->findOrFail(Auth::guard('client')->id());

        $invoiceCount = Invoice::where('client_id', $client->id)->count();
        $unpaidInvoiceCount = Invoice::unpaid()->where('client_id', $client->id)->count();
        $recentSubscriptions = Subscription::with('plan')
            ->where('client_id', $client->id)
            ->latest()
            ->limit(5)
            ->get();
        $recentInvoices = Invoice::where('client_id', $client->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('client.index', compact(
            'client',
            'invoiceCount',
            'unpaidInvoiceCount',
            'recentSubscriptions',
            'recentInvoices',
        ));
    }

    public function updateClient()
    {
        $client = Client::findOrFail(Auth::guard('client')->id());

        return view('client.updateclinet', [
            'client' => $client,
            'countryOptions' => $this->countryOptions(),
        ]);
    }

    public function saveUpdateClient(Request $request)
    {
        $client = Client::findOrFail(Auth::guard('client')->id());

        $validated = $request->validate(
            [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($client->id)],
                'company_name' => ['nullable', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:255'],
                'avatar' => ['nullable', 'image', 'max:2048'],
                'country' => ['nullable', 'string', 'max:2'],
                'city' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string'],
                'zip_code' => ['nullable', 'string', 'max:255'],
                'password' => [
                    'nullable',
                    'string',
                    'confirmed',
                    'min:8',
                    'regex:/[A-Z]/',
                    'regex:/[a-z]/',
                    'regex:/[0-9]/',
                    'regex:/[^A-Za-z0-9]/',
                ],
            ],
            [
                'password.confirmed' => 'Passwords do not match.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
            ]
        );

        $payload = [
            'first_name' => trim((string) $validated['first_name']),
            'last_name' => trim((string) $validated['last_name']),
            'email' => trim((string) $validated['email']),
            'company_name' => trim((string) ($validated['company_name'] ?? '')),
            'phone' => trim((string) $validated['phone']),
            'country' => $this->nullableString($validated['country'] ?? null),
            'city' => $this->nullableString($validated['city'] ?? null),
            'address' => $this->nullableString($validated['address'] ?? null),
            'zip_code' => $this->nullableString($validated['zip_code'] ?? null),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = bcrypt($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($client->avatar && Storage::disk('public')->exists($client->avatar)) {
                Storage::disk('public')->delete($client->avatar);
            }

            $payload['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $client->update($payload);

        ActivityLog::create([
            'actor_type' => 'client',
            'actor_id' => $client->id,
            'action' => 'client.updated',
            'meta' => [
                'client_id' => $client->id,
                'client_name' => trim($client->first_name . ' ' . $client->last_name),
                'changes' => array_keys($payload),
            ],
        ]);

        return redirect()
            ->route('client.update_account')
            ->with('success', 'Account updated successfully.');
    }

    public function subscriptions(Request $request)
    {
        $client = Client::findOrFail(Auth::guard('client')->id());
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));
        $allowedStatuses = ['all', 'active', 'pending', 'suspended', 'cancelled'];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        $baseQuery = Subscription::query()
            ->where('client_id', $client->id);

        $subscriptionStats = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'active')->count(),
            'provisioning' => (clone $baseQuery)
                ->whereIn('provisioning_status', [
                    Subscription::PROVISIONING_PENDING,
                    Subscription::PROVISIONING_IN_PROGRESS,
                ])->count(),
            'renewing_soon' => (clone $baseQuery)
                ->whereDate('next_due_date', '>=', now()->toDateString())
                ->whereDate('next_due_date', '<=', now()->addDays(7)->toDateString())
                ->count(),
        ];

        $subscriptions = Subscription::query()
            ->with([
                'plan.translations',
                'template.translations',
            ])
            ->where('client_id', $client->id)
            ->when($status !== 'all', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';

                $query->where(function ($nestedQuery) use ($like) {
                    $nestedQuery
                        ->where('domain_name', 'like', $like)
                        ->orWhere('subdomain', 'like', $like)
                        ->orWhere('username', 'like', $like)
                        ->orWhere('cpanel_username', 'like', $like)
                        ->orWhereHas('plan', function ($planQuery) use ($like) {
                            $planQuery
                                ->where('name', 'like', $like)
                                ->orWhere('slug', 'like', $like)
                                ->orWhereHas('translations', function ($translationQuery) use ($like) {
                                    $translationQuery
                                        ->where('name', 'like', $like)
                                        ->orWhere('title', 'like', $like);
                                });
                        })
                        ->orWhereHas('template', function ($templateQuery) use ($like) {
                            $templateQuery
                                ->where('name', 'like', $like)
                                ->orWhere('slug', 'like', $like)
                                ->orWhereHas('translations', function ($translationQuery) use ($like) {
                                    $translationQuery
                                        ->where('name', 'like', $like)
                                        ->orWhere('title', 'like', $like);
                                });
                        });
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $statusOptions = [
            'all' => 'كل الاشتراكات',
            'active' => 'النشطة',
            'pending' => 'المعلقة',
            'suspended' => 'الموقوفة',
            'cancelled' => 'الملغية',
        ];

        return view('client.subscriptions', compact(
            'subscriptions',
            'subscriptionStats',
            'statusOptions',
            'status',
            'search'
        ));
    }

    public function invoices()
    {
        $client = Client::find(Auth::guard('client')->user()->id);
        $invoices = Invoice::with(['client', 'items'])->where('client_id',$client->id)->latest()->paginate(20);
        return view('client.invoices',compact('invoices'));
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function countryOptions(): array
    {
        return [
            '' => 'Select Country',
            'PS' => 'Palestine',
            'JO' => 'Jordan',
            'SA' => 'Saudi Arabia',
            'AE' => 'United Arab Emirates',
            'EG' => 'Egypt',
            'LB' => 'Lebanon',
            'SY' => 'Syria',
            'IQ' => 'Iraq',
            'KW' => 'Kuwait',
            'QA' => 'Qatar',
            'BH' => 'Bahrain',
            'OM' => 'Oman',
            'YE' => 'Yemen',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'FR' => 'France',
        ];
    }
}
