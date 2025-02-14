<?php

namespace Database\Factories;

use App\Models\BudgetItem;
use App\Models\Enums\BudgetPlanState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BudgetPlanFactory extends Factory
{
    public function definition(): array
    {
        $year = Carbon::create(fake()->unique()->year());

        return [
            'state' => BudgetPlanState::FINAL,
            'start_date' => $year->dayOfYear(1)->format('Y-m-d'),
            'end_date' => $year->dayOfYear(now()->daysInYear)->format('Y-m-d'),
            'organisation' => 'Students Council',
            'resolution_date' => $year->dayOfYear(1)->subDays(60),
            'approval_date' => $year->dayOfYear(1)->subDays(30),
        ];
    }

    public function populate()
    {
        return $this->has(
            BudgetItem::factory(5)
        );
    }
}
