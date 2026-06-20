<div class="bg-purple-brand px-6 py-5">
    <h3 class="text-white font-bold text-2xl md:text-[30px] uppercase text-center {{ $isFeatured ? 'pt-3' : '' }}">
        {{ $planTitle }}
    </h3>
    @if ($planDesc)
        <p class="text-[#d9d9d9] text-base text-center mt-1">{{ $planDesc }}</p>
    @endif

    @if ($monthlyPrice !== null)
        <div class="mt-4 pt-4 border-t border-white/20">
            <div class="flex items-end justify-center gap-1">
                <span class="text-white/70 text-xl font-semibold self-start mt-2.5">$</span>
                <span class="text-white text-5xl font-extrabold leading-none tabular-nums"
                    x-text="annual ? '{{ $monthlyEquiv ?? number_format($monthlyPrice, 2) }}' : '{{ number_format($monthlyPrice, 2) }}'">
                    {{ number_format($monthlyPrice, 2) }}
                </span>
                <span class="text-white/70 text-sm mb-1.5">/mo</span>
            </div>
            @if ($annualPrice && $yearlySaving)
                <p class="text-[#d9d9d9] text-xs text-center mt-2 transition-opacity duration-300 min-h-[16px]"
                    :class="annual ? 'opacity-100' : 'opacity-0'">
                    Billed ${{ number_format($annualPrice, 2) }}/yr &mdash;
                    <span class="text-white font-semibold">save ${{ $yearlySaving }}</span>
                </p>
            @else
                <div class="min-h-[16px]"></div>
            @endif
        </div>
    @endif
</div>
