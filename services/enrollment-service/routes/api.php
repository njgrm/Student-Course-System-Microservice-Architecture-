<?php

use App\Http\Controllers\EnrollmentController;
use Illuminate\Support\Facades\Route;

// Specific routes must come before wildcard {enrollment} routes
Route::get('enrollments/student/{studentId}', [EnrollmentController::class, 'getByStudent']);
Route::delete('enrollments/student/{studentId}', [EnrollmentController::class, 'destroyByStudent']);
Route::delete('enrollments/course/{courseId}', [EnrollmentController::class, 'destroyByCourse']);

Route::get('enrollments', [EnrollmentController::class, 'index']);
Route::post('enrollments', [EnrollmentController::class, 'store']);
Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show']);
Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);
