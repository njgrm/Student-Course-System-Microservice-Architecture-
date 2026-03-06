<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('courses.index');
})->name('courses.index');
