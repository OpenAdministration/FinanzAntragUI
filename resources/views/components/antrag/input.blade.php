@props([
    'name' => $attributes->thatStartWith('wire:model')->first(),
    'readonly' => false,
    'placeholder' => '',
    'value' => '',
])

<x-antrag.form-group :$name label="{{ $slot }}">
    <input type="text" name="{{ $name }}" id="{{ $name }}" {{ $attributes }} @if($readonly) readonly @endif placeholder="{{ $placeholder }}" value="{{ $value }}"
        class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">
        <div>
            @error($name) <span class="error">{{ $message }}</span> @enderror
        </div>
</x-antrag.form-group>
