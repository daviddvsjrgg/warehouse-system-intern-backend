<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExampleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MasterItemController;
use App\Http\Controllers\Api\ScannedItemController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// -> 'php artisan route:list' to check all route

// Get User by their Token
Route::middleware(['auth:sanctum', 'check.token.expiration'])->get('/user', function (Request $request) {
    return response()->json($request->user()->load('roles'));
});

// Examples -> Just for example for API crud & response
Route::apiResource('examples', ExampleController::class);

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Protected routes
Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']); // Logout

    // Master Item routes (accessible only by 'master-item' role)
    Route::middleware(['check.role:master-item'])->group(function () {
        Route::get('/master-item', [MasterItemController::class, 'index'])->name('master-item.index');
        Route::get('/master-item/{id}', [MasterItemController::class, 'show'])->name('master-item.show');
        Route::post('/master-item', [MasterItemController::class, 'store'])->name('master-item.store');
        Route::put('/master-item/{id}', [MasterItemController::class, 'update'])->name('master-item.update');
        Route::delete('/master-item/{id}', [MasterItemController::class, 'destroy'])->name('master-item.destroy');
    });

    // Scanned Item routes (accessible only by 'office' role)
    Route::middleware(['check.role:office'])->group(function () {
        Route::get('/scanned-item', [ScannedItemController::class, 'index'])->name('scanned-item.index');
        Route::get('/scanned-item/{id}', [ScannedItemController::class, 'show'])->name('scanned-item.show');
        Route::post('/scanned-item', [ScannedItemController::class, 'store'])->name('scanned-item.store');
        Route::put('/scanned-item/{id}', [ScannedItemController::class, 'update'])->name('scanned-item.update');
        Route::delete('/scanned-item/{id}', [ScannedItemController::class, 'destroy'])->name('scanned-item.destroy');
    });
});