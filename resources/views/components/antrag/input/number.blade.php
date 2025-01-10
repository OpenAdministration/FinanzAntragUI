@props([
    'name',
    'readonly' => false,
    'placeholder' => '',
    'value' => '',
    'min' => '0',
    'max' => '',
    'step' => '.01',
])
<x-antrag.form-group :$name label="{{ $slot }}">
    <input type="number" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" name="{{ $name }}" id="{{ $name }}" @if($readonly) readonly @endif placeholder="{{ $placeholder }}" value="{{ $value }}"
        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">
</x-antrag.form-group>
