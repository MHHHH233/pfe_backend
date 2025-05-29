<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationConfirmation;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;

class EmailController extends Controller
{
    /**
     * Send a reservation confirmation email
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendReservationConfirmation(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'reservation_id' => 'required|integer|exists:reservation,id_reservation',
                'email' => 'required|email',
            ]);

            $reservation = Reservation::findOrFail($validatedData['reservation_id']);
            
            // Get terrain name
            $terrain = DB::table('terrain')
                ->where('id_terrain', $reservation->id_terrain)
                ->value('name') ?? 'Unknown';
            
            // Send email
            Mail::to($validatedData['email'])->send(new ReservationConfirmation(
                $reservation->Name ?? 'Guest',
                $reservation->num_res,
                $reservation->date,
                $reservation->heure,
                $terrain,
                $reservation->etat
            ));

            return response()->json([
                'success' => true,
                'message' => 'Confirmation email sent successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test email configuration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function testEmail(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'email' => 'required|email',
            ]);

            // Create test data
            $testData = [
                'name' => 'Test User',
                'numRes' => 'TEST-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5)),
                'date' => date('Y-m-d'),
                'time' => '14:00:00',
                'terrain' => 'Test Field',
                'status' => 'confirmed'
            ];
            
            // Send test email
            Mail::to($validatedData['email'])->send(new ReservationConfirmation(
                $testData['name'],
                $testData['numRes'],
                $testData['date'],
                $testData['time'],
                $testData['terrain'],
                $testData['status']
            ));

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully to ' . $validatedData['email']
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 