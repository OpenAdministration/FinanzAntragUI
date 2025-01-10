<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivewireBudgetPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_livewire_component_renders(): void
    {
        $this->loginAsUser();
        $create = $this->get('/plan/create');
        $create->assertRedirectToRoute('budget-plan.edit', ['plan_id' => 1]);
    }

    public function test_edit_budget_plan_meta_data_livewire(): void
    {
        \Livewire::test('budget-plan-livewire')
            ->set('plan.organisation', 'Test Organisation')
            ->set('plan.start_date', '2020-01-01')
            ->set('plan.end_date', '2020-12-31')
            ->set('plan.resolution_date', '2019-12-01')
            ->set('plan.approval_date', '2019-12-24')
            ->assertSet('plan.organisation', 'Test Organisation')
            ->assertSet('plan.start_date', new Carbon('2020-01-01'))
            ->assertSet('plan.end_date', new Carbon('2020-12-31'))
            ->assertSet('plan.resolution_date', new Carbon('2019-12-01'))
            ->assertSet('plan.approval_date', new Carbon('2019-12-24'));

        $this->assertDatabaseHas('budget_plan', [
            'organisation' => 'Test Organisation',
            'start_date' => '2020-01-01',
            'end_date' => '2020-12-31',
            'resolution_date' => '2019-12-01',
            'approval_date' => '2019-12-24',
        ]);
    }
}
