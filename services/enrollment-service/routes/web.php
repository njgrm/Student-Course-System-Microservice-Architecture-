<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('enrollments.index');
})->name('enrollments.index');
