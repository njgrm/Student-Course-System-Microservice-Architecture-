<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('students.index');
})->name('students.index');
