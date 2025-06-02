<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\Admin\V1\PlayerRequestController;
use App\Http\Controllers\StripeController;
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
Route::get('/today-reservations', [AuthController::class, 'getTodayReservations']);
Route::get('/refresh-reservation-count', [AuthController::class, 'refreshReservationCount']);
Route::patch('player-requests/{id}/status', [PlayerRequestController::class, 'updateStatus']);

// Google Auth routes (with web middleware for sessions)
Route::middleware('web')->group(function() {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
});

// Email routes
Route::prefix('v1/emails')->group(function () {
    Route::post('/test', [App\Http\Controllers\Api\User\V1\EmailController::class, 'testEmail']);
    Route::post('/reservation-confirmation', [App\Http\Controllers\Api\User\V1\EmailController::class, 'sendReservationConfirmation']);
});

Route::post('/create-payment-intent', [StripeController::class, 'createPaymentIntent']);
Route::get('/payment-intent/{paymentIntentId}', [StripeController::class, 'retrievePaymentIntent']);
Route::post('/attach-payment-method', [StripeController::class, 'attachPaymentMethod']);
Route::post('/webhook', [StripeController::class, 'webhook']);

// Include admin and user routes
require __DIR__ . '/Api/admin/v1.php';
require __DIR__ . '/Api/user/v1.php';

/*
|--------------------------------------------------------------------------
| Cleanup Routes
|--------------------------------------------------------------------------
*/

Route::get('/cleanup/reservations/{secret_token}', function ($secretToken) {
    // Check if the token matches the one in the environment
    if ($secretToken !== env('CLEANUP_SECRET_TOKEN', 'your-default-secret-token')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Delete pending reservations older than 1 hour
    $oneHourAgo = \Carbon\Carbon::now()->subHour();
    
    $deletedCount = \App\Models\Reservation::where('etat', 'en attente')
        ->where('created_at', '<', $oneHourAgo)
        ->delete();
        
    \Illuminate\Support\Facades\Log::info('API cleanup: Deleted ' . $deletedCount . ' expired pending reservations older than 1 hour');
    
    return response()->json([
        'success' => true,
        'message' => 'Cleanup completed successfully',
        'deleted_count' => $deletedCount
    ]);
});

?>