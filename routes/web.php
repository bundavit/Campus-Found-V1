<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\HomeController;
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
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/report', [ReportController::class, 'create'])->name('report.create');
    Route::post('/report', [ReportController::class, 'store'])->name('report.store');
    Route::get('/reports/{item}/edit', [ReportController::class, 'edit'])->name('report.edit');
    Route::put('/reports/{item}', [ReportController::class, 'update'])->name('report.update');
    Route::delete('/reports/{item}', [ReportController::class, 'destroy'])->name('report.destroy');
    Route::post('/claims', [ClaimController::class, 'store'])->name('claims.store');
    Route::patch('/claims/{claim}/review', [ClaimController::class, 'review'])->name('claims.review');
    Route::post('/claims/{claim}/dispute', [ClaimController::class, 'dispute'])->name('claims.dispute');
});

Route::get('/admin', function () {
    if (session('is_admin') === true) {
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
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});
