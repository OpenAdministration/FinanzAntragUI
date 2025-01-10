@props([
    'name' => $attributes->thatStartWith('wire:model')->first(),
    'label',
    'id',
])
<div class="relative flex gap-x-3">
    <div class="flex items-center h-6">
        <input id="{{ $id }}" name="{{ $name }}" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-600" {{ $attributes }}>
    </div>
    <div class="text-sm leading-6">
        <label for="{{ $id }}" class="font-medium text-gray-900">{{ $label ?? $slot }}</label>
        <p class="mt-1 text-gray-600">
            {{ isset($label) ? $slot : '' }}
        </p>
    </div>
</div>
