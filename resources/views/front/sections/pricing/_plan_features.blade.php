<div class="p-6 flex-1 space-y-[14px]">

    {{-- Monthly features (always visible) --}}
    <div @if ($hasAnnualFeatures) x-show="!annual" @endif>
        @forelse ($features as $feature)
            <div class="flex items-center gap-3 {{ ! $loop->first ? 'mt-[14px]' : '' }}">
                <svg class="h-[14px] w-[18px] flex-shrink-0" fill="none" viewBox="0 0 27 21">
                    <path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z" fill="#BA112C"/>
                </svg>
                <span class="text-purple-brand text-base md:text-lg">{{ $feature }}</span>
            </div>
        @empty
            <p class="text-[#aaa] text-sm italic">{{ t('site.No_Features_Listed', 'No features listed.') }}</p>
        @endforelse
    </div>

    {{-- Annual features (only shown when toggle is annual AND list differs) --}}
    @if ($hasAnnualFeatures)
        <div x-show="annual" x-cloak>
            @foreach ($featuresAnnual as $feature)
                <div class="flex items-center gap-3 {{ ! $loop->first ? 'mt-[14px]' : '' }}">
                    <svg class="h-[14px] w-[18px] flex-shrink-0" fill="none" viewBox="0 0 27 21">
                        <path d="M8.4 15.9L2.1 9.6L0 11.7L8.4 20.1L26.4 2.1L24.3 0L8.4 15.9Z" fill="#BA112C"/>
                    </svg>
                    <span class="text-purple-brand text-base md:text-lg">{{ $feature }}</span>
                </div>
            @endforeach
        </div>
    @endif

</div>
