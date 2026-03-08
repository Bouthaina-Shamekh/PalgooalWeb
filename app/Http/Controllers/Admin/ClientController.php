<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\ClientNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view', Client::class);

        $search = trim((string) $request->query('search', ''));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 25], true) ? $perPage : 10;

        $clients = Client::query()
            ->withCount(['subscriptions', 'domains'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($clientQuery) use ($search) {
                    $clientQuery->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.clients', compact('clients', 'search', 'perPage'));
    }

    public function create(): View
    {
        $this->authorize('create', Client::class);

        return view('dashboard.clients.create', [
            'client' => null,
            'countryOptions' => $this->countryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Client::class);

        $validated = $this->validateClient($request);
        $client = Client::create($this->buildClientPayload($request, $validated));

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.created',
            'meta' => [
                'client_id' => $client->id,
                'client_name' => trim($client->first_name . ' ' . $client->last_name),
            ],
        ]);

        return redirect()
            ->route('dashboard.clients')
            ->with('success', 'Client added successfully.');
    }

    public function show(Request $request, Client $client): View
    {
        $this->authorize('view', Client::class);

        $activeTab = (string) $request->query('tab', 'details');
        if (!in_array($activeTab, ['details', 'contacts', 'notes', 'activities'], true)) {
            $activeTab = 'details';
        }

        $client->load(['subscriptions', 'domains'])
            ->loadCount(['subscriptions', 'domains', 'contacts', 'notes']);

        $clientContacts = $client->contacts()->orderByDesc('created_at')->get();
        $clientNotes = $client->notes()->with('admin')->orderByDesc('created_at')->get();
        $clientActivities = ActivityLog::where(function ($query) use ($client) {
            $query->where(function ($activityQuery) use ($client) {
                $activityQuery->where('actor_type', 'client')
                    ->where('actor_id', $client->id);
            })->orWhere('meta->client_id', $client->id);
        })
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('dashboard.clients.show', [
            'client' => $client,
            'clientContacts' => $clientContacts,
            'clientNotes' => $clientNotes,
            'clientActivities' => $clientActivities,
            'activeTab' => $activeTab,
            'contactRoleOptions' => $this->contactRoleOptions(),
        ]);
    }

    public function edit(Client $client): View
    {
        $this->authorize('edit', Client::class);

        return view('dashboard.clients.edit', [
            'client' => $client,
            'countryOptions' => $this->countryOptions(),
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('edit', Client::class);

        $validated = $this->validateClient($request, $client);
        $payload = $this->buildClientPayload($request, $validated, $client);

        $client->update($payload);

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.updated',
            'meta' => [
                'client_id' => $client->id,
                'client_name' => trim($client->first_name . ' ' . $client->last_name),
                'changes' => array_keys($payload),
            ],
        ]);

        return redirect()
            ->route('dashboard.clients.show', ['client' => $client, 'tab' => 'details'])
            ->with('success', 'Client updated successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', Client::class);

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.deleted',
            'meta' => [
                'client_id' => $client->id,
                'client_name' => trim($client->first_name . ' ' . $client->last_name),
            ],
        ]);

        $this->deleteAvatar($client);
        $client->delete();

        return redirect()
            ->route('dashboard.clients')
            ->with('success', 'Client deleted successfully.');
    }

    public function impersonate(Client $client): RedirectResponse
    {
        $this->authorize('login', Client::class);

        if (!$client->can_login) {
            return redirect()
                ->back()
                ->with('warning', 'Client login is disabled.');
        }

        $admin = Auth::user();
        Auth::guard('client')->login($client);

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => $admin?->id,
            'action' => 'client.impersonated',
            'meta' => [
                'client_id' => $client->id,
                'client_email' => $client->email,
            ],
        ]);

        return redirect()
            ->route('client.home')
            ->with('success', 'You are now logged in as the client.');
    }

    public function storeContact(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('edit', Client::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:client_contacts,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(array_keys($this->contactRoleOptions()))],
            'can_login' => ['nullable', 'boolean'],
            'password_hash' => ['nullable', 'string', 'min:6'],
        ]);

        $canLogin = (bool) ((int) ($validated['can_login'] ?? 0));
        if ($canLogin && empty($validated['password_hash'])) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['password_hash' => 'Password is required when login access is enabled.']);
        }

        $contactData = [
            'client_id' => $client->id,
            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'phone' => $this->nullableString($validated['phone'] ?? null),
            'role' => $validated['role'],
            'can_login' => $canLogin,
            'password_hash' => !empty($validated['password_hash']) ? bcrypt($validated['password_hash']) : null,
        ];

        ClientContact::create($contactData);

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.contact.created',
            'meta' => [
                'client_id' => $client->id,
                'contact_name' => $contactData['name'],
                'contact_role' => $contactData['role'],
            ],
        ]);

        return redirect()
            ->route('dashboard.clients.show', ['client' => $client, 'tab' => 'contacts'])
            ->with('success', 'Contact added successfully.');
    }

    public function destroyContact(Client $client, ClientContact $contact): RedirectResponse
    {
        $this->authorize('edit', Client::class);

        abort_unless((int) $contact->client_id === (int) $client->id, 404);

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.contact.deleted',
            'meta' => [
                'client_id' => $client->id,
                'contact_name' => $contact->name,
                'contact_role' => $contact->role,
            ],
        ]);

        $contact->delete();

        return redirect()
            ->route('dashboard.clients.show', ['client' => $client, 'tab' => 'contacts'])
            ->with('success', 'Contact deleted successfully.');
    }

    public function storeNote(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('edit', Client::class);

        $validated = $request->validate([
            'note' => ['required', 'string'],
        ]);

        ClientNote::create([
            'client_id' => $client->id,
            'admin_id' => Auth::id(),
            'note' => trim((string) $validated['note']),
        ]);

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.note.created',
            'meta' => [
                'client_id' => $client->id,
            ],
        ]);

        return redirect()
            ->route('dashboard.clients.show', ['client' => $client, 'tab' => 'notes'])
            ->with('success', 'Note added successfully.');
    }

    public function destroyNote(Client $client, ClientNote $note): RedirectResponse
    {
        $this->authorize('edit', Client::class);

        abort_unless((int) $note->client_id === (int) $client->id, 404);

        ActivityLog::create([
            'actor_type' => 'admin',
            'actor_id' => Auth::id(),
            'action' => 'client.note.deleted',
            'meta' => [
                'client_id' => $client->id,
            ],
        ]);

        $note->delete();

        return redirect()
            ->route('dashboard.clients.show', ['client' => $client, 'tab' => 'notes'])
            ->with('success', 'Note deleted successfully.');
    }

    private function validateClient(Request $request, ?Client $client = null): array
    {
        $passwordRules = [
            $client ? 'nullable' : 'required',
            'string',
            'confirmed',
            'min:8',
            'regex:/[A-Z]/',
            'regex:/[a-z]/',
            'regex:/[0-9]/',
            'regex:/[^A-Za-z0-9]/',
        ];

        return $request->validate(
            [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($client?->id)],
                'company_name' => ['nullable', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:255'],
                'can_login' => ['required', 'boolean'],
                'avatar' => ['nullable', 'image', 'max:2048'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
                'country' => ['nullable', 'string', 'max:2'],
                'city' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string'],
                'zip_code' => ['nullable', 'string', 'max:255'],
                'password' => $passwordRules,
            ],
            [
                'password.confirmed' => 'Passwords do not match.',
                'password.min' => 'Password must be at least 8 characters.',
                'password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
            ]
        );
    }

    private function buildClientPayload(Request $request, array $validated, ?Client $client = null): array
    {
        $payload = [
            'first_name' => trim((string) $validated['first_name']),
            'last_name' => trim((string) $validated['last_name']),
            'email' => trim((string) $validated['email']),
            'company_name' => trim((string) ($validated['company_name'] ?? '')),
            'phone' => trim((string) $validated['phone']),
            'can_login' => (bool) ((int) $validated['can_login']),
            'status' => $validated['status'],
            'country' => $this->nullableString($validated['country'] ?? null),
            'city' => $this->nullableString($validated['city'] ?? null),
            'address' => $this->nullableString($validated['address'] ?? null),
            'zip_code' => $this->nullableString($validated['zip_code'] ?? null),
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = bcrypt($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($client) {
                $this->deleteAvatar($client);
            }

            $payload['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        return $payload;
    }

    private function deleteAvatar(Client $client): void
    {
        if ($client->avatar && Storage::disk('public')->exists($client->avatar)) {
            Storage::disk('public')->delete($client->avatar);
        }
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

    private function contactRoleOptions(): array
    {
        return [
            'billing' => 'Billing',
            'tech' => 'Technical',
            'general' => 'General',
        ];
    }
}
