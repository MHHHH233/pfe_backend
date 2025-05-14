<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\Admin\V1\PlayerRequestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout']);
Route::get('/me', [AuthController::class, 'me']);
Route::get('/getUserByToken/{token}', [AuthController::class, 'getUserByToken']);
Route::patch('player-requests/{id}/status', [PlayerRequestController::class, 'updateStatus']);

// Google Auth routes (with web middleware for sessions)
Route::middleware('web')->group(function() {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
});

// Include admin and user routes
require __DIR__ . '/Api/admin/v1.php';
require __DIR__ . '/Api/user/v1.php';

?>