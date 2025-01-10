<?php

namespace App\Livewire\CreateAntrag;

use App\Livewire\Forms\ActorForm;
use Livewire\Component;

class NewOrganisation extends Component
{
    public ActorForm $orgForm;

    public function mount(): void
    {
        $this->orgForm->is_organisation = true;
    }

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View|\Illuminate\Contracts\Foundation\Application
    {
        return view('livewire.create-antrag.new-organisation');
    }

    public function create(): void
    {
        $this->orgForm->create();
    }
}
