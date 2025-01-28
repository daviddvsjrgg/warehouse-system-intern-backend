<?php

use App\Http\Controllers\Api\UserManagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\ExampleController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MasterItemController;
use App\Http\Controllers\Api\ScannedItemController;
use App\Http\Controllers\Api\RoleController;

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
    // Eager load roles and permissions for the authenticated user
    $user = $request->user()->load('roles');

    return response()->json($user);
});

// Examples -> Just for example for API crud & response
Route::apiResource('examples', ExampleController::class);

// Public routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Protected routes
Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']); // Logout
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/permissions', [PermissionController::class, 'index']);
    Route::get('/permissions/{id}', [PermissionController::class, 'show']);


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
        Route::put('/scanned-item/update-sn/{id}', [ScannedItemController::class, 'updateBarcodeSNOnly'])->name('scanned-item.update.sn');
        Route::put('/scanned-item/update-invoice/{id}', [ScannedItemController::class, 'updateInvoiceOnly'])->name('scanned-item.update.invoice');
        Route::put('/scanned-item/update-all-invoice', [ScannedItemController::class, 'updateAllInvoice'])->name('scanned-item.update.all.invoice');
        Route::delete('/scanned-item/{id}', [ScannedItemController::class, 'destroy'])->name('scanned-item.destroy');

        Route::get('/invoice', [ScannedItemController::class, 'indexByInvoice'])->name('scanned-item.index.by.invoice');
    });

    // User Management routes (accessible only by 'user-management' role)
    Route::middleware(['check.role:user-management'])->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserManagementController::class, 'updateUser'])->name('users.update');

        // Assign permissions to a role
        Route::post('/roles/{roleId}/permissions', [PermissionController::class, 'assignPermissions']);
        // Show permissions for a role
        Route::get('/roles/{roleId}/permissions', [PermissionController::class, 'showPermissions']);
        // Remove permission from a role
        Route::delete('/roles/{roleId}/permissions', [PermissionController::class, 'removePermission']);

        // -> Role
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // -> Permission
        Route::post('/permissions', [PermissionController::class, 'store']);
        Route::put('/permissions/{id}', [PermissionController::class, 'update']);
        Route::delete('/permissions/{id}', [PermissionController::class, 'destroy']);
    });
});


/*
|--------------------------------------------------------------------------
| API Routes (API KEY USER ONLY)
|--------------------------------------------------------------------------
|
*/

// Version 2 API key authenticated routes
Route::prefix('v2')->middleware(['api.key'])->group(function () {
    // Master Items
    Route::get('/master-item', [MasterItemController::class, 'index'])->name('master-item.index');

    // Scanned Items
    Route::get('/scanned-item', [ScannedItemController::class, 'index'])->name('scanned-item.index');
    Route::get('/invoice', [ScannedItemController::class, 'indexByInvoice'])->name('scanned-item.index.by.invoice');
});