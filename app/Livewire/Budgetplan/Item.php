<?php

namespace App\Livewire\Budgetplan;

use Livewire\Component;

class Item extends Component
{
    public $short_name;

    public $name;

    public $value;

    public $is_expense = true;

    public $is_group = true;

    public $level = 0;

    public function render()
    {
        return view('livewire.budgetplan.item');
    }
}
