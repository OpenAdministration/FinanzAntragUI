<?php

namespace App\Livewire\Forms;

use App\Models\Application;
use App\Models\Project;
use Livewire\Form;

class FundingRequestForm extends Form
{
    public Project $project;

    public $funding_total = '';

    // vorkasse?

    public function store()
    {
        $this->validate();

        Application::create($this->all());
    }
}
