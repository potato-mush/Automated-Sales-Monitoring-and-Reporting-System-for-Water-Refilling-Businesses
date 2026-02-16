<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AdminController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Admin Routes
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminController::class, 'login'])->name('login.submit');
        Route::get('/signup', [AdminController::class, 'showSignup'])->name('signup');
        Route::post('/signup', [AdminController::class, 'signup'])->name('signup.submit');
    });

    // Protected admin routes
    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
        Route::get('/gallons', [AdminController::class, 'gallons'])->name('gallons');
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
        Route::get('/inventory', [AdminController::class, 'inventory'])->name('inventory');
        Route::get('/employees', [AdminController::class, 'employees'])->name('employees');
        Route::get('/qr-print', [AdminController::class, 'qrPrint'])->name('qr-print');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    });
});

