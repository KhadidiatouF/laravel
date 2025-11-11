<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TestController;

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
    // Routes pour les comptes
    Route::get('comptes', [CompteController::class, 'index']);
    Route::post('comptes', [CompteController::class, 'store'])->middleware('logging');

    // Routes pour les transactions
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::post('transactions', [TransactionController::class, 'store']);
    Route::get('transactions/{id}', [TransactionController::class, 'show']);

    // Routes spéciales (après les routes paramétrées pour éviter conflits)
});

// Routes publiques (sans auth)
Route::prefix('v1')->group(function () {
    Route::get('test', function () {
        return response()->json(['message' => 'API v1 fonctionne', 'timestamp' => now()]);
    });
});
