<?php

// in this file all routes which should be visible in debug=true should be listed

Route::middleware(['auth'])->group(function () {

    // Debugging
    Route::get('dev/groups', [\App\Http\Controllers\Dev::class, 'groups']);
});
