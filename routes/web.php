<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PasswordResetCodeController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/board', [BoardController::class, 'index'])->name('board.index');
Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/forgot-password', [PasswordResetCodeController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetCodeController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password', [PasswordResetCodeController::class, 'resetForm'])->name('password.reset.form');
    Route::post('/reset-password', [PasswordResetCodeController::class, 'reset'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'show'])->name('verification.notice');
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])->middleware('throttle:10,1')->name('verification.verify');
    Route::post('/email/verification-code', [EmailVerificationController::class, 'resend'])->middleware('throttle:3,1')->name('verification.send');

    Route::middleware('verified')->group(function () {
        Route::get('/account', [AccountController::class, 'show'])->name('account.show');
        Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
        Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
        Route::delete('/account', [AccountController::class, 'destroy'])->middleware('throttle:3,10')->name('account.destroy');
        Route::get('/report', [ReportController::class, 'create'])->name('report.create');
        Route::post('/report', [ReportController::class, 'store'])->middleware('throttle:10,10')->name('report.store');
        Route::get('/reports/{item}/edit', [ReportController::class, 'edit'])->name('report.edit');
        Route::put('/reports/{item}', [ReportController::class, 'update'])->name('report.update');
        Route::delete('/reports/{item}', [ReportController::class, 'destroy'])->name('report.destroy');
        Route::post('/claims', [ClaimController::class, 'store'])->middleware('throttle:12,10')->name('claims.store');
        Route::patch('/claims/{claim}/review', [ClaimController::class, 'review'])->name('claims.review');
        Route::post('/claims/{claim}/dispute', [ClaimController::class, 'dispute'])->middleware('throttle:5,10')->name('claims.dispute');
    });
});

Route::get('/admin', function () {
    if (auth()->user()?->isAdmin() === true && auth()->user()?->status === 'active') {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('admin.login');
})->name('admin');

Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('admin.login.store');

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::delete('/items/{id}', [AdminDashboardController::class, 'destroy'])->name('items.destroy');
    Route::delete('/claims/{id}', [AdminDashboardController::class, 'destroyClaim'])->name('claims.destroy');
    Route::patch('/claims/{claim}/review', [AdminDashboardController::class, 'reviewClaim'])->name('claims.review');
    Route::patch('/items/{item}/moderate', [AdminDashboardController::class, 'moderateItem'])->name('items.moderate');
    Route::patch('/claims/{claim}/dispute', [AdminDashboardController::class, 'resolveDispute'])->name('claims.dispute');
    Route::patch('/users/{user}', [AdminDashboardController::class, 'updateUser'])->name('users.update');
    Route::post('/users/{user}/verification-code', [AdminDashboardController::class, 'resendVerification'])->name('users.verification.send');
    Route::post('/users/{user}/password-reset', [AdminDashboardController::class, 'sendPasswordReset'])->name('users.password-reset.send');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});
