<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');

    Route::get('activate', [\App\Http\Controllers\Auth\StudentActivationController::class, 'showVerifyStudentForm'])
        ->name('student.activate');

    Route::post('activate/verify-student', [\App\Http\Controllers\Auth\StudentActivationController::class, 'verifyStudent'])
        ->middleware('throttle:10,1')
        ->name('student.activate.student.submit');

    Route::get('activate/email', [\App\Http\Controllers\Auth\StudentActivationController::class, 'showEmailForm'])
        ->name('student.activate.email');

    Route::post('activate/email', [\App\Http\Controllers\Auth\StudentActivationController::class, 'submitEmailAndSendOtp'])
        ->middleware('throttle:5,1')
        ->name('student.activate.email.submit');

    Route::get('activate/verify', [\App\Http\Controllers\Auth\StudentActivationController::class, 'showVerifyForm'])
        ->name('student.activate.verify');

    Route::post('activate/verify', [\App\Http\Controllers\Auth\StudentActivationController::class, 'verifyOtp'])
        ->middleware('throttle:5,1')
        ->name('student.activate.verify.submit');

    Route::get('activate/password', [\App\Http\Controllers\Auth\StudentActivationController::class, 'showPasswordForm'])
        ->name('student.activate.password');

    Route::post('activate/complete', [\App\Http\Controllers\Auth\StudentActivationController::class, 'completeActivation'])
        ->middleware('throttle:5,1')
        ->name('student.activate.complete');
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
