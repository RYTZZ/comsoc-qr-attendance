<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('signup', [App\Http\Controllers\Auth\StudentRegisterController::class, 'create'])
        ->name('signup');

    Route::post('signup', [App\Http\Controllers\Auth\StudentRegisterController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('signup.store');

    Route::get('signup/check-username', [\App\Http\Controllers\Onboarding\AdventurerSetupController::class, 'checkUsername'])
        ->middleware('throttle:60,1')
        ->name('signup.check-username');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Account Assistance (Official COMSOC Communication Channels)
    Route::get('account-assistance', function () {
        return view('auth.account-assistance');
    })->name('account.assistance');

    // Permanent 301 redirects for obsolete password recovery endpoints
    Route::get('forgot-password', function () {
        return redirect()->route('account.assistance', [], 301);
    })->name('password.request');

    Route::post('forgot-password', function () {
        return redirect()->route('account.assistance', [], 301);
    })->name('password.email');

    Route::get('reset-password/{token}', function () {
        return redirect()->route('account.assistance', [], 301);
    })->name('password.reset');

    Route::post('reset-password', function () {
        return redirect()->route('account.assistance', [], 301);
    })->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
