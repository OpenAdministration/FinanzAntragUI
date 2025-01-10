<flux:row>
    <flux:cell>
        @for($i = 0; $i < $level; $i++)
            <div class="w-3 h-10 bg-zinc-700"></div>
        @endfor
    </flux:cell>
    <flux:cell class="flex flex-col content-evenly h-full">
        <flux:icon.arrow-up class="w-5 h-5"/>
        <flux:icon.arrow-down class="w-5 h-5"/>
    </flux:cell>
    <flux:cell>
        <flux:switch wire:model.live="is_group" />
    </flux:cell>
    <flux:cell>
        <flux:radio.group wire:model="is_expense">
            <flux:radio value="false" label="Einnahme" checked />
            <flux:radio value="true" label="Ausgabe" />
        </flux:radio.group>
    </flux:cell>
    <flux:cell>
        <flux:input wire:model="short_name"/>
    </flux:cell>
    <flux:cell>
        <flux:input wire:model="name"/>
    </flux:cell>
    <flux:cell>
        @if($is_group)
            Σ 5 €
        @else
            <flux:input type="number" wire:model="value"/>
        @endif
    </flux:cell>
</flux:row>
