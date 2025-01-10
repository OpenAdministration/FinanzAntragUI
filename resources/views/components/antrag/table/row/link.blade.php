@props([
    'href',
])
<x-antrag.table.row>
    <x-antrag.table.cell />
    <x-antrag.table.cell>
        <a href="{{ $href }}" class="text-lg hover:text-indigo-600 hover:underline"><x-fas-plus class="inline mx-2 -mt-1"/>{{ $slot }}</a>
    </x-antrag.table.cell>
    <x-antrag.table.cell />
    <x-antrag.table.cell />
</x-antrag.table.row>
