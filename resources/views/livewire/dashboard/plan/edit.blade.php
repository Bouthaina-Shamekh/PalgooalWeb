    <!-- [ breadcrumb ] start -->
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="../dashboard/index.html">Home</a></li>
                <li class="breadcrumb-item"><a href="javascript: void(0)">Plans</a></li>
                <li class="breadcrumb-item" aria-current="page">Plans Edit</li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">Plans Edit - {{ $plan['name'] }}</h2>
            </div>
        </div>
    </div>
    <!-- [ breadcrumb ] end -->
    <!-- [ Main Content ] start -->
    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    {{--Success messages--}}
                    @if (session()->has('success'))
                    <div class="alert alert-success" role="alert">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <form wire:submit.prevent="save" class="grid grid-cols-12 gap-x-6">
                        <!-- First & Last Name -->
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="name" wire:model="plan.name" label="Name" />
                            @error('plan.name') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-6">
                            <x-form.input name="price" type="number" step="0.01" min="0" required wire:model="plan.price" label="Price" />
                            @error('plan.price') <span class="text-red-600">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-span-12 md:col-span-12">
                            <x-form.input name="features" type="text" wire:keydown.enter="addFeature" wire:model="plan.feature" label="Features" />
                            @foreach ($plan['features'] as $feature)
                                <span class="badge bg-success-500/10 text-success-500 rounded-full text-sm">
                                    {{ $feature }}
                                    <button type="button" wire:click="removeFeature({{ $loop->index }})" class="btn btn-danger btn-sm ms-2">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </span>
                            @endforeach
                        </div>
                        <div class="col-span-12 text-right">
                            <button type="button" wire:click="showIndex" class="btn btn-secondary">Cancel</button>
                            <button type="button" wire:click="save" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- [ Main Content ] end -->
