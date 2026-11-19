<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RescueController;
use Illuminate\Support\Facades\Route;

// ------------------------------------------------- everyone (guests too)
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/map', [PageController::class, 'map'])->name('map');
Route::get('/help', [PageController::class, 'help'])->name('help');
Route::get('/alerts', [PageController::class, 'alerts'])->name('alerts');
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
// Guests may ask for rescue too (FR-G8); max 3 a minute from one browser.
Route::post('/help/rescue', [RescueController::class, 'store'])->name('rescue.store')->middleware('throttle:3,1');

// ------------------------------------------------------------ accounts
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/forgot-password', [AuthController::class, 'forgot'])->name('password.forgot')->middleware('throttle:3,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --------------------------------------------------- logged-in users
Route::middleware('login.required')->group(function () {
    Route::get('/reports/new', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::post('/reports/{report}/vote', [ReportController::class, 'vote'])->name('reports.vote');
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');
});

// ------------------------------------------------------- admins only
Route::middleware(['login.required', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::post('/rescue/{rescue}/status', [AdminController::class, 'rescueStatus'])->name('rescue.status');
    Route::post('/rescue/{rescue}/flag', [AdminController::class, 'rescueFlag'])->name('rescue.flag');
    Route::post('/reports/{report}/status', [AdminController::class, 'reportStatus'])->name('reports.status');
    Route::delete('/reports/{report}', [AdminController::class, 'reportDelete'])->name('reports.delete');
    Route::post('/alerts', [AdminController::class, 'alertStore'])->name('alerts.store');
    Route::delete('/alerts/{alert}', [AdminController::class, 'alertDelete'])->name('alerts.delete');
    Route::post('/centers', [AdminController::class, 'centerStore'])->name('centers.store');
    Route::post('/centers/{center}/status', [AdminController::class, 'centerStatus'])->name('centers.status');
    Route::delete('/centers/{center}', [AdminController::class, 'centerDelete'])->name('centers.delete');
    Route::post('/users/{user}/status', [AdminController::class, 'userStatus'])->name('users.status');
});
