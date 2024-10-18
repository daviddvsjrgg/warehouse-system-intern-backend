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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Examples
Route::apiResource('examples', ExampleController::class);

// Public routes
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');


// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    //Master Item
    Route::apiResource('master-item', MasterItemController::class);
    //Scanned Item
    Route::apiResource('scanned-item', ScannedItemController::class);
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
});
