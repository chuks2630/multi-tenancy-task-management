<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\PlanController;

Route::get('/', function () {
    return view('welcome');
});

// Tenant Registration (Organization Signup)
Route::prefix('register')->group(function () {
    Route::get('/', [TenantController::class, 'create'])->name('tenant.create');
    Route::post('/', [TenantController::class, 'store'])->name('tenant.store');
});

// Public Plans
Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');