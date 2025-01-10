@props([
    'stripped' => false,
])
<div class="px-4 my-4 sm:px-6 lg:px-8">
    <div class="flow-root">
      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
          <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                @if (!$stripped)
                    <thead class="bg-gray-50">
                        <tr>
                            {{ $header }}
                        </tr>
                    </thead>
                @endif
                @if ($slot->isNotEmpty())
                    <tbody class="bg-white divide-y divide-gray-200">
                        {{ $slot }}
                    </tbody>
                @endif
                @if (!$stripped)
                    <tfoot class="bg-gray-50">
                        <tr>
                            {{ $footer }}
                        </tr>
                    </tfoot>
                @endif
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
