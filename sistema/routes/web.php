<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Rutas públicas (solo invitados)
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// Rutas protegidas (requieren iniciar sesión)
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Redirección inicial al dashboard
    Route::get('/', fn () => redirect()->route('dashboard.index'));

    // Ruta del Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::resource('medicines', MedicineController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::resource('sales', SaleController::class)->except(['edit', 'update']);
    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
});
