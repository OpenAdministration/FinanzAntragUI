@props([
    'id' => 0,
    'name' => 'ausgaben',
    'sum' => '0,00',
])
<x-antrag.table.row>
    <x-antrag.table.header>{{ $id }}</x-antrag.table.header>
    <x-antrag.table.header>
        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            <input type="text" placeholder="Name dieser Kategorie (= Postengruppe)" name="{{ $name }}-{{ $id }}-name" id="{{ $name }}-{{ $id }}-name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-lg sm:text-sm sm:leading-6">
        @endif
    </x-antrag.table.header>
    <x-antrag.table.header/>
    <x-antrag.table.header>
        <span class="sm:mx-2">&sum; &equals; {{ $sum }} &euro;</span>
    </x-antrag.table.header>
</x-antrag.table.row>
