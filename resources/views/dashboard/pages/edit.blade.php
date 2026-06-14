<x-dashboard-layout>
    <div class="page-header">
        <div class="page-block">
            <ul class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.home') }}">{{ t('dashboard.Home', 'الرئيسية') }}</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard.pages.index') }}">{{ t('dashboard.All_Pages', 'الصفحات') }}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page">
                    {{ t('dashboard.Edit_Page', 'تعديل الصفحة') }}
                </li>
            </ul>
            <div class="page-header-title">
                <h2 class="mb-0">
                    {{ t('dashboard.Edit_Page', 'تعديل الصفحة') }}
                    @php $pageTitle = $page->translation()?->title; @endphp
                    @if ($pageTitle)
                        <span class="text-muted fw-normal fs-5">— {{ $pageTitle }}</span>
                    @endif
                </h2>
            </div>
        </div>
    </div>

    @if (session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('dashboard.pages.update', $page) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-12 gap-6">
            @include('dashboard.pages.partials.form', [
                'page'      => $page,
                'languages' => $languages,
            ])
        </div>
    </form>
</x-dashboard-layout>
