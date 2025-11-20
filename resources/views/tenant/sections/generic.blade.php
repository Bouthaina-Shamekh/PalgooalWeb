<section class="border rounded-2xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <div>
            <p class="text-xs uppercase text-gray-400 mb-1">{{ $section->key ?? 'Section' }}</p>
            <h2 class="text-xl font-semibold">{{ $translation->title ?? __('قسم بدون عنوان') }}</h2>
        </div>
        <span class="text-xs text-gray-300">#{{ $section->sort_order }}</span>
    </div>

    @php
        $isArray = is_array($content);
    @endphp

    @if ($isArray)
        <div class="space-y-3 text-sm text-gray-600">
            @foreach ($content as $key => $value)
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400">{{ is_string($key) ? $key : 'item' }}</p>
                    <pre class="whitespace-pre-wrap text-sm">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $value }}</pre>
                </div>
            @endforeach
        </div>
    @elseif(!empty($content))
        <p class="text-sm text-gray-600">{{ $content }}</p>
    @else
        <p class="text-sm text-gray-400">{{ __('لا توجد بيانات بعد لهذا القسم.') }}</p>
    @endif
</section>
