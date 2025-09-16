<div class="col-span-12 md:col-span-6">
    <label for="client_id">Client</label>
    <select name="client_id" id="client_id" class="form-select">
        <option value="">Select Client</option>
        @foreach ($clients as $client)
            <option value="{{ $client->id }}" @selected($domain->client_id == $client->id)>{{ $client->first_name }}
                {{ $client->last_name }}</option>
        @endforeach
    </select>
</div>
<div class="col-span-12 md:col-span-6">
    <div class="mb-3">
        <x-form.input label="Domain Name" :value="$domain->domain_name" name="domain_name" type="text"
            placeholder="e.g. example.com or client.palgoals.com" />
    </div>
</div>
<div class="col-span-12 md:col-span-6">
    <div class="mb-3">
        <label for="registrar" class="form-label">Registrar Domain</label>
        <select id="registrar" name="registrar" class="form-select">
            <option value="" @selected($domain->registrar == '')>-- Select Registrar Domain --</option>
            <option value="enom" @selected($domain->registrar == 'enom')>enom</option>
            <option value="namcheap" @selected($domain->registrar == 'namcheap')>namcheap</option>
        </select>
    </div>
</div>
<div class="col-span-12 md:col-span-6">
    <div class="mb-3">
        <x-form.input label="Registration Date" :value="$domain->registration_date" name="registration_date" type="date"
            placeholder="Registration Date" />
    </div>
</div>
<div class="col-span-12 md:col-span-6">
    <div class="mb-3">
        <x-form.input label="Renewal Date" :value="$domain->renewal_date" name="renewal_date" type="date"
            placeholder="Renewal Date" />
    </div>
</div>
<div class="col-span-12 md:col-span-6">
    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select">
            <option value="">-- Select Registrar Domain --</option>
            <option value="active" @selected($domain->status == 'active')>active</option>
            <option value="expired" @selected($domain->status == 'expired')>expired</option>
            <option value="pending" @selected($domain->status == 'pending')>pending</option>
        </select>
    </div>
</div>
<div class="col-span-12 text-right">
    <a href="{{ route('dashboard.domains.index') }}" class="btn btn-secondary">Cancel</a>
    <button type="submit" class="btn btn-primary">Submit</button>
</div>
