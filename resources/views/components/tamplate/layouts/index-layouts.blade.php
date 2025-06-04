
@props([
  'title' => 'بال قول - حلول رقمية متكاملة',
  'description' => 'أنشئ موقعك بسهولة واحترف التواجد الرقمي مع خدمات بال قول.',
  'keywords' => 'استضافة, دومين, تصميم مواقع, WordPress, بال قول',
  'ogImage' => asset('assets/images/default-og.jpg'),
])

@include('tamplate.layouts.head', [
  'title' => $title,
  'description' => $description,
  'keywords' => $keywords,
  'ogImage' => $ogImage
])
@include('tamplate.layouts.header')
{{ $slot }}

<!-- [ Footer ] start -->
@include('tamplate.layouts.footer')
<!-- [ Footer ] end -->
<!-- [ Customizer ] start -->
@include('tamplate.layouts.end')
<!-- [ Customizer ] end -->
