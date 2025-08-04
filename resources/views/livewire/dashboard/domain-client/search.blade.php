
    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript: void(0)">Clients</a></li>
                <li class="breadcrumb-item" aria-current="page">Domain Name Search</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Domain Name Search</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->

    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-body">
                    {{--Success messages--}}
                    @if (session()->has('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                    @endif

                    <form wire:submit.prevent="save" class="grid grid-cols-12 gap-x-6">
                        @csrf
                        <div class="col-span-12">
                            <div class="input-group btn-group mb-4">
                                <input type="text" class="form-control" placeholder="Domain Name" wire:model="domain_name" />
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @foreach ($domain_extensions as $extension => $price)
                                        <li><a class="dropdown-item" wire:click="setExtension({{ $extension }})">{{ $extension }} - {{ $price }}$</a></li>
                                    @endforeach
                                    <li>
                                        <hr class="dropdown-divider" />
                                    </li>
                                    <li>
                                        <input type="text" class="form-control dropdown-item" placeholder=".com" wire:change="setExtension($event.target.value)">
                                    </li>
                                </ul>
                                <button class="btn btn-outline-success dropdown-toggle" type="button" data-pc-toggle="dropdown" aria-expanded="false">
                                    {{ $domain_extension }}
                                </button>
                            </div>
                            @if($domain_check)
                            <div class="col-span-12 mt-2">
                                <div class="alert alert-{{ $domain_available ? 'success' : 'danger' }} flex items-center" role="alert">
                                  <svg xmlns="http://www.w3.org/2000/svg" style="display: none">
                                    <symbol id="info-fill" fill="currentColor" viewBox="0 0 16 16">
                                      <path
                                        d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"
                                      ></path>
                                    </symbol>
                                  </svg>
                                  <svg class="bi flex-shrink-0 me-2" width="24" height="24">
                                    <use xlink:href="#info-fill"></use>
                                  </svg>
                                  <div>{{ $domain_available ? 'The domain "' . $domain . '" is available' : 'The domain "' . $domain . '" is not available' }}</div>
                                </div>
                                <div class="mt-2">
                                    @if(!$domain_available && count($domain_extensions_available) > 0)
                                        <span class="text-lg">But you can buy it for</span>
                                        @foreach ($domain_extensions_available as $extension)
                                            <span class="text-lg btn btn-outline-success">{{ $domain_extensions[$extension] }}$ {{ $extension }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-lg">Please try another domain Example:</span>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($domain_names_available as $domain_name)
                                                <span class="text-sm btn btn-outline-success">{{ $domain_name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                        <div class="col-span-12 text-right">
                            <button type="button" wire:click="showIndex" class="btn btn-secondary">Cancel</button>
                            <button type="button" wire:click="search" class="btn btn-primary">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
