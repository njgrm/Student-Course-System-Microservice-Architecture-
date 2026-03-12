<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

// Temporary route to simulate a slow/hanging service for timeout testing (Lab 2).
// Remove after testing is complete.
Route::get('students/slow-test', function () {
    sleep(10); // Simulate 10-second delay

    return response()->json(['id' => 1, 'full_name' => 'Slow Response']);
});

Route::apiResource('students', StudentController::class);
