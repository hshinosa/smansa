<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ============================================================================
// CHATBOT API ROUTES
// ============================================================================
Route::name('api.')->group(function () {
    Route::post('/chat/send', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage'])
        ->middleware('throttle:ai_chat')
        ->name('chat.send');

    Route::get('/chat/history', [\App\Http\Controllers\Api\ChatController::class, 'getHistory'])
        ->middleware('throttle:100,1')
        ->name('chat.history');

    // Security: CSP Reporting
    Route::post('/security/csp-report', [\App\Http\Controllers\SecurityController::class, 'handleCspReport'])
        ->middleware('throttle:security_report')
        ->name('security.csp-report');
});
