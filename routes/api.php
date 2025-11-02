<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TestController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/api/documentation', function () {
    return view('vendor.l5-swagger.index');
});


// Login (obtenir token)
Route::post('/login', [TestController::class, 'login']);

// Test email
Route::get('/test-email', [TestController::class, 'testEmail']);

// Route de diagnostic
Route::get('/diagnostic', [TestController::class, 'diagnostic']);

// API Version 1
Route::prefix('v1')->middleware(['auth:api'])->group(function () {
    // Routes pour les utilisateurs
    Route::get('users', [UserController::class, 'index']);
    Route::post('users', [UserController::class, 'store']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);

    // Routes pour les clients
    Route::get('clients', [ClientController::class, 'index']);
    Route::post('clients', [ClientController::class, 'store']);
    Route::get('clients/telephone/{telephone}', [ClientController::class, 'findByPhone']);
    Route::get('clients/{id}', [ClientController::class, 'show']);
    Route::put('clients/{id}', [ClientController::class, 'update']);
    Route::delete('clients/{id}', [ClientController::class, 'destroy']);

    // Routes pour les comptes
    Route::get('comptes/archives', [CompteController::class, 'archives']);
    Route::get('comptes', [CompteController::class, 'index']);
    Route::post('comptes', [CompteController::class, 'store'])->middleware('logging');
    Route::get('comptes/telephone/{telephone}', [CompteController::class, 'findByPhone']);
    Route::get('comptes/{numero}', [CompteController::class, 'show']);
    Route::put('comptes/{id}', [CompteController::class, 'update']);
    Route::delete('comptes/{id}', [CompteController::class, 'destroy']);

    // Routes spéciales (après les routes paramétrées pour éviter conflits)
});

// Routes publiques (sans auth)
Route::prefix('v1')->group(function () {
    Route::get('test', function () {
        return response()->json(['message' => 'API v1 fonctionne', 'timestamp' => now()]);
    });
});
