<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // --- Autenticação (público) ---
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

    // --- Rotas protegidas ---
    Route::middleware('auth:sanctum')->group(function () {

        // Utilizador autenticado
        Route::get('/user', function (Request $request) {
            return $request->user();
        });

        // Logout
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::post('/logout-all', [\App\Http\Controllers\Api\AuthController::class, 'logoutAll']);

        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\Api\DashboardApiController::class, 'getStats']);
            Route::get('/student-status', [\App\Http\Controllers\Api\DashboardApiController::class, 'getStudentStatus']);
            Route::get('/candidate-status', [\App\Http\Controllers\Api\DashboardApiController::class, 'getCandidateStatus']);
            Route::get('/institution-stats', [\App\Http\Controllers\Api\DashboardApiController::class, 'getInstitutionStats']);
            Route::get('/students-by-course', [\App\Http\Controllers\Api\DashboardApiController::class, 'getStudentsByCourse']);
            Route::get('/recent-students', [\App\Http\Controllers\Api\DashboardApiController::class, 'getRecentStudents']);
        });
    });
});
