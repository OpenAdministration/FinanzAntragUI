@props([
    'label' => '',
])
<div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
    <dt class="text-sm font-medium leading-3 text-gray-900">{{ $label }}</dt>
    <dd class="mt-1 text-sm leading-3 text-gray-700 sm:col-span-2 sm:mt-0">
        {{ $slot }}
    </dd>
</div>
