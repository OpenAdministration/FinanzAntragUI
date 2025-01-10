<?php

namespace App\Livewire\Forms;

use App\Models\Project;
use Livewire\Form;

class ProjectForm extends Form
{
    public $name = '';

    public $start_date = '';

    public $end_date = '';

    public $description = '';

    public $student_body_duties = [];

    public function rules(): array
    {
        return [
            'name' => 'required|min:3',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'description' => 'required|min:3',
            'target_group' => 'required',
            'student_body_duties' => 'required|array',
        ];
    }

    public function store(): void
    {
        $this->validate();

        Project::create($this->all());
    }
}
