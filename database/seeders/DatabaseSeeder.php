<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\WithFaker;

class DatabaseSeeder extends Seeder
{
    use WithFaker;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();
        // BudgetPlan::factory(5)->populate()->create();

        if (\App::runningUnitTests()) {
            $this->call(DemoDataSeeder::class);
            $this->call(LocalSeeder::class);
            $this->call(TestSeeder::class);
        }

        if (\App::isLocal()) {
            $this->call(DemoDataSeeder::class);
            $this->call(LocalSeeder::class);
        }

        if (\App::isProduction()) {
            $this->call(ProductionSeeder::class);
            if (config('app.realm') === 'demo') {
                $this->call(DemoDataSeeder::class);
            }
        }
    }
}
