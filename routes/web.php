<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {

    Route::get(
        '/login',
        [AuthController::class, 'showLogin']
    )->name('login');

    Route::post(
        '/login',
        [AuthController::class, 'login']
    )->name('login.submit');

    Route::get(
        '/register',
        [AuthController::class, 'showRegister']
    )->name('register');

    Route::post(
        '/register',
        [AuthController::class, 'register']
    )->name('register.submit');
});

Route::middleware('auth')->group(function () {

    Route::post(
        '/logout',
        [AuthController::class, 'logout']
    )->name('logout');

    Route::get(
        '/',
        [ChatController::class, 'index']
    )->name('chat.index');

    Route::post(
        '/chat/start/{user}',
        [ChatController::class, 'start']
    )->name('chat.start');

    Route::get(
        '/chat/{conversation}',
        [ChatController::class, 'show']
    )->name('chat.show');

    Route::post(
        '/chat/{conversation}/messages',
        [ChatController::class, 'send']
    )->name('chat.send');

    Route::post(
        '/chat/{conversation}/read',
        [ChatController::class, 'read']
    )->name('chat.read');

    Route::get(
        '/chat/{conversation}/messages',
        [ChatController::class, 'older']
    )->name('chat.older');
});
