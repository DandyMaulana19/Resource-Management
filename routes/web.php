<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

    Route::get('/login', [LoginController::class, "index"])->name('login');
    Route::post('/login', [LoginController::class, "authenticate"]);

Route::middleware(['auth:user'])->group(function () {
    Route::get('/', [HomeController::class, "index"]);
});
