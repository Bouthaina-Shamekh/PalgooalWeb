@props([
  'label' => '',
  'name',
  'options' => [],
])

<div {{ $attributes->merge(['class' => 'mb-3']) }}>
  @if($label)
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
  @endif
  <select id="{{ $name }}"
  name="{{ $name }}"
  {{ $attributes->merge(['class' => 'form-select']) }}>
  <option value="">— Select —</option>
  @foreach($options as $value => $text)
  <option value="{{ $value }}">{{ $text }}</option>
    @endforeach
  </select>
  @error($name)
    <span class="text-red-600 text-sm">{{ $message }}</span>
  @enderror
</div>
