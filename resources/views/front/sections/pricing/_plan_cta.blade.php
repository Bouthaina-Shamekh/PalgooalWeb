<div class="p-6 pt-0 flex justify-center">
    <a :href="annual ? '{{ $ctaUrl }}&plan_sub_type=annual' : '{{ $ctaUrl }}&plan_sub_type=monthly'"
        href="{{ $ctaUrl }}&plan_sub_type=monthly"
        class="bg-red-brand text-white text-center py-[15px] px-10 rounded-[12px] text-lg md:text-xl font-medium hover:bg-red-brand/90 transition-all duration-300 hover:-translate-y-0.5 capitalize">
        {{ t('site.Choose_Now', 'Choose now') }}
    </a>
</div>
