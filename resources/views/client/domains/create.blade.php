<x-client-layout>
    <div class="container mx-auto py-6 max-w-5xl">
        <h1 class="text-2xl font-bold mb-6">➕ Add Domain</h1>

        @if ($errors->any())
            <div class="bg-red-100 text-red-800 p-4 mb-6 rounded">
                <ul class="list-disc ps-6">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('client.domains.store') }}" method="POST" enctype="multipart/form-data"
            class="grid grid-cols-12 gap-x-6">
            @csrf

            @include('client.domains._form')

        </form>
    </div>
</x-client-layout>
