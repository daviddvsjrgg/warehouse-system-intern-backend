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
    return $request->user();
});

// Examples -> Just for example for API crud & response
Route::apiResource('examples', ExampleController::class);

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');


// Protected routes
Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']); // Logout
    Route::apiResource('master-item', MasterItemController::class); //Master Item Api Resource 
    Route::apiResource('scanned-item', ScannedItemController::class); //Scanned Item Api Resource
});
