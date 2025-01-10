<x-antrag.row>
    <x-antrag.form-group label="{{ $label }}" :$name>
        <input type="text" wire:model="values.0"
               class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">

        @for($i = 1,$iMax = count($values);$i < $iMax; $i++)
            <div class="mt-1" wire:key="values.{{$i}}">
                <input type="text" wire:model="values.{{$i}}"
                       class="inline-block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">
                <button type="button" wire:click="removeValue({{$i}})"
                        class="inline-flex px-3 pt-1 py-3 text-sm font-semibold text-white bg-red-300 rounded-md shadow-sm hover:bg-red-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                    <x-fas-minus class="pt-2"/>
                </button>
            </div>
        @endfor
        <!-- delete this input button -->


        <!-- add another input button -->
        <div class="mt-1">
            <button type="button" wire:click="addValue()"
                    class="inline-flex justify-center px-3 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-md shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                <x-fas-plus/>&nbsp;Eine weitere Zeile hinzufügen
            </button>
        </div>
    </x-antrag.form-group>
</x-antrag.row>
