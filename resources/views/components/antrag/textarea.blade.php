@props([
    'name',
])
<x-antrag.form-group :$name label="{{$slot}}">
    <textarea :$name id="$name" rows="3" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 md:max-w-2xl sm:text-sm sm:leading-6"></textarea>
    <!--p class="mt-3 text-sm leading-6 text-gray-600">Write a few sentences about yourself.</p-->
</x-antrag.form-group>
