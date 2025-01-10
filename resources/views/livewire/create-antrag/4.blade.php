<div class="mt-8 sm:mx-8 lg:px-8">
    <!-- Fortschrittsanzeige -->
    <x-progressbar>
        <x-progressbar.step completed valid>Step 1</x-progressbar.step>
        <x-progressbar.step completed valid>Step 2</x-progressbar.step>
        <x-progressbar.step completed valid>Step 3</x-progressbar.step>
        <x-progressbar.step active>Step 4</x-progressbar.step>
        <x-progressbar.step>Step 5</x-progressbar.step>
    </x-progressbar>

    <!-- Überschrift -->

    <div class="mx-auto mt-4 sm:mt-6 lg:mt-8">
        <div class="max-w-2xl lg:mx-0">
            <h2 class="text-2xl font-medium tracking-tight text-gray-900 uppercase sm:text-4xl">
                StuRa: Externes Projekt
            </h2>
        </div>
    </div>

    <!-- Formular -->

    <form class="mt-4">
        @csrf
        <div class="space-y-12 sm:space-y-16">
            <div>


                <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">

                    <x-antrag.row>
                        <x-antrag.input.number name="stura-funding" readonly value="72.33">StuRa-Förderung €</x-antrag.input.number>
                    </x-antrag.row>

                    <x-antrag.row>
                        Vorkasse beantragen:
                        <x-toggle></x-toggle>
                    </x-antrag.row>

                    <x-antrag.row>
                        <x-antrag.input.number name="advance-payment" max="72.33" value="0">
                            Höhe der Vorkasse in €
                        </x-antrag.input.number>
                    </x-antrag.row>


                    <x-antrag.row>
                        <x-antrag.form-group name="attachments" label="Weitere Anhänge">

                            <!-- Additional File Uploads -->

                        </x-antrag.form-group>
                    </x-antrag.row>

                    <!-- Buttons -->

                    <x-antrag.row>
                        <div></div>
                        <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <x-antrag.button.light wire:click="previousPage()">Zurück</x-antrag.button.light>
                            <x-antrag.button.primary wire:click="nextPage()">Weiter</x-antrag.button.primary>
                        </div>
                    </x-antrag.row>

                </div>
            </div>
        </div>

        <div class="flex items-center justify-end mt-6 mb-4 gap-x-6">
            <x-antrag.button.light>Antrag abbrechen</x-antrag.button.light>
            <x-antrag.button.primary>Entwurf speichern</x-antrag.button.primary>
        </div>
    </form>
</div>
