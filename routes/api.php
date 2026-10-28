<?php

use App\Http\Controllers\AuthController as A;
use App\Http\Controllers\ExamController as E;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:exam-auth')->group(function () {
    Route::post('/auth/register', [A::class, 'register']);
    Route::post('/auth/login', [A::class, 'login']);
    Route::post('/auth/google', [A::class, 'google']);
    Route::post('/auth/nonce', [A::class, 'nonce']);
    Route::post('/auth/forgot', [A::class, 'forgot']);
    Route::post('/auth/reset', [A::class, 'reset']);
});
Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('/me', fn (Request $r) => ['user' => $r->user()]);
    Route::post('/auth/logout', [A::class, 'logout']);
    Route::put('/profile', [A::class, 'profile']);
    Route::put('/security', [A::class, 'security']);
    Route::get('/exams', [E::class, 'index']);
    Route::post('/exams', [E::class, 'create']);
    Route::get('/exams/{id}', [E::class, 'show']);
    Route::post('/exams/{id}/deactivate', [E::class, 'deactivate']);
    Route::post('/exams/{id}/reset-code', [E::class, 'resetCode']);
    Route::post('/exams/{id}/reactivate', [E::class, 'reactivate']);
});
Route::middleware('throttle:exam-codes')->group(function () {
    Route::post('/student/lookup', [E::class, 'lookup']);
    Route::post('/student/join', [E::class, 'join']);
});
Route::post('/student/status', [E::class, 'status'])->middleware('throttle:120,1');
Route::post('/student/authorize', [E::class, 'authorize'])->middleware('throttle:20,1');
