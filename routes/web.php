<?php

use Illuminate\Support\Facades\Route;

// /*
// |--------------------------------------------------------------------------
// | Web Routes
// |--------------------------------------------------------------------------
// |
// | Here is where you can register web routes for your application. These
// | routes are loaded by the RouteServiceProvider and all of them will
// | be assigned to the "web" middleware group. Make something great!
// |
// */

Route::get('/', function () {
    return response()->json(['message' => 'API Laravel fonctionne', 'status' => 'OK', 'timestamp' => now()]);
});

use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {
    Mail::raw('Test SendGrid OK ✅', function ($message) {
        $message->to('jamiral2019@gmail.com')->subject('Test SendGrid');
    });

    return 'Email envoyé ✅';
});

