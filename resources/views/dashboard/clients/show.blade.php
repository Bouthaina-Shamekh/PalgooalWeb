<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.home') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('dashboard.clients') }}">Clients</a></li>
                <li class="breadcrumb-item" aria-current="page">Client Details</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">{{ $client->first_name }} {{ $client->last_name }}</h2>
            </div>
        </div>
    </div>

    @include('dashboard.clients._alerts')

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 lg:col-span-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-4">
                        <img src="{{ $client->avatar ? asset('storage/' . $client->avatar) : asset('assets/images/user/avatar-1.jpg') }}"
                            class="w-24 h-24 mx-auto rounded-full object-cover border-4 border-gray-200 shadow-lg"
                            alt="Client avatar">
                    </div>

                    <h4 class="mb-1 font-semibold">{{ $client->first_name }} {{ $client->last_name }}</h4>
                    <p class="text-gray-600 mb-2">{{ $client->email }}</p>

                    @if ($client->company_name)
                        <p class="text-sm text-gray-500 mb-3">
                            <i class="ti ti-building mr-1"></i>{{ $client->company_name }}
                        </p>
                    @endif

                    <div class="flex justify-center gap-2 mb-4">
                        @if ($client->status === 'active')
                            <span class="badge bg-success-500/10 text-success-500 rounded-full px-3 py-1">
                                <i class="ti ti-check w-3 h-3 mr-1"></i>Active
                            </span>
                        @else
                            <span class="badge bg-danger-500/10 text-danger rounded-full px-3 py-1">
                                <i class="ti ti-ban w-3 h-3 mr-1"></i>Inactive
                            </span>
                        @endif

                        @if ($client->can_login)
                            <span class="badge bg-blue-500/10 text-blue-500 rounded-full px-3 py-1">
                                <i class="ti ti-login w-3 h-3 mr-1"></i>Can Login
                            </span>
                        @else
                            <span class="badge bg-gray-500/10 text-gray-500 rounded-full px-3 py-1">
                                <i class="ti ti-lock w-3 h-3 mr-1"></i>No Login
                            </span>
                        @endif
                    </div>

                    <div class="grid grid-cols-3 gap-4 mt-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $client->subscriptions_count ?? 0 }}</div>
                            <div class="text-xs text-gray-500">Subscriptions</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ $client->domains_count ?? 0 }}</div>
                            <div class="text-xs text-gray-500">Domains</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ $client->contacts_count ?? 0 }}</div>
                            <div class="text-xs text-gray-500">Contacts</div>
                        </div>
                    </div>

                    <div class="flex gap-2 mt-6">
                        @can('edit', 'App\\Models\\Client')
                            <a href="{{ route('dashboard.clients.edit', $client) }}" class="btn btn-primary btn-sm flex-1">
                                <i class="ti ti-edit mr-1"></i>Edit
                            </a>
                        @endcan
                        <a href="{{ route('dashboard.clients') }}" class="btn btn-secondary btn-sm flex-1">
                            <i class="ti ti-arrow-left mr-1"></i>Back
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12 lg:col-span-8">
            <div class="card">
                <div class="card-header">
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="flex flex-wrap gap-2" aria-label="Tabs">
                            <a href="{{ route('dashboard.clients.show', ['client' => $client, 'tab' => 'details']) }}"
                                class="flex items-center px-4 py-3 text-sm font-medium rounded-t-lg transition-colors duration-200 {{ $activeTab === 'details' ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                                <i class="ti ti-user text-lg mr-2"></i>
                                <span>Details</span>
                            </a>
                            <a href="{{ route('dashboard.clients.show', ['client' => $client, 'tab' => 'contacts']) }}"
                                class="flex items-center px-4 py-3 text-sm font-medium rounded-t-lg transition-colors duration-200 {{ $activeTab === 'contacts' ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                                <i class="ti ti-users text-lg mr-2"></i>
                                <span>Contacts</span>
                                @if ($clientContacts->count() > 0)
                                    <span class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-500 rounded-full">
                                        {{ $clientContacts->count() }}
                                    </span>
                                @endif
                            </a>
                            <a href="{{ route('dashboard.clients.show', ['client' => $client, 'tab' => 'notes']) }}"
                                class="flex items-center px-4 py-3 text-sm font-medium rounded-t-lg transition-colors duration-200 {{ $activeTab === 'notes' ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                                <i class="ti ti-notes text-lg mr-2"></i>
                                <span>Notes</span>
                                @if ($clientNotes->count() > 0)
                                    <span class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-green-500 rounded-full">
                                        {{ $clientNotes->count() }}
                                    </span>
                                @endif
                            </a>
                            <a href="{{ route('dashboard.clients.show', ['client' => $client, 'tab' => 'activities']) }}"
                                class="flex items-center px-4 py-3 text-sm font-medium rounded-t-lg transition-colors duration-200 {{ $activeTab === 'activities' ? 'bg-blue-50 text-blue-700 border-b-2 border-blue-700' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                                <i class="ti ti-history text-lg mr-2"></i>
                                <span>Activities</span>
                                @if ($clientActivities->count() > 0)
                                    <span class="ml-2 inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-purple-500 rounded-full">
                                        {{ $clientActivities->count() }}
                                    </span>
                                @endif
                            </a>
                        </nav>
                    </div>
                </div>

                <div class="card-body">
                    @if ($activeTab === 'details')
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-12">
                                <h5 class="mb-4 font-semibold text-gray-900">Personal Information</h5>
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <label class="text-sm text-gray-600">First Name</label>
                                <p class="font-medium">{{ $client->first_name }}</p>
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <label class="text-sm text-gray-600">Last Name</label>
                                <p class="font-medium">{{ $client->last_name }}</p>
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <label class="text-sm text-gray-600">Email</label>
                                <p class="font-medium">{{ $client->email }}</p>
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <label class="text-sm text-gray-600">Phone</label>
                                <p class="font-medium">{{ $client->phone ?: 'Not provided' }}</p>
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <label class="text-sm text-gray-600">Company</label>
                                <p class="font-medium">{{ $client->company_name ?: 'Not provided' }}</p>
                            </div>
                            <div class="col-span-12 md:col-span-6">
                                <label class="text-sm text-gray-600">Member Since</label>
                                <p class="font-medium">{{ $client->created_at->format('M j, Y') }}</p>
                            </div>

                            @if ($client->country || $client->city || $client->address)
                                <div class="col-span-12 mt-4">
                                    <h5 class="mb-4 font-semibold text-gray-900">Address Information</h5>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="text-sm text-gray-600">Country</label>
                                    <p class="font-medium">{{ $client->country ?: 'Not provided' }}</p>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="text-sm text-gray-600">City</label>
                                    <p class="font-medium">{{ $client->city ?: 'Not provided' }}</p>
                                </div>
                                <div class="col-span-12 md:col-span-8">
                                    <label class="text-sm text-gray-600">Address</label>
                                    <p class="font-medium">{{ $client->address ?: 'Not provided' }}</p>
                                </div>
                                <div class="col-span-12 md:col-span-4">
                                    <label class="text-sm text-gray-600">Zip Code</label>
                                    <p class="font-medium">{{ $client->zip_code ?: 'Not provided' }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($activeTab === 'contacts')
                        <div class="space-y-6">
                            @can('edit', 'App\\Models\\Client')
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <h5 class="font-semibold text-gray-900 mb-4">Add Contact</h5>
                                    <form method="POST" action="{{ route('dashboard.clients.contacts.store', $client) }}" class="grid grid-cols-12 gap-4">
                                        @csrf
                                        <div class="col-span-12 md:col-span-6">
                                            <label for="contact_name" class="form-label">Name</label>
                                            <input id="contact_name" name="name" type="text" class="form-control" value="{{ old('name') }}">
                                        </div>
                                        <div class="col-span-12 md:col-span-6">
                                            <label for="contact_email" class="form-label">Email</label>
                                            <input id="contact_email" name="email" type="email" class="form-control" value="{{ old('email') }}">
                                        </div>
                                        <div class="col-span-12 md:col-span-4">
                                            <label for="contact_phone" class="form-label">Phone</label>
                                            <input id="contact_phone" name="phone" type="text" class="form-control" value="{{ old('phone') }}">
                                        </div>
                                        <div class="col-span-12 md:col-span-4">
                                            <label for="contact_role" class="form-label">Role</label>
                                            <select id="contact_role" name="role" class="form-select">
                                                @foreach ($contactRoleOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected(old('role', 'general') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-span-12 md:col-span-4">
                                            <label for="contact_can_login" class="form-label">Login Access</label>
                                            <select id="contact_can_login" name="can_login" class="form-select">
                                                <option value="0" @selected(old('can_login', '0') === '0')>No Login</option>
                                                <option value="1" @selected(old('can_login') === '1')>Can Login</option>
                                            </select>
                                        </div>
                                        <div class="col-span-12">
                                            <label for="contact_password" class="form-label">Password</label>
                                            <input id="contact_password" name="password_hash" type="password" class="form-control">
                                            <small class="text-muted">Required only if login access is enabled.</small>
                                        </div>
                                        <div class="col-span-12 flex justify-end">
                                            <button type="submit" class="btn btn-primary">Add Contact</button>
                                        </div>
                                    </form>
                                </div>
                            @endcan

                            @if ($clientContacts->count() > 0)
                                <div class="space-y-3">
                                    @foreach ($clientContacts as $contact)
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex justify-between items-start gap-3">
                                                <div class="flex-1">
                                                    <h6 class="font-semibold">{{ $contact->name }}</h6>
                                                    <p class="text-sm text-gray-600">{{ $contact->email }}</p>
                                                    @if ($contact->phone)
                                                        <p class="text-sm text-gray-600">{{ $contact->phone }}</p>
                                                    @endif
                                                    <span class="badge bg-blue-500/10 text-blue-500 rounded-full text-xs mt-2">
                                                        {{ ucfirst($contact->role) }}
                                                    </span>
                                                    @if ($contact->can_login)
                                                        <span class="badge bg-green-500/10 text-green-500 rounded-full text-xs mt-2 ml-1">
                                                            Can Login
                                                        </span>
                                                    @endif
                                                </div>

                                                @can('edit', 'App\\Models\\Client')
                                                    <form method="POST" action="{{ route('dashboard.clients.contacts.destroy', ['client' => $client, 'contact' => $contact]) }}"
                                                        onsubmit="return confirm('Delete this contact?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <i class="ti ti-users text-4xl text-gray-300 mb-2"></i>
                                    <p class="text-gray-500">No contacts added yet</p>
                                    <p class="text-sm text-gray-400">Add contacts to manage client communication</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($activeTab === 'notes')
                        <div class="space-y-4">
                            @can('edit', 'App\\Models\\Client')
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <h5 class="font-semibold text-gray-900 mb-3">Add Note</h5>
                                    <form method="POST" action="{{ route('dashboard.clients.notes.store', $client) }}">
                                        @csrf
                                        <div class="flex gap-3">
                                            <textarea name="note" class="form-control flex-1" rows="3" placeholder="Add a new note about this client...">{{ old('note') }}</textarea>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ti ti-send"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endcan

                            @if ($clientNotes->count() > 0)
                                <div class="space-y-3">
                                    @foreach ($clientNotes as $note)
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex justify-between items-start mb-2 gap-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium text-sm">{{ $note->admin->name ?? 'Admin' }}</span>
                                                    <span class="text-xs text-gray-500">{{ $note->created_at->diffForHumans() }}</span>
                                                </div>

                                                @can('edit', 'App\\Models\\Client')
                                                    <form method="POST" action="{{ route('dashboard.clients.notes.destroy', ['client' => $client, 'note' => $note]) }}"
                                                        onsubmit="return confirm('Delete this note?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-xs">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                            <p class="text-gray-700 mb-0">{{ $note->note }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <i class="ti ti-notes text-4xl text-gray-300 mb-2"></i>
                                    <p class="text-gray-500">No notes added yet</p>
                                    <p class="text-sm text-gray-400">Add internal notes about this client</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($activeTab === 'activities')
                        <div>
                            <h5 class="font-semibold text-gray-900 mb-4">Activity Log</h5>

                            @if ($clientActivities->count() > 0)
                                <div class="space-y-3">
                                    @foreach ($clientActivities as $activity)
                                        <div class="flex items-start gap-3 p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0
                                                {{ str_contains($activity->action, 'created') ? 'bg-green-100 text-green-600' : '' }}
                                                {{ str_contains($activity->action, 'updated') ? 'bg-blue-100 text-blue-600' : '' }}
                                                {{ str_contains($activity->action, 'deleted') ? 'bg-red-100 text-red-600' : '' }}
                                                {{ str_contains($activity->action, 'suspended') ? 'bg-yellow-100 text-yellow-600' : '' }}">
                                                @if (str_contains($activity->action, 'created'))
                                                    <i class="ti ti-plus text-lg"></i>
                                                @elseif (str_contains($activity->action, 'updated'))
                                                    <i class="ti ti-edit text-lg"></i>
                                                @elseif (str_contains($activity->action, 'deleted'))
                                                    <i class="ti ti-trash text-lg"></i>
                                                @elseif (str_contains($activity->action, 'suspended'))
                                                    <i class="ti ti-ban text-lg"></i>
                                                @else
                                                    <i class="ti ti-activity text-lg"></i>
                                                @endif
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-3">
                                                    <h6 class="font-medium text-gray-900">
                                                        {{ ucfirst(str_replace(['.', '_'], ' ', $activity->action)) }}
                                                    </h6>
                                                    <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                                                        {{ $activity->created_at->diffForHumans() }}
                                                    </span>
                                                </div>

                                                <p class="text-sm text-gray-600 mt-1">
                                                    {{ $activity->created_at->format('M j, Y \a\t g:i A') }}
                                                </p>

                                                @if ($activity->meta && is_array($activity->meta) && count($activity->meta) > 0)
                                                    <div class="mt-3 p-3 bg-gray-50 rounded-lg">
                                                        <p class="text-xs font-medium text-gray-700 mb-2">Details:</p>
                                                        <div class="grid grid-cols-1 gap-1">
                                                            @foreach ($activity->meta as $key => $value)
                                                                @if (!is_array($value) && !is_object($value))
                                                                    <div class="flex text-xs">
                                                                        <span class="font-medium text-gray-600 w-24 flex-shrink-0">
                                                                            {{ ucfirst(str_replace('_', ' ', $key)) }}:
                                                                        </span>
                                                                        <span class="text-gray-800">{{ $value }}</span>
                                                                    </div>
                                                                @elseif (is_array($value))
                                                                    <div class="flex text-xs">
                                                                        <span class="font-medium text-gray-600 w-24 flex-shrink-0">
                                                                            {{ ucfirst(str_replace('_', ' ', $key)) }}:
                                                                        </span>
                                                                        <span class="text-gray-800">{{ implode(', ', $value) }}</span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="ti ti-history text-2xl text-gray-400"></i>
                                    </div>
                                    <h6 class="text-lg font-medium text-gray-900 mb-2">No Activities Yet</h6>
                                    <p class="text-gray-500">Client activities and system events will appear here</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
