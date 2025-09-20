<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\LanguageController;
use App\Http\Controllers\Settings\SecurityController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    Route::get('settings/language', [LanguageController::class, 'edit'])->name('language.edit');
    Route::put('settings/language', [LanguageController::class, 'update'])->name('language.update');

    // Security settings
    Route::get('settings/security', [SecurityController::class, 'index'])->name('security.index');
    Route::post('settings/security/2fa/enable', [SecurityController::class, 'enableTwoFactor'])->name('settings.security.2fa.enable');
    Route::delete('settings/security/2fa/disable', [SecurityController::class, 'disableTwoFactor'])->name('settings.security.2fa.disable');
    Route::delete('settings/security/devices/{device}', [SecurityController::class, 'destroyDevice'])->name('settings.security.devices.destroy');
    Route::delete('settings/security/devices', [SecurityController::class, 'destroyAllDevices'])->name('settings.security.devices.destroyAll');
    Route::post('settings/security/logout-others', [SecurityController::class, 'logoutOthers'])->name('settings.security.logoutOthers');
});
