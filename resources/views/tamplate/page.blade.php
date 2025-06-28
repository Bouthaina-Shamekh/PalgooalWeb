<x-tamplate.layouts.index-layouts
    title="بال قول لتكنولوجيا المعلومات - مواقع الكترونية واستضافة عربية"
    description="شركة فلسطينية متخصصة في برمجة وتصميم المواقع الالكترونية تقدم خدمات استضافة مواقع، حجز دومين،مواقع ووردبريس،اعلانات جوجل،تحسين محركات البحث"
    keywords="خدمات حجز دومين , افضل شركة برمجيات , استضافة مواقع , استضافة مشتركة , شركة استضافة مواقع , شركات استضافة مواقع , افضل شركة برمجة, خدمة كتابة محتوى , تحسين محركات البحث , web hosting service , shared hosting , best wordpress hosting , web hosting company, domain registration services , best IT company , information technology company , content writing service , best SEO services"
    ogImage="{{ asset('assets/images/services.jpg') }}"
>
<div class="container mx-auto py-10">
        <h1 class="text-3xl font-bold mb-6">
            {{ $page->translation()?->title ?? 'عنوان غير متوفر' }}
        </h1>

        <div class="prose max-w-4xl">
            {!! $page->translation()?->content ?? '<p>لا يوجد محتوى.</p>' !!}
        </div>
    </div>

@php
    $heroSection = $page->sections->where('key', 'hero')->first();
    $hero = $heroSection?->translation()?->content ?? null;
@endphp

@if ($hero)
    @php
  $heroSection = $page->sections->where('key', 'hero')->first();
  $translation = $heroSection?->translation();

  $heroData = [
      'title' => $translation?->title ?? '',
      'subtitle' => $translation?->content['subtitle'] ?? '',
      'button_text' => $translation?->content['button_text'] ?? '',
      'button_url' => $translation?->content['button_url'] ?? '#',
  ];
@endphp
<x-tamplate.sections.hero :data="$heroData" />
@endif
</x-tamplate.layouts.index-layouts>




