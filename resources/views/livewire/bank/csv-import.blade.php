<div class="mt-8 sm:mx-8">
    <x-headline :headline="__('konto.manual-headline')" :sub-text="__('konto.manual-headline-sub')"/>

    <div class="max-w-screen-lg">
        <div class="py-3">
            <label for="account_id" class="block text-sm font-medium leading-6 text-gray-900">
                {{ __('konto.csv-label-choose-konto') }}
            </label>
            <select wire:model.live="account_id"
                    class="my-2 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6"
            >
                <option value=""></option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                @endforeach
            </select>
            @if($account_id !== "")
                <dl class="-my-3 divide-y divide-gray-100 py-4 text-sm leading-6" wire:transition.scale.origin.top>
                    @isset($latestTransaction)
                        <div wire:key="last-transaction" class="px-6 border-l-2 border-indigo-600">
                            <div class="flex justify-between gap-x-4 py-3">
                                <dt class="text-gray-500">{{ __('konto.csv-latest-saldo') }}:</dt>
                                <dd class="text-gray-700">{{ $this->formatDataView($latestTransaction->saldo, 'value') }}</dd>
                            </div>
                            <div class="flex justify-between gap-x-4 py-3">
                                <dt class="text-gray-500">{{ __('konto.csv-latest-date') }}:</dt>
                                <dd class="text-gray-700">
                                    {{ $latestTransaction->date->format('d.m.Y') }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-x-4 py-3">
                                <dt class="text-gray-500">{{ __('konto.csv-latest-zweck') }}:</dt>
                                <dd class="text-gray-700">
                                    {{ $latestTransaction->zweck }}
                                </dd>
                            </div>
                        </div>
                    @else
                        <div class="px-6 py-3 border-l-2 border-indigo-600" wire:key="no-transaction">
                            <dt class="text-gray-700">{{ __('konto.csv-no-transaction') }}</dt>
                        </div>
                    @endif
                </dl>
            @endif
        </div>
        <div wire:key="csv-upload-block">
            @if($account_id !== "")
                <div>
                    <x-headline :headline="__('konto.csv-upload-headline')" :sub-text="__('konto.csv-upload-headline-sub')"/>
                    <x-drop-area wire:model="csv" :upload-done="!empty($csv)">
                        <p class="mb-3 text-sm text-gray-500 dark:text-gray-400 font-semibold">{{ __('konto.csv-draganddrop-fat-text') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('konto.csv-draganddrop-sub-text') }}</p>
                    </x-drop-area>
                </div>
            @endif
        </div>

        @if(isset($csv) && !$errors->has('csv'))
            <div wire:transition.scale.origin.top>
                <x-headline :headline="__('konto.transaction.headline')" :sub-text="__('konto.transaction.headline-sub')"/>
                <div class="my-5">
                    <x-toggle wire:click="reverseCsvOrder" :active="$csvOrder === 1">
                        <span class="font-medium text-gray-900">{{ __('konto.manual-button-reverse-csv-order') }}</span>
                        <span class="text-sm text-gray-500 ml-1">{{ __('konto.manual-button-reverse-csv-order-sub') }}</span>
                    </x-toggle>
                </div>
            </div>
        @endif
    </div>
    @if(isset($csv) && !$errors->has('csv'))
        <x-grid-list wire:transition.scale.origin.top>
            @foreach($labels as $attr => $label)
                <x-grid-list.item-card wire:key="{{ $attr }}">
                    <div>
                        <div class="flex justify-between">
                            <label for="mapping.{{ $attr }}" class="block text-sm font-medium leading-6 text-gray-900">
                                {{ __($label) }}:
                            </label>
                            <span class="text-sm leading-6 text-gray-500" id="email-optional">{{ __("konto.hint.transaction.$attr") }}</span>
                        </div>
                        <div class="mt-2">
                            <select wire:model.live="mapping.{{ $attr }}" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    aria-describedby="email-optional">
                                <option value=""></option>
                                @foreach($header as $csv_column_id => $value)
                                    <option value="{{ $csv_column_id }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            @error("mapping.$attr")<span class="text-red-500"> {{ $message }}</span> @enderror
                        </div>
                    </div>
                    <x-slot:rows>
                        @isset($firstNewTransaction[$mapping[$attr]])
                            <x-grid-list.item-card.row label="konto.csv-preview-first">
                                {{ $this->formatDataView($firstNewTransaction[$mapping[$attr]], $attr) }}
                            </x-grid-list.item-card.row>
                        @endisset
                        @isset($lastNewTransaction[$mapping[$attr]])
                            <x-grid-list.item-card.row label="konto.csv-preview-last">
                                {{ $this->formatDataView($lastNewTransaction[$mapping[$attr]], $attr) }}
                            </x-grid-list.item-card.row>
                        @endisset
                    </x-slot:rows>
                </x-grid-list.item-card>
            @endforeach
        </x-grid-list>
        <div class="py-4">
            <button wire:click="save" wire:loading.class="disabled opacity-50" wire:target="save"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:w-auto"
                    id="submit-assign">
                <x-fas-floppy-disk class="w-4 h-4 mr-2"/>
                <span>{{ __('konto.manual-button-assign') }}</span>
                <x-fas-spinner class="animate-spin fill-white hidden ml-3" wire:loading.class.remove="hidden" wire:target="save"/>
            </button>
        </div>
    @endif
</div>
