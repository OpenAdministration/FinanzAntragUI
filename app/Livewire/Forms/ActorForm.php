<?php

namespace App\Livewire\Forms;

use App\Models\Actor;
use Intervention\Validation\Rules\Bic;
use Intervention\Validation\Rules\Iban;
use Intervention\Validation\Rules\Postalcode;
use Livewire\Form;

class ActorForm extends Form
{
    public $is_organisation = false;

    public $name = '';

    public $zip_code = '';

    public $city = '';

    public $street = '';

    public $iban = '';

    public $bic = '';

    public $website = '';

    public $phones = [];

    public $mails = [];

    public $socials = [];

    public $register_number = '';

    public $vat_deduction = false;

    public $charitable = false;

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|between:2,100',
            'iban' => ['required', new Iban],
            'bic' => ['required', new Bic],
            'website' => 'string',

            'zip_code' => new Postalcode(['de']),
            'street' => 'string',
            'city' => 'string',

            'phones' => 'required|array',
            'phones.*' => 'sometimes|phone:de', // better validation?

            'socials' => 'required|array',
            'socials.*' => 'sometimes|array',
            'socials.*.provider' => 'required|string|in:facebook,twitter,linkedin,google',
            'socials.*.username' => 'required|string',

            'mails' => 'required|array',
            'mails.*' => 'required|email|distinct',
        ];

        if (! $this->is_organisation) {
            return $rules + [
                'register_number' => 'required|string|between:1,100',
                'vat_deduction' => 'required|boolean',
                'charitable' => 'required|boolean',
            ];
        }

        return $rules;
    }

    public function create(): void
    {
        $this->validate();
        $actor = Actor::create(
            $this->except(['socials', 'mails', 'phones'])
        );
        $actor->mails()->createMany($this->mails);
        $actor->phones()->createMany($this->phones);
        $actor->socials()->createMany($this->socials);
    }
}
