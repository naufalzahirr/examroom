<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('examroom'));
Route::get('/teacher', fn () => view('examroom'));
Route::get('/reset-password/{token}', fn (string $token) => view('examroom', ['resetToken' => $token, 'resetEmail' => request('email')]))->name('password.reset');
