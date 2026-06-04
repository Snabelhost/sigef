<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\Api\DashboardApiController::class, 'getStats']);
        Route::get('/student-status', [\App\Http\Controllers\Api\DashboardApiController::class, 'getStudentStatus']);
        Route::get('/candidate-status', [\App\Http\Controllers\Api\DashboardApiController::class, 'getCandidateStatus']);
        Route::get('/institution-stats', [\App\Http\Controllers\Api\DashboardApiController::class, 'getInstitutionStats']);
        Route::get('/recent-students', [\App\Http\Controllers\Api\DashboardApiController::class, 'getRecentStudents']);
    });
});
