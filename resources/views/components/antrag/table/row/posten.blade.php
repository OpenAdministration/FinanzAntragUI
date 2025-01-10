@props([
    'name',
    'id',
])
<x-antrag.table.row>
    <x-antrag.table.cell>{{ $id }}</x-antrag.table.cell>
    <x-antrag.table.cell>
        <input type="text" placeholder="Name dieses Postens" name="{{ $name }}-{{ $id }}-name" id="{{ $name }}-{{ $id }}-name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-lg sm:text-sm sm:leading-6">
    </x-antrag.table.cell>
    <x-antrag.table.cell>
        <input type="text" placeholder="Platz für Details" name="{{ $name }}-{{ $id }}-beschreibung" id="{{ $name }}-{{ $id }}-beschreibung" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-lg sm:text-sm sm:leading-6">
    </x-antrag.table.cell>
    <x-antrag.table.cell>
        <input type="number" min="0.00" step=".01" placeholder="Höhe des Postens in Euro" name="{{ $name }}-{{ $id }}-betrag" id="{{ $name }}-{{ $id }}-betrag" class="inline-block w-16 sm:w-24 md:w-32 lg:w-64 rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
    </x-antrag.table.cell>
</x-antrag.table.row>
