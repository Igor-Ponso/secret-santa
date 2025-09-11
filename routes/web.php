<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';

Route::middleware(['auth'])
    ->prefix('groups')
    ->name('groups.')
    ->group(function () {
        Route::get('/', [\App\Http\Controllers\GroupController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\GroupController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\GroupController::class, 'store'])->name('store');
        Route::get('/{group}/edit', [\App\Http\Controllers\GroupController::class, 'edit'])->name('edit');
        Route::put('/{group}', [\App\Http\Controllers\GroupController::class, 'update'])->name('update');
        Route::delete('/{group}', [\App\Http\Controllers\GroupController::class, 'destroy'])->name('destroy');
        Route::post('/{group}/invitations', [\App\Http\Controllers\GroupInvitationController::class, 'store'])->name('invitations.store');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/invites/{token}', [\App\Http\Controllers\PublicInvitationController::class, 'show'])->name('invites.show');
    Route::post('/invites/{token}/accept', [\App\Http\Controllers\PublicInvitationController::class, 'accept'])->name('invites.accept');
    Route::post('/invites/{token}/decline', [\App\Http\Controllers\PublicInvitationController::class, 'decline'])->name('invites.decline');
});
