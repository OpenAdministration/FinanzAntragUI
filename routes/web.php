<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

Route::redirect('/', 'menu/mygremium')->name('home');

Route::get('auth/login', [\App\Http\Controllers\AuthController::class, 'login'])->name('login');
Route::get('auth/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
Route::get('auth/callback', [\App\Http\Controllers\AuthController::class, 'callback'])->name('login.callback');

Route::middleware(['auth'])->group(function () {

    Route::get('plan/{plan_id}', [\App\Http\Controllers\BudgetPlanController::class, 'show'])->name('budget-plan.show');
    // Route::get('plan/{plan_id}/edit', \App\Http\Livewire\BudgetPlanLivewire::class)->name('budget-plan.edit');

    Route::get('konto/import/manual', \App\Livewire\TransactionImportWire::class)->name('konto.import.manual');

});

// guest routes
Route::get('about', static function () {
    return redirect(config('app.about_url'));
})->name('about');

Route::get('privacy', static function () {
    return redirect(config('app.privacy_url'));
})->name('privacy');

Route::get('terms', static function () {
    return redirect(config('app.terms_url'));
})->name('terms');

Route::get('git-repo', static function () {
    return redirect(config('app.git-repo'));
})->name('git-repo');

Route::get('blog', static function () {
    return redirect(config('app.blog_url'));
})->name('blog');

Route::get('docs', static function () {
    return redirect(config('app.docs_url'));
})->name('docs');
