{{--
    _plan_header_dynamic.blade.php
    ──────────────────────────────
    Variables expected:
      $planTitle      string
      $planDesc       string
      $monthlyPrice   float|null   (dollars)
      $annualPrice    float|null   (total per year, dollars)
      $monthlyEquiv   string|null  (annualPrice / 12, formatted)
      $yearlySaving   int          (dollars saved per year)
      $discountPercent int         (0–100)
      $hasDiscount    bool
      $isFeatured     bool

    Alpine context: `annual` (bool) from parent x-data="{ annual: false }"
--}}
<div class="bg-purple-brand px-6 py-5">

    {{-- Plan name --}}
    <h3 class="text-white font-bold text-2xl md:text-[30px] uppercase text-center {{ $isFeatured ? 'pt-3' : '' }}">
        {{ $planTitle }}
    </h3>

    {{-- Plan short description --}}
    @if ($planDesc)
        <p class="text-[#d9d9d9] text-base text-center mt-1">{{ $planDesc }}</p>
    @endif

    {{-- ── Price block ──────────────────────────────────────────────────── --}}
    @if ($monthlyPrice !== null)
        <div class="mt-4 pt-4 border-t border-white/20">

            {{-- Main price figure — switches between monthly and annual/12 --}}
            <div class="flex items-end justify-center gap-1">
                <span class="text-white/70 text-xl font-semibold self-start mt-2.5">$</span>
                <span class="text-white text-5xl font-extrabold leading-none tabular-nums"
                    x-text="annual
                        ? '{{ $monthlyEquiv ?? number_format($monthlyPrice, 2) }}'
                        : '{{ number_format($monthlyPrice, 2) }}'">
                    {{ number_format($monthlyPrice, 2) }}
                </span>
                <span class="text-white/70 text-sm mb-1.5">
                    {{ t('site.Per_Month', '/mo') }}
                </span>
            </div>

            {{-- ── Annual billing sub-line ─────────────────────────────── --}}
            {{-- Appears only in annual mode.                               --}}
            {{-- If no annual price at all: reserve minimum height.        --}}
            <div class="text-center mt-2 min-h-[18px]">
                @if ($annualPrice)

                    <p class="text-[#d9d9d9] text-xs transition-opacity duration-300"
                       x-show="annual" x-cloak>

                        {{-- "Billed $191.88 / year" --}}
                        {{ strtr(
                            t('site.Billed_Per_Year', 'Billed $:amount / year'),
                            [':amount' => number_format($annualPrice, 2)]
                        ) }}

                        @if ($hasDiscount && $yearlySaving > 0)
                            &mdash;
                            <span class="text-white font-semibold">
                                {{-- "save $72" --}}
                                {{ strtr(
                                    t('site.Save_Per_Year', 'save $:amount'),
                                    [':amount' => $yearlySaving]
                                ) }}
                            </span>
                        @endif
                    </p>

                @endif
            </div>
            {{-- ── End annual billing sub-line ────────────────────────── --}}

        </div>
    @endif
    {{-- ── End price block ────────────────────────────────────────────── --}}

</div>
