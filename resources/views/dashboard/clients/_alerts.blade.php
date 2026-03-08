@if (session('success'))
    <div class="alert alert-success mb-4">{{ session('success') }}</div>
@endif

@if (session('warning'))
    <div class="alert alert-warning mb-4">{{ session('warning') }}</div>
@endif

@if (session('error'))
    <div class="alert alert-danger mb-4">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0 ps-4">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
