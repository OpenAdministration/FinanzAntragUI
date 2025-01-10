@props(['file', 'size'])

<li class="flex items-center justify-between py-4 pl-4 pr-5 text-sm leading-6">
  <div class="flex items-center flex-1 w-0">
    <svg class="flex-shrink-0 w-5 h-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
      <path fill-rule="evenodd" d="M15.621 4.379a3 3 0 00-4.242 0l-7 7a3 3 0 004.241 4.243h.001l.497-.5a.75.75 0 011.064 1.057l-.498.501-.002.002a4.5 4.5 0 01-6.364-6.364l7-7a4.5 4.5 0 016.368 6.36l-3.455 3.553A2.625 2.625 0 119.52 9.52l3.45-3.451a.75.75 0 111.061 1.06l-3.45 3.451a1.125 1.125 0 001.587 1.595l3.454-3.553a3 3 0 000-4.242z" clip-rule="evenodd" />
    </svg>
    <div class="flex flex-1 min-w-0 gap-2 ml-4">
      <span class="font-medium truncate">{{ $file }}</span>
      <span class="flex-shrink-0 text-gray-400">{{ $size }}</span>
    </div>
  </div>
  <div class="flex-shrink-0 ml-4">
    <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">Download</a>
  </div>
</li>
