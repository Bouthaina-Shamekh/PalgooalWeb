@push('styles')
    <link rel="stylesheet" href="{{ $previewView['stylesheetUrl'] }}">
@endpush

@include('front.layouts.partials.head', [
    'seo' => $previewView['seo'],
])

@if ($previewView['showFrontChrome'])
    @include('front.layouts.partials.header')
@endif

<div class="pc-container">
    <div class="pc-content">
        <div class="sections-preview-page" data-sections-preview-root
            data-highlight-section-id="{{ $previewView['highlightSectionId'] }}">
            <div class="sections-preview-shell">
                @if ($previewView['isTenantPagePreview'])
                    @include('tenant.partials.render-sections', $previewView['tenantHeaderRenderData'])
                @endif

                @forelse ($previewView['previewBlocks'] as $previewBlock)
                    <div id="{{ $previewBlock['domId'] }}" data-preview-section-id="{{ $previewBlock['id'] }}"
                        class="{{ $previewBlock['containerClass'] }}">
                        @if ($previewBlock['isHidden'])
                            <div class="sections-preview-state">{{ $previewBlock['hiddenStateLabel'] }}</div>
                        @endif

                        @include('front.pages.partials.definition-section', [
                            'section' => $previewBlock['section'],
                        ])
                    </div>
                @empty
                    <div class="sections-preview-empty">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900">{{ $previewView['emptyStateTitle'] }}</h2>
                            <p class="mt-3 text-sm leading-6">{{ $previewView['emptyStateDescription'] }}</p>
                        </div>
                    </div>
                @endforelse

                @if ($previewView['isTenantPagePreview'])
                    @include('tenant.partials.render-sections', $previewView['tenantFooterRenderData'])
                @endif
            </div>
        </div>
    </div>
</div>

@if ($previewView['showFrontChrome'])
    @include('front.layouts.partials.footer')
@endif

<script src="{{ $previewView['scriptUrl'] }}" defer></script>

@include('front.layouts.partials.end')
