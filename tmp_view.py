from pathlib import Path
text = Path("resources/views/tamplate/page.blade.php").read_text(encoding="utf-8")
start = text.find("@foreach ($page->sections as $section)")
end = text.find("@endforeach", start) + len("@endforeach")
print(text[start:end].encode('utf-8'))
