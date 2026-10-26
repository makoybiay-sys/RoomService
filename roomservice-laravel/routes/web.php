<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\DashboardController;

// --- GUEST ROUTES ---
Route::get('/', function () { return view('index'); })->name('home');
Route::get('/track', function () { return view('track'); })->name('track');

// --- AUTH ROUTES ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --- API ROUTES (Para sa JS) ---
Route::prefix('api')->group(function () {
    // Menu
    Route::get('/menu', [MenuController::class, 'index']);
    Route::post('/menu', [MenuController::class, 'store']);
    Route::post('/menu/{id}', [MenuController::class, 'update']);
    Route::delete('/menu/{id}', [MenuController::class, 'destroy']);
    Route::post('/menu/reset-stock', [MenuController::class, 'resetStock']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders/{id}/assign', [OrderController::class, 'assignWaiter']);
    Route::post('/orders/{id}/cancel-request', [OrderController::class, 'requestCancel']);
    Route::post('/orders/{id}/cancel-handle', [OrderController::class, 'handleCancel']);
    Route::post('/orders/{id}/notify', [OrderController::class, 'notifyArrival']);
    Route::post('/orders/{id}/void', [OrderController::class, 'voidOrder']); // IDINAGDAG

    // Arrival Polling
    Route::get('/arrival/check', [OrderController::class, 'checkArrival']);
    Route::post('/arrival/seen', [OrderController::class, 'markArrivalSeen']);

    // Staff
    Route::get('/staff', [StaffController::class, 'index']);
    Route::get('/waiters', [StaffController::class, 'waiters']);
    Route::post('/staff', [StaffController::class, 'store']);
    Route::post('/staff/{id}', [StaffController::class, 'update']);
    Route::delete('/staff/{id}', [StaffController::class, 'destroy']);

    // Dashboard / Analytics
    Route::get('/revenue-stats', [DashboardController::class, 'revenueStats']);
    Route::get('/unpaid-folios', [DashboardController::class, 'unpaidFolios']);
    Route::get('/reports', [DashboardController::class, 'reports']); // IDINAGDAG
});

// --- PROTECTED ROUTES (Kailangan ng Login) ---
Route::middleware('auth')->group(function () {
    Route::get('/admin', function () { return view('admin'); });
    Route::get('/kitchen', function () { return view('kitchen'); });
    Route::get('/waiter', function () { return view('waiter'); });
    Route::get('/coffee-manager', function () { return view('coffee_manager'); });
    Route::get('/frontdesk', function () { return view('frontdesk'); });
    Route::get('/fnb-manager', function () { return view('fnb_manager'); });
    Route::get('/reports', function () { return view('reports'); }); // IDINAGDAG
    Route::get('/qr-codes', function () { return view('qr_codes'); });
});