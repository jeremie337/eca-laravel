<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/materials/{material}/download', [MaterialController::class, 'download']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/trainings/available', [TrainingController::class, 'available']);
    Route::get('/trainings/my', [TrainingController::class, 'myTrainings']);
    Route::post('/trainings/self-enroll', [TrainingController::class, 'selfEnroll']);
    Route::put('/trainings/complete', [TrainingController::class, 'complete']);
    Route::put('/trainings/progress', [TrainingController::class, 'updateProgress']);

    Route::get('/trainings/{training}/materials', [MaterialController::class, 'index']);
    Route::post('/materials', [MaterialController::class, 'store']);
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy']);

    Route::apiResource('trainings', TrainingController::class);

    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('/enrollments', [EnrollmentController::class, 'store']);
    });
});
