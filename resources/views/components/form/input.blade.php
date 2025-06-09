@props([
  'label' => '',
  'type'  => 'text',
  'name'  => null,
])


<div class="mb-3">
  @if($label)
    <label for="{{ $name }}" class="form-label capitalize">{{ $label }}</label>
  @endif

  <input
    id="{{ $name }}"
    name="{{ $name }}"
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'form-control']) }}
    placeholder="{{ $attributes->get('placeholder', 'Enter ' . $label) }}"
  />
    
</div>
