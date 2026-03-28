<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/admin/dashboard', [DashboardController::class, 'admin'])->name('admin.dashboard');
Route::get('/trainer/dashboard', [DashboardController::class, 'trainer'])->name('trainer.dashboard');
Route::get('/trainee/dashboard', [DashboardController::class, 'trainee'])->name('trainee.dashboard');
Route::get('/trainings/{id}/details', [DashboardController::class, 'trainingDetails'])->name('trainings.details');
