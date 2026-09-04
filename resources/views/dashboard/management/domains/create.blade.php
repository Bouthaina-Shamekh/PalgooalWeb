<x-dashboard-layout>
    <div class="container mx-auto py-6 max-w-5xl">
        <h1 class="text-2xl font-bold mb-2">➕ Add Domain</h1>

        {{-- TLD-3E.4A: create-only clarification. This note lives here (not in the shared
             _form.blade.php partial) so it never appears on the Edit screen. --}}
        <p class="text-sm text-gray-500 mb-6">
            {{ __('هذا السجل يُنشأ كنطاق خارجي غير مُدار. يمكن ربطه بمزوّد لاحقًا من خلال إجراء التسجيل.') }}
        </p>

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-4 mb-6 rounded">
                <ul class="list-disc ps-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('dashboard.domains.store') }}" method="POST" enctype="multipart/form-data"
            class="grid grid-cols-12 gap-x-6">
            @csrf

            @include('dashboard.management.domains._form')

        </form>
    </div>
</x-dashboard-layout>
