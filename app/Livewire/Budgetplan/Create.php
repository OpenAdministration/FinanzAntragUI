<?php

namespace App\Livewire\Budgetplan;

use App\Models\FiscalYear;
use Livewire\Attributes\Url;
use Livewire\Component;

class Create extends Component
{
    #[Url(as: 'org')]
    public $organization;

    #[Url(as: 'year')]
    public $fiscal_year_id;

    public $resolution_date;

    public $approval_date;

    public $state;

    public $budget_items;

    public function render()
    {
        $fiscal_years = FiscalYear::all();

        return view('livewire.budgetplan.create', ['fiscal_years' => $fiscal_years]);
    }
}
