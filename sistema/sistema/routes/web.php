<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('template/administrador');
// });

Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');

Route::resource('categories', CategoryController::class)->except('show');

Route::resource('roles', RoleController::class)->except('show');