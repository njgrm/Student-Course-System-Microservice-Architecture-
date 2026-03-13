<?php

use App\Http\Controllers\EnrollmentController;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

// Lab 2 — Timeout evidence route. Calls the student service slow-test endpoint (10s sleep)
// through the enrollment service's 5s timeout, producing a 504 response.
Route::get('enrollments/timeout-test', function () {
    try {
        Http::timeout(5)->get('http://localhost:8001/api/students/slow-test');
    } catch (ConnectionException $e) {
        $isTimeout = str_contains($e->getMessage(), 'timed out') || str_contains($e->getMessage(), 'Timeout');

        return response()->json([
            'error'   => $isTimeout ? '504 GATEWAY_TIMEOUT' : '503 SERVICE_UNAVAILABLE',
            'message' => $isTimeout ? 'Student Service timed out.' : 'Student Service is unavailable.',
        ], $isTimeout ? 504 : 503);
    }
});

// Specific routes must come before wildcard {enrollment} routes
Route::get('enrollments/student/{studentId}', [EnrollmentController::class, 'getByStudent']);
Route::delete('enrollments/student/{studentId}', [EnrollmentController::class, 'destroyByStudent']);
Route::delete('enrollments/course/{courseId}', [EnrollmentController::class, 'destroyByCourse']);

Route::get('enrollments', [EnrollmentController::class, 'index']);
Route::post('enrollments', [EnrollmentController::class, 'store']);
Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show']);
Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);
