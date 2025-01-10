@props([
    'name',
    'label',
])
<label for="{{ $name }}" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">{{ $label }}</label>
<div class="mt-2 sm:col-span-2 sm:mt-0">
    {{ $slot }}
</div>
