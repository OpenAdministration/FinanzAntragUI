<x-layout>
    <div class="sm:mx-8 mt-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h1 class="text-xl font-semibold text-gray-900">{{ __('budget-plan.index-headline') }}</h1>
                <p class="mt-2 text-sm text-gray-700">{{ __('budget-plan.index-headline-sub') }}</p>
            </div>
            <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
                <a href="{{ route('budget-plan.create') }}" class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto">
                    <x-heroicon-o-plus class="-ml-0.5 mr-2 h-4 w-4"/>
                    {{ __('budget-plan.index-button-new-plan') }}
                </a>
            </div>
        </div>
        @if($years->isEmpty())
            <a href="{{ route('budget-plan.create') }}" class="group mt-8 relative block w-full rounded-lg border-2 border-dashed border-gray-300
            p-12 text-center hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <x-heroicon-o-table-cells stroke-width="1" class="mx-auto h-12 w-12 text-gray-400 group-hover:text-gray-600" />
                <span class="mt-2 block text-sm font-medium text-gray-700 group-hover:text-black">
                    {{ __('budget-plan.index-no-plans') }}
                </span>
            </a>
        @else
            <div class="mt-8 flex flex-col overflow-hidden bg-white shadow sm:rounded-md">
                <ul role="list" class="divide-y divide-gray-200">
                    @foreach($years as $year)
                        <div>{{ $year->start_date }} - {{ $year->end_date }}</div>
                        @foreach($year->budgetPlans as $plan)
                            <li>
                                <a href="{{ route('budget-plan.show', ['plan_id' => $plan->id]) }}" class="block group hover:bg-gray-100">
                                    <div class="flex items-center justify-between">
                                        <div class="px-4 py-4 sm:px-6">
                                            <div class="flex items-center">
                                                <p class="truncate text-sm font-medium text-indigo-600">
                                                    {{ $plan->organisation }}
                                                </p>
                                                <div class="ml-2 flex flex-shrink-0">
                                                    <p class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">
                                                        {{ $plan->state }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="mt-2 sm:flex sm:justify-between">
                                                <div class="sm:flex">
                                                    <p class="flex items-center text-sm text-gray-500">
                                                        <x-heroicon-m-calendar class="mr-1.5 h-5 w-5 flex-shrink-0 text-gray-400" />
                                                        <x-date :date="$plan->start_date" format="M y"/>
                                                        <span class="px-1">-</span>
                                                        <x-date :date="$plan->end_date" format="M y"/>
                                                    </p>
                                                    <!-- follows: class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0 sm:ml-6" -->
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 pr-4">
                                            <x-heroicon-s-chevron-right class="h-5 w-5 text-gray-400 group-hover:text-gray-600" />
                                        </div>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-layout>
