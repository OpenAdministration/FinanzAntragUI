<?php

namespace App\Http\Controllers;

use App\Models\BudgetPlan;
use App\Models\FiscalYear;

class BudgetPlanController extends Controller
{
    public function index()
    {
        $years = FiscalYear::orderByDesc('start_date')->get();

        return view('budget-plan.index', ['years' => $years]);
    }

    public function show(BudgetPlan $plan)
    {
        return view('budget-plan.show');
    }
}
