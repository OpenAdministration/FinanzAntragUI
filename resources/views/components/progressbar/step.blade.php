@props([
    'completed' => false,
    'valid' => false,
    'active' => false
])
<li class="relative pr-16 sm:pr-24 md:pr-[19%] last:pr-0">
    <!-- Completed Step -->
    <div class="absolute inset-0 flex items-center" aria-hidden="true">
        <div @class(["h-0.5 w-full", "bg-black" => $completed, "bg-gray-200" => !$completed])></div>
    </div>
    <a href="#" @class([
        "relative flex items-center justify-center w-8 h-8 border-2 rounded-full",
        "border-black" => ($completed || $active),
        "bg-green-600 hover:bg-green-400" => ($completed && $valid),
        "bg-red-500 hover:bg-red-400" => ($completed && !$valid),
        "bg-white" => (!$completed),
        "border-gray-300 group hover:border-gray-400" => (!$completed && !$active)
        ])>
        <x-fas-check @class(["w-5 h-5 text-white", "hidden" => !($completed && $valid)])/>
        <x-fas-exclamation @class(["w-5 h-5 text-white", "hidden" => !($completed && !$valid)])/>
        <x-fas-circle @class([
            "w-2.5 h-2.5",
            "text-indigo-600" => $active,
            "text-transparent group-hover:text-gray-300" => !$active,
            "hidden" => $completed])/>
        <span class="sr-only">{{ $slot }}</span>
    </a>
</li>
