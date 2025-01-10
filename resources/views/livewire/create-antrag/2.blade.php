<div class="mt-8 sm:mx-8 lg:px-8">
    <!-- Fortschrittsanzeige -->
    <x-progressbar>
        <x-progressbar.step completed valid>Step 1</x-progressbar.step>
        <x-progressbar.step active>Step 2</x-progressbar.step>
        <x-progressbar.step>Step 3</x-progressbar.step>
        <x-progressbar.step>Step 4</x-progressbar.step>
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
                <h2 class="text-base font-semibold leading-7 text-gray-900">Allgemeine Angaben</h2>

                <div class="pb-12 mt-10 space-y-8 border-b border-gray-900/10 sm:space-y-0 sm:divide-y sm:divide-gray-900/10 sm:border-t sm:pb-0">
                    <x-antrag.row>
                        <x-antrag.input name="project-name">Projektname</x-antrag.input>
                    </x-antrag.row>

                    <!-- Date picker -->

                    <x-antrag.row>
                        <label for="project-start-date" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Projektstart</label></label>
                        <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <div class="flex -space-x-px">
                                <div class="flex-1 w-1/2 min-w-0">
                                    <!--label for="project-start-date" class="sr-only">Startdatum</label-->
                                    <input type="date" name="project-start-date" id="project-start-date" class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">
                                </div>
                            </div>
                        </div>

                        <label for="project-start-date" class="block text-sm font-medium leading-6 text-gray-900 sm:pt-1.5">Projektende</label></label>
                        <div class="mt-2 sm:col-span-2 sm:mt-0">
                            <div class="flex -space-x-px">
                                <div class="flex-1 w-1/2 min-w-0">
                                    <!--label for="project-end-date" class="sr-only">Enddatum</label-->
                                    <input type="date" name="project-end-date" id="project-end-date" class="relative block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6">
                                </div>
                            </div>
                        </div>
                    </x-antrag.row>

                    <!-- WYSIWYG Editor -->

                    <x-antrag.row>
                        <x-antrag.textarea name="about">Projektbeschreibung</x-antrag.textarea>
                    </x-antrag.row>

                    <!-- TODO: File Upload -->

                    <!-- Checkbox List -->

                    <fieldset>
                        <legend class="sr-only">Aufgaben der Studierendenschaft</legend>
                        <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:py-6">
                            <div class="text-sm font-semibold leading-6 text-gray-900" aria-hidden="true">Aufgaben der Studierendenschaft</div>
                            <div class="mt-4 sm:col-span-2 sm:mt-0">
                                <div class="max-w-lg space-y-6">
                                    <x-antrag.checkbox name="aufgaben-meinungsbildung" label="Meinungsbildung">
                                        die Meinungsbildung in der Gruppe der Studierenden zu ermöglichen
                                    </x-antrag.checkbox>
                                    <x-antrag.checkbox name="aufgaben-gesellschaft" label="Hochschule und Gesellschaft">
                                        die Belange ihrer Mitglieder in Hochschule und Gesellschaft wahrzunehmen
                                    </x-antrag.checkbox>
                                    <x-antrag.checkbox name="aufgaben-hochschule" label="Erfüllung der Aufgaben der Hochschule">
                                        an der Erfüllung der Aufgaben der Hochschule (§§ 3 und 4) insbesondere durch Stellungnahmen zu hochschul- oder wissenschaftspolitischen Fragen mitzuwirken
                                    </x-antrag.checkbox>
                                    <x-antrag.checkbox name="aufgaben-politik" label="politische Bildung">
                                        auf der Grundlage der verfassungsmäßigen Ordnung die politische Bildung, das staatsbürgerliche Verantwortungsbewusstsein und die Bereitschaft ihrer Mitglieder zur aktiven Toleranz sowie zum Eintreten für die Grund- und Menschenrechte zu fördern
                                    </x-antrag.checkbox>
                                    <x-antrag.checkbox name="aufgaben-kultur" label="Kultur & Soziales">
                                        kulturelle, fachliche, wirtschaftliche und soziale Belange ihrer Mitglieder wahrzunehmen
                                    </x-antrag.checkbox>
                                    <x-antrag.checkbox name="aufgaben-integration" label="Integration ausländischer Studierender">
                                        die Integration ausländischer Studierender zu fördern
                                    </x-antrag.checkbox>
                                    <x-antrag.checkbox name="aufgaben-sport" label="Sport">
                                        den Studentensport zu fördern
                                    </x-antrag.checkbox>
                                    <x-antrag.checkbox name="aufgaben-internationales" label="Internationales">
                                        die überregionalen und internationalen Studierendenbeziehungen zu pflegen
                                    </x-antrag.checkbox>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sonstige Eingabefelder -->

                    <x-antrag.row>
                        <x-antrag.input name="target-group">Zielgruppe</x-antrag.input>
                    </x-antrag.row>

                    <x-antrag.row>
                        <x-antrag.input name="estimated-guests">Erwartete Teilnehmendenzahl</x-antrag.input>
                        <x-antrag.input name="estimated-students">Davon Studierende</x-antrag.input>
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
