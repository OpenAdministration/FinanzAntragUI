<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        \DB::table('konto_type')->insert([
            'name' => 'Kasse',
            'short' => 'K',
        ]);
    }
}
