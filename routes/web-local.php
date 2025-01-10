<?php

// in this file all routes are included which are only visible in env=local

Route::middleware(['auth'])->group(function () {
    // Feature External
    Route::get('antrag/create', \App\Livewire\CreateAntrag::class)->name('antrag.create');
    Route::get('antrag/new-org', \App\Livewire\CreateAntrag\NewOrganisation::class)->name('antrag.new-org');

    // Feature Budget Plans
    Route::get('plan', [\App\Http\Controllers\BudgetPlanController::class, 'index'])->name('budget-plan.index');
    Route::get('plan/create', \App\Livewire\Budgetplan\Create::class)->name('budget-plan.create');

    // Debugging
    Route::get('dev/groups', [\App\Http\Controllers\Dev::class, 'groups']);
});
