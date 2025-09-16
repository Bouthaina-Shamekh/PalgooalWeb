<x-client-layout>
    {{-- resources/views/client/domains/search-results.blade.php --}}

    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript: void(0)">Clients</a></li>
                <li class="breadcrumb-item" aria-current="page">Domain Search Results</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Search Results for "{{ $domain }}"</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->

    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">

            <!-- Main Domain Result -->
            <div class="card mb-3">
                <div class="card-body">
                    @if ($domain_available)
                        <div class="alert alert-success flex items-center" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" style="display: none">
                                <symbol id="check-circle-fill" fill="currentColor" viewBox="0 0 16 16">
                                    <path
                                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                                </symbol>
                            </svg>
                            <svg class="bi flex-shrink-0 me-2" width="24" height="24">
                                <use xlink:href="#check-circle-fill"></use>
                            </svg>
                            <div>The domain "{{ $domain }}" is available!</div>
                        </div>

                        <div class="text-center mt-3">
                            <form method="GET" action="{{ route('client.domains.buy') }}">
                                <input type="hidden" name="domain" value="{{ $domain }}">
                                <button type="submit" class="btn btn-success">
                                    Buy Now - ${{ $domain_extensions[$domain_extension] ?? 10 }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-danger flex items-center" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" style="display: none">
                                <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
                                    <path
                                        d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                                </symbol>
                            </svg>
                            <svg class="bi flex-shrink-0 me-2" width="24" height="24">
                                <use xlink:href="#exclamation-triangle-fill"></use>
                            </svg>
                            <div>The domain "{{ $domain }}" is not available</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Alternative Extensions -->
            @if (!$domain_available && count($alternative_extensions) > 0)
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Available with Other Extensions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($alternative_extensions as $alt)
                                <div class="col-md-4 mb-3">
                                    <div class="text-center">
                                        <h6>{{ $alt['domain'] }}</h6>
                                        <span class="text-lg btn btn-outline-success">${{ $alt['price'] }}
                                            {{ $alt['extension'] }}</span>
                                        <div class="mt-2">
                                            <form method="GET" action="{{ route('client.domains.buy') }}">
                                                <input type="hidden" name="domain" value="{{ $alt['domain'] }}">
                                                <button type="submit" class="btn btn-primary btn-sm">Buy</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Alternative Domain Names -->
            @if (!$domain_available && count($alternative_names) > 0)
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Similar Available Domains</h5>
                    </div>
                    <div class="card-body">
                        <span class="text-lg">Please try another domain Example:</span>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach ($alternative_names as $alt_name)
                                <form method="GET" action="{{ route('client.domains.buy') }}"
                                    style="display: inline;">
                                    <input type="hidden" name="domain" value="{{ $alt_name }}">
                                    <button type="submit"
                                        class="btn btn-outline-success btn-sm">{{ $alt_name }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            <div class="text-center">
                <a href="{{ route('client.domains.search') }}" class="btn btn-secondary">Search Again</a>
                <a href="{{ route('client.domains.index') }}" class="btn btn-outline-primary">My Domains</a>
            </div>

        </div>
    </div>
    <!-- [ Main Content ] end -->
</x-client-layout>
