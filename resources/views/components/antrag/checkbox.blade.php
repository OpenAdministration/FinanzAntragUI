@props([
    'name' => $attributes->thatStartWith('wire:model')->first(),
    'label',
])
<div class="relative flex gap-x-3">
    <div class="flex items-center h-6">
        <input id="{{ $name }}" name="{{ $name }}" type="checkbox" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600" {{ $attributes }}>
    </div>
    <div class="text-sm leading-6">
        <label for="{{ $name }}" class="font-medium text-gray-900">{{ $label }}</label>
        <p class="mt-1 text-gray-600">
            {{ $slot }}
        </p>
    </div>
</div>
