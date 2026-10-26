<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('examroom'));
Route::get('/teacher', fn () => view('examroom'));
Route::get('/reset-password/{token}', fn (string $token) => view('examroom', ['resetToken' => $token, 'resetEmail' => request('email')]))->name('password.reset');

Route::view('/privacy', 'privacy', ['page' => 'privacy'])->name('privacy');
Route::view('/support', 'privacy', ['page' => 'support'])->name('support');
Route::view('/delete-account', 'privacy', ['page' => 'deletion'])->name('account-deletion');
Route::post('/delete-account', [\App\Http\Controllers\PrivacyController::class, 'requestDeletion'])
    ->middleware('throttle:exam-deletion')->name('account-deletion.request');
Route::get('/delete-account/confirm/{user}', [\App\Http\Controllers\PrivacyController::class, 'confirmDeletion'])
    ->middleware(['signed', 'throttle:30,1'])->name('account-deletion.confirm');
Route::post('/delete-account/confirm/{user}', [\App\Http\Controllers\PrivacyController::class, 'deleteAccount'])
    ->middleware(['signed', 'throttle:10,1']);
