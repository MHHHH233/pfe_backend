<?php

namespace App\Http\Controllers\Api\user\V1;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\ReservationResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountCreated;
use App\Mail\ReservationConfirmation;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = DB::table('reservation')
                ->select([
                    'reservation.id_reservation',
                    'reservation.num_res',
                    'reservation.id_client',
                    'reservation.id_terrain',
                    'reservation.Name',
                    'reservation.date',
                    'reservation.heure',
                    'reservation.etat',
                    'reservation.created_at'
                ]);

            // Apply any filters from the request
            if ($request->has('id_terrain')) {
                $query->where('reservation.id_terrain', $request->input('id_terrain'));
            }

            if ($request->has('date')) {
                $query->where('reservation.date', $request->input('date'));
            }
            
            if ($request->has('id_client')) {
                $query->where('reservation.id_client', $request->input('id_client'));
            }
            
            if ($request->has('etat')) {
                $query->where('reservation.etat', $request->input('etat'));
            }

            // Delete past reservations
            Reservation::where(DB::raw("CONCAT(date, ' ', heure)"), '<', now())->delete();

            $reservations = $query->get();

            if ($reservations->isEmpty()) {
                return response()->json([
                    "status" => "error",
                    "message" => "No reservations found."
                ]);
            }

            // Transform the data to match your expected format
            $response = $reservations->map(function ($reservation) {
                return [
                    'id_reservation' => $reservation->id_reservation,
                    'num_res' => $reservation->num_res,
                    'id_client' => $reservation->id_client,
                    'id_terrain' => $reservation->id_terrain,
                    'name' => $reservation->Name,
                    'date' => $reservation->date,
                    'heure' => $reservation->heure,
                    'etat' => $reservation->etat,
                    'created_at' => $reservation->created_at
                ];
            });

            return response()->json([
                "status" => "success",
                "data" => $response
            ]);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "An error occurred while fetching reservations.",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function show($id, Request $request)
    {
        try {
            $query = DB::table('reservation')
                ->select([
                    'reservation.id_reservation',
                    'reservation.num_res',
                    'reservation.id_client',
                    'reservation.id_terrain',
                    'reservation.Name',
                    'reservation.date',
                    'reservation.heure',
                    'reservation.etat',
                    'reservation.created_at'
                ])
                ->where('reservation.id_reservation', $id);

            $reservation = $query->first();

            if (!$reservation) {
                return response()->json(['message' => 'Reservation not found'], 404);
            }

            $result = [
                'id_reservation' => $reservation->id_reservation,
                'num_res' => $reservation->num_res,
                'id_client' => $reservation->id_client,
                'id_terrain' => $reservation->id_terrain,
                'name' => $reservation->Name,
                'date' => $reservation->date,
                'heure' => $reservation->heure,
                'etat' => $reservation->etat,
                'created_at' => $reservation->created_at
            ];

            return response()->json([
                "status" => "success",
                "data" => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "An error occurred while fetching the reservation.",
                "error" => $e->getMessage()
            ]);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_client' => 'nullable|integer|exists:compte,id_compte',
                'id_terrain' => 'required|integer|exists:terrain,id_terrain',
                'date' => 'required|date',
                'heure' => 'required|date_format:H:i:s',
                'email' => 'nullable|string|email|max:255',
                'telephone' => 'nullable|string|max:255',
                'guest_email' => 'nullable|string|email|max:255',
                'guest_telephone' => 'nullable|string|max:255',
                'guest_name' => 'nullable|string|max:255',
                'Name' => 'nullable|string|max:255',
                'type' => 'required|string|in:admin,client',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            // Normalize data - use guest fields if main fields are empty
            $name = $validatedData['Name'] ?? $validatedData['guest_name'] ?? null;
            $email = $validatedData['email'] ?? $validatedData['guest_email'] ?? null;
            $telephone = $validatedData['telephone'] ?? $validatedData['guest_telephone'] ?? null;

            // Delete pending reservations that are older than 1 hour
            $this->deleteExpiredPendingReservations();

            // Check if client has reached maximum daily reservations (if not admin)
            if ($validatedData['type'] !== 'admin' && isset($validatedData['id_client'])) {
                $maxDailyReservations = $this->hasReachedMaxDailyReservations($validatedData['id_client'], $validatedData['date']);
                if ($maxDailyReservations) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Vous avez atteint le nombre maximum de réservations pour cette journée (2 maximum).'
                    ], 400);
                }
            }

            // Check for existing reservations at the same time
            $existingReservation = Reservation::where('id_terrain', $validatedData['id_terrain'])
                ->where('date', $validatedData['date'])
                ->where('heure', $validatedData['heure'])
                ->first();

            if ($existingReservation) {
                if ($existingReservation->etat === 'reserver') {
                    return response()->json([
                        'error' => true,
                        'message' => 'Cet horaire est déjà réservé pour ce terrain.'
                    ], 409);
                }

                if ($existingReservation->id_client === $validatedData['id_client']) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Vous avez déjà réservé cette horaire dans ce terrain.'
                    ], 409);
                }
            }

            // Delete past reservations
            Reservation::where(DB::raw("CONCAT(date, ' ', heure)"), '<', now())->delete();

            // Check if email or telephone exists in compte table
            $client = null;
            $newPassword = null;
            $accountCreated = false;
            
            if (!empty($email) || !empty($telephone)) {
                $query = \App\Models\Compte::query();
                
                if (!empty($email)) {
                    $query->where('email', $email);
                }
                
                if (!empty($telephone)) {
                    $query->orWhere('telephone', $telephone);
                }
                
                $client = $query->first();
                
                // If client exists, update their information if new data is provided
                if ($client) {
                    $updateData = [];
                    
                    // Only update if new data is provided and different from existing data
                    if (!empty($name) && $client->name != $name) {
                        $updateData['name'] = $name;
                    }
                    
                    if (!empty($name) && $client->prenom != $name) {
                        $updateData['prenom'] = $name;
                    }
                    
                    if (!empty($telephone) && $client->telephone != $telephone) {
                        $updateData['telephone'] = $telephone;
                    }
                    
                    // Update client if there are changes
                    if (!empty($updateData)) {
                        $client->update($updateData);
                    }
                }
                // If client doesn't exist, create a new account
                else if (!$client && !empty($email)) {
                    // Generate a random password
                    $newPassword = \Illuminate\Support\Str::random(10);
                    $accountCreated = true;
                    
                    $client = \App\Models\Compte::create([
                        'name' => $name ?? 'Guest',
                        'prenom' => $name ?? 'Guest',
                        'age' => 20,
                        'email' => $email,
                        'password' => \Illuminate\Support\Facades\Hash::make($newPassword),
                        'telephone' => $telephone ?? '',
                        'pfp' => app(\App\Http\Controllers\Api\AuthController::class)->getRandomProfilePicture(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    // Assign user role
                    $client->assignRole('user');
                }
            }

            // Generate a unique reservation number
            $numRes = 'RES-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(5));

            // Create new reservation based on user type
            if ($validatedData['type'] === 'admin') {
                $reservation = Reservation::create([
                    'id_client' => $client ? $client->id_compte : null,
                    'id_terrain' => $validatedData['id_terrain'],
                    'date' => $validatedData['date'],
                    'heure' => $validatedData['heure'],
                    'etat' => 'reserver',
                    'Name' => $name ?? ($client ? $client->name : null),
                    'num_res' => $numRes
                ]);
            } else {
                $reservation = Reservation::create([
                    'id_client' => $validatedData['id_client'] ?? ($client ? $client->id_compte : null),
                    'id_terrain' => $validatedData['id_terrain'],
                    'date' => $validatedData['date'],
                    'heure' => $validatedData['heure'],
                    'etat' => 'en attente',
                    'Name' => $name ?? ($client ? $client->name : null),
                    'num_res' => $numRes
                ]);
            }
            
            // Log reservation creation details
            \Illuminate\Support\Facades\Log::debug('Reservation created', [
                'id_reservation' => $reservation->id_reservation,
                'id_client' => $reservation->id_client,
                'date' => $reservation->date,
                'heure' => $reservation->heure,
                'etat' => $reservation->etat,
                'today' => now()->format('Y-m-d'),
                'matches_today' => $reservation->date === now()->format('Y-m-d')
            ]);

            // If a new account was created and we have an email, send the account details
            if ($accountCreated && !empty($email) && !empty($newPassword)) {
                try {
                    Mail::to($email)->send(new AccountCreated(
                        $name ?? 'Guest',
                        $email,
                        $newPassword,
                        $numRes
                    ));
                    
                    \Illuminate\Support\Facades\Log::info("Account creation email sent to: " . $email);
                } catch (\Exception $mailException) {
                    \Illuminate\Support\Facades\Log::error("Failed to send account email: " . $mailException->getMessage());
                    // Continue with the process even if email fails
                }
            }
            
            // Send reservation confirmation email if we have an email
            if (!empty($email)) {
                try {
                    // Get terrain name from database
                    $terrain = DB::table('terrain')->where('id_terrain', $validatedData['id_terrain'])->value('name') ?? 'Unknown';
                    
                    $this->sendReservationConfirmationEmail(
                        $email,
                        $name ?? 'Guest',
                        $numRes,
                        $validatedData['date'],
                        $validatedData['heure'],
                        $terrain,
                        $reservation->etat
                    );
                    
                    \Illuminate\Support\Facades\Log::info("Reservation confirmation email sent to: " . $email);
                } catch (\Exception $mailException) {
                    \Illuminate\Support\Facades\Log::error("Failed to send reservation confirmation email: " . $mailException->getMessage());
                    // Continue with the process even if email fails
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Réservation enregistrée avec succès',
                'data' => new ReservationResource($reservation),
                'is_new_user' => $accountCreated,
                'user_email' => $accountCreated ? $email : null
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        try {
            $reservation->delete();
            return response()->json([
                'message' => 'Reservation deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('etat')) {
            $query->where('etat', $request->input('etat'));
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->input('date'));
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('Name', 'like', "%$search%")
                  ->orWhereHas('client', function($q) use ($search) {
                      $q->where('nom', 'like', "%$search%")
                        ->orWhere('prenom', 'like', "%$search%");
                  });
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'date');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['date', 'heure'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('date', 'desc')
                  ->orderBy('heure', 'desc');
        }
    }   

    /**
     * Get upcoming reservations for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $currentDateTime = now();
            
            // Log debugging information
            \Illuminate\Support\Facades\Log::debug('User ID: ' . $user->id_compte);
            \Illuminate\Support\Facades\Log::debug('Current DateTime: ' . $currentDateTime);

            $query = DB::table('reservation')
                ->select([
                    'reservation.id_reservation',
                    'reservation.num_res',
                    'reservation.id_client',
                    'reservation.id_terrain',
                    'reservation.Name',
                    'reservation.date',
                    'reservation.heure',
                    'reservation.etat',
                    'reservation.created_at'
                ]);
            
            // Modify query to handle both null and matching id_client
            $query->where(function($q) use ($user) {
                $q->where('reservation.id_client', $user->id_compte)
                  ->orWhere('reservation.id_client', null);
            })
            ->where(DB::raw("CONCAT(reservation.date, ' ', reservation.heure)"), '>', now())
            ->where('reservation.etat', '!=', 'annuler')
            ->orderBy('reservation.date', 'asc')
            ->orderBy('reservation.heure', 'asc');
            
            // Log the SQL query
            \Illuminate\Support\Facades\Log::debug('SQL Query: ' . $query->toSql());
            \Illuminate\Support\Facades\Log::debug('SQL Bindings: ' . json_encode($query->getBindings()));
            
            $upcomingReservations = $query->get();
            
            return response()->json([
                'status' => 'success',
                'data' => $upcomingReservations
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in upcoming method: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch upcoming reservations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reservation history for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $currentDateTime = now();

            $historyReservations = DB::table('reservation')
                ->select([
                    'reservation.id_reservation',
                    'reservation.num_res',
                    'reservation.id_client',
                    'reservation.id_terrain',
                    'reservation.Name',
                    'reservation.date',
                    'reservation.heure',
                    'reservation.etat',
                    'reservation.created_at'
                ])
                ->where('reservation.id_client', $user->id_compte)
                ->where(function($query) use ($currentDateTime) {
                    $query->where(DB::raw("CONCAT(reservation.date, ' ', reservation.heure)"), '<', $currentDateTime)
                          ->orWhere('reservation.etat', 'annuler');
                })
                ->orderBy('reservation.date', 'desc')
                ->orderBy('reservation.heure', 'desc')
                ->paginate(10);

            return response()->json([
                'status' => 'success',
                'data' => $historyReservations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch reservation history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel a reservation.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel($id, Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $reservation = Reservation::where('id_reservation', $id)
                ->where(function($query) use ($user) {
                    $query->where('id_client', $user->id_compte)
                          ->orWhere('id_client', null);
                })
                ->first();

            if (!$reservation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reservation not found or unauthorized'
                ], 404);
            }

            // Check if the reservation is in the future
            $reservationDateTime = \Carbon\Carbon::parse($reservation->date . ' ' . $reservation->heure);
            if ($reservationDateTime->isPast()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel past reservations'
                ], 400);
            }

            // Check if the reservation is already cancelled
            if ($reservation->etat === 'annuler') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reservation is already cancelled'
                ], 400);
            }

            // Make sure to properly quote the value
            $reservation->etat = 'annuler';
            $reservation->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation cancelled successfully',
                'data' => new ReservationResource($reservation)
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in cancel method: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reservations for a specific week.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function week(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $date = $request->input('date', now()->toDateString());
            $startOfWeek = \Carbon\Carbon::parse($date)->startOfWeek();
            $endOfWeek = \Carbon\Carbon::parse($date)->endOfWeek();
            
            // Log debugging information
            \Illuminate\Support\Facades\Log::debug('User ID: ' . $user->id_compte);
            \Illuminate\Support\Facades\Log::debug('Week Range: ' . $startOfWeek->toDateString() . ' to ' . $endOfWeek->toDateString());

            $query = DB::table('reservation')
                ->select([
                    'reservation.id_reservation',
                    'reservation.num_res',
                    'reservation.id_client',
                    'reservation.id_terrain',
                    'reservation.Name',
                    'reservation.date',
                    'reservation.heure',
                    'reservation.etat',
                    'reservation.created_at'
                ]);
            
            // Modify query to handle both null and matching id_client
            $query->where(function($q) use ($user) {
                $q->where('reservation.id_client', $user->id_compte)
                  ->orWhere('reservation.id_client', null);
            })
            ->whereBetween('reservation.date', [$startOfWeek->toDateString(), $endOfWeek->toDateString()])
            ->orderBy('reservation.date', 'asc')
            ->orderBy('reservation.heure', 'asc');
            
            // Log the SQL query
            \Illuminate\Support\Facades\Log::debug('SQL Query: ' . $query->toSql());
            \Illuminate\Support\Facades\Log::debug('SQL Bindings: ' . json_encode($query->getBindings()));
            
            $reservations = $query->get();

            return response()->json([
                'status' => 'success',
                'data' => $reservations,
                'week_range' => [
                    'start' => $startOfWeek->toDateString(),
                    'end' => $endOfWeek->toDateString()
                ]
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in week method: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch reservations for the week: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a user has reached the maximum number of reservations for a day (2 max)
     *
     * @param int $clientId
     * @param string $date
     * @return bool
     */
    protected function hasReachedMaxDailyReservations($clientId, $date): bool
    {
        $today = now()->format('Y-m-d');
        
        // Check if the reservation is for today or a future date
        if ($date === $today) {
            // For today's reservations, count both by date field and created_at
            $reservationCount = Reservation::where('id_client', $clientId)
                ->where(function($query) use ($today) {
                    $query->where('date', $today)
                          ->orWhereDate('created_at', $today);
                })
                ->where('etat', '!=', 'annuler')
                ->count();
        } else {
            // For future dates, just check the date field
            $reservationCount = Reservation::where('id_client', $clientId)
                ->where('date', $date)
                ->where('etat', '!=', 'annuler')
                ->count();
        }
        
        // Log the count for debugging
        \Illuminate\Support\Facades\Log::debug('Reservation count check for client ' . $clientId, [
            'date' => $date,
            'today' => $today,
            'is_today' => ($date === $today),
            'count' => $reservationCount
        ]);
            
        return $reservationCount >= 2;
    }
    
    /**
     * Delete pending reservations that are older than 1 hour
     */
    protected function deleteExpiredPendingReservations(): void
    {
        $oneHourAgo = Carbon::now()->subHour();
        
        Reservation::where('etat', 'en attente')
            ->where('created_at', '<', $oneHourAgo)
            ->delete();
            
        \Illuminate\Support\Facades\Log::info('Deleted expired pending reservations older than 1 hour');
    }

    /**
     * Send reservation confirmation email
     *
     * @param string $email
     * @param string $name
     * @param string $numRes
     * @param string $date
     * @param string $time
     * @param string $terrain
     * @param string $status
     * @return void
     */
    protected function sendReservationConfirmationEmail(
        string $email,
        string $name,
        string $numRes,
        string $date,
        string $time,
        string $terrain,
        string $status
    ): void {
        try {
            Mail::to($email)->send(new ReservationConfirmation(
                $name,
                $numRes,
                $date,
                $time,
                $terrain,
                $status
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send reservation confirmation email: " . $e->getMessage());
            // We're just logging the error, not stopping execution
        }
    }
} 