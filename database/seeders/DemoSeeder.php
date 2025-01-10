<?php

namespace Database\Seeders;

use App\Models\Actor;
use App\Models\ActorMail;
use App\Models\ActorPhone;
use App\Models\ActorSocial;
use App\Models\Legacy\BankAccount;
use App\Models\Legacy\BankTransaction;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run()
    {
        $startDate = now()->dayOfYear(1);
        \DB::table('haushaltsplan')->insert([
            'von' => $startDate->format('Y-m-d'),
            'bis' => now()->lastOfYear()->format('Y-m-d'),
            'state' => 'final',
        ]);

        \DB::table('haushaltsgruppen')->insert([
            [
                'id' => 1,
                'gruppen_name' => 'Testgruppe Einnahmen',
                'hhp_id' => 1,
                'type' => 0,
            ], [
                'id' => 2,
                'gruppen_name' => 'Testgruppe Einnahmen 2',
                'hhp_id' => 1,
                'type' => 0,
            ], [
                'id' => 3,
                'gruppen_name' => 'Testgruppe Ausgaben',
                'hhp_id' => 1,
                'type' => 1,
            ],
            [
                'id' => 4,
                'gruppen_name' => 'Testgruppe Ausgaben 2',
                'hhp_id' => 1,
                'type' => 1,
            ],
        ]);

        \DB::table('haushaltstitel')->insert([
            [
                'titel_name' => 'Einnahme 1',
                'hhpgruppen_id' => 1,
                'titel_nr' => 'E1.1',
                'value' => 1000,
            ], [
                'titel_name' => 'Einnahme 2',
                'hhpgruppen_id' => 1,
                'titel_nr' => 'E1.2',
                'value' => 2000,
            ], [
                'titel_name' => 'Einnahme 3',
                'hhpgruppen_id' => 2,
                'titel_nr' => 'E2.1',
                'value' => 3000,
            ], [
                'titel_name' => 'Einnahme 4',
                'hhpgruppen_id' => 2,
                'titel_nr' => 'E2.2',
                'value' => 4000,
            ], [
                'titel_name' => 'Ausgabe 1',
                'hhpgruppen_id' => 3,
                'titel_nr' => 'A1.1',
                'value' => 10000,
            ], [
                'titel_name' => 'Ausgabe 2',
                'hhpgruppen_id' => 3,
                'titel_nr' => 'A1.2',
                'value' => 20000,
            ], [
                'titel_name' => 'Ausgabe 3',
                'hhpgruppen_id' => 4,
                'titel_nr' => 'A2.1',
                'value' => 10000,
            ], [
                'titel_name' => 'Ausgabe 4',
                'hhpgruppen_id' => 4,
                'titel_nr' => 'A2.2',
                'value' => 20000,
            ],
        ]);

        BankAccount::factory()->state([
            'name' => 'Cash',
            'short' => 'C',
            'manually_enterable' => true,
        ])->has(
            BankTransaction::factory()->continuous(10, now())
        )->create();

        BankAccount::factory()->state([
            'name' => 'Bank 1',
            'short' => 'B',
        ])->has(
            BankTransaction::factory()->continuous(10, now())
        )->create();

        BankAccount::factory()->state([
            'name' => 'Bank 2',
            'short' => 'T',
        ])->has(
            BankTransaction::factory()->continuous(10, now())
        )->create();

        BankAccount::factory()->state([
            'name' => 'Bank empty',
            'short' => 'E',
        ])->create();

        Actor::factory()->count(10)->asOrganisation()
            ->has(ActorMail::factory()->count(2))
            ->has(ActorPhone::factory()->count(2))
            ->has(ActorSocial::factory()->count(3))
            ->create();

        Actor::factory()->count(2)
            ->has(ActorMail::factory()->count(2))
            ->has(ActorPhone::factory()->count(2))
            ->create();

        // insert does not take 0 as id
        // \DB::table('konto_type')->where('short', '=', 'C')->update([
        //    'id' => 0,
        // ]);

    }
}
