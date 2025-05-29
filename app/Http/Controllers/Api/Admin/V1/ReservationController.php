<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Compte;
use App\Mail\ReservationConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\ReservationResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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
                    DB::raw('COALESCE(compte.nom, reservation.Name) AS client_nom'),
                    DB::raw('COALESCE(compte.prenom, "") AS client_prenom'),
                    DB::raw('COALESCE(compte.telephone, "") AS client_telephone'),
                    DB::raw('COALESCE(compte.email, "") AS client_email'),
                    'reservation.id_terrain',
                    'terrain.nom_terrain as terrain_nom',
                    'terrain.type as terrain_type',
                    'terrain.prix as terrain_prix',
                    'reservation.date',
                    'reservation.heure',
                    'reservation.etat',
                    'reservation.created_at'
                ])
                ->leftJoin('compte', 'reservation.id_client', '=', 'compte.id_compte')
                ->leftJoin('terrain', 'reservation.id_terrain', '=', 'terrain.id_terrain');

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
            
            // Apply search if provided
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('reservation.num_res', 'like', "%$search%")
                      ->orWhere('reservation.Name', 'like', "%$search%")
                      ->orWhere('compte.nom', 'like', "%$search%")
                      ->orWhere('compte.prenom', 'like', "%$search%")
                      ->orWhere('compte.email', 'like', "%$search%")
                      ->orWhere('compte.telephone', 'like', "%$search%");
                });
            }
            
            // Apply sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            
            $allowedSortFields = ['id_reservation', 'date', 'heure', 'etat', 'created_at'];
            if (in_array($sortBy, $allowedSortFields)) {
                $query->orderBy("reservation.$sortBy", $sortOrder);
            } else {
                $query->orderBy('reservation.created_at', 'desc');
            }

            // Delete past reservations
            Reservation::where(DB::raw("CONCAT(date, ' ', heure)"), '<', now())->delete();

            // Pagination
            $perPage = $request->input('per_page', 15);
            $reservations = $query->paginate($perPage);

            if ($reservations->isEmpty()) {
                return response()->json([
                    "status" => "error",
                    "message" => "No reservations found."
                ]);
            }

            // Transform the data to match your expected format
            $response = collect($reservations->items())->map(function ($reservation) {
                return [
                    'id_reservation' => $reservation->id_reservation,
                    'num_res' => $reservation->num_res,
                    'client' => [
                        'id_client' => $reservation->id_client,
                        'nom' => $reservation->client_nom,
                        'prenom' => $reservation->client_prenom,
                        'telephone' => $reservation->client_telephone,
                        'email' => $reservation->client_email,
                    ],
                    'terrain' => [
                        'id_terrain' => $reservation->id_terrain,
                        'nom' => $reservation->terrain_nom,
                        'type' => $reservation->terrain_type,
                        'prix' => $reservation->terrain_prix,
                    ],
                    'date' => $reservation->date,
                    'heure' => $reservation->heure,
                    'etat' => $reservation->etat,
                    'created_at' => $reservation->created_at
                ];
            });

            return response()->json([
                "status" => "success",
                "data" => $response,
                "pagination" => [
                    "total" => $reservations->total(),
                    "per_page" => $reservations->perPage(),
                    "current_page" => $reservations->currentPage(),
                    "last_page" => $reservations->lastPage(),
                    "from" => $reservations->firstItem(),
                    "to" => $reservations->lastItem()
                ]
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
                    'terrain.nom_terrain as terrain_nom',
                    'terrain.type as terrain_type',
                    'terrain.prix as terrain_prix',
                    'terrain.description as terrain_description',
                    'terrain.image as terrain_image',
                    DB::raw('COALESCE(compte.nom, reservation.Name) AS client_nom'),
                    DB::raw('COALESCE(compte.prenom, "") AS client_prenom'),
                    DB::raw('COALESCE(compte.telephone, "") AS client_telephone'),
                    DB::raw('COALESCE(compte.email, "") AS client_email'),
                    'reservation.date',
                    'reservation.heure',
                    'reservation.etat',
                    'reservation.created_at',
                    'reservation.updated_at'
                ])
                ->leftJoin('compte', 'reservation.id_client', '=', 'compte.id_compte')
                ->leftJoin('terrain', 'reservation.id_terrain', '=', 'terrain.id_terrain')
                ->where('reservation.id_reservation', $id);

            $reservation = $query->first();

            if (!$reservation) {
                return response()->json(['message' => 'Reservation not found'], 404);
            }

            $result = [
                'id_reservation' => $reservation->id_reservation,
                'num_res' => $reservation->num_res,
                'client' => [
                    'id_client' => $reservation->id_client,
                    'nom' => $reservation->client_nom,
                    'prenom' => $reservation->client_prenom,
                    'telephone' => $reservation->client_telephone,
                    'email' => $reservation->client_email,
                ],
                'terrain' => [
                    'id_terrain' => $reservation->id_terrain,
                    'nom' => $reservation->terrain_nom,
                    'type' => $reservation->terrain_type,
                    'prix' => $reservation->terrain_prix,
                    'description' => $reservation->terrain_description,
                    'image' => $reservation->terrain_image,
                ],
                'date' => $reservation->date,
                'heure' => $reservation->heure,
                'etat' => $reservation->etat,
                'created_at' => $reservation->created_at,
                'updated_at' => $reservation->updated_at
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

     /**
     * Store a newly created reservation.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_client' => 'nullable|integer|exists:compte,id_compte',
                'id_terrain' => 'required|integer|exists:terrain,id_terrain',
                'date' => 'required|date',
                'heure' => 'required|date_format:H:i:s',
                'type' => 'required|string|in:admin,client',
                'Name' => 'nullable|string|max:255',
                'email' => 'nullable|string|email|max:255',
                'telephone' => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            // Delete pending reservations that are older than 1 hour
            $this->deleteExpiredPendingReservations();

            // Check if client has reached maximum daily reservations (if not admin type)
            if ($validatedData['type'] !== 'admin' && isset($validatedData['id_client'])) {
                $maxDailyReservations = $this->hasReachedMaxDailyReservations($validatedData['id_client'], $validatedData['date']);
                if ($maxDailyReservations) {
                    return response()->json([
                        'error' => true,
                        'message' => 'Ce client a atteint le nombre maximum de réservations pour cette journée (2 maximum).'
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

                if (isset($validatedData['id_client']) && $existingReservation->id_client === $validatedData['id_client']) {
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
            if (!empty($validatedData['email']) || !empty($validatedData['telephone'])) {
                $query = \App\Models\Compte::query();
                
                if (!empty($validatedData['email'])) {
                    $query->where('email', $validatedData['email']);
                }
                
                if (!empty($validatedData['telephone'])) {
                    $query->orWhere('telephone', $validatedData['telephone']);
                }
                
                $client = $query->first();
                
                // If client exists, update their information if new data is provided
                if ($client) {
                    $updateData = [];
                    
                    // Only update if new data is provided and different from existing data
                    if (!empty($validatedData['Name']) && $client->name != $validatedData['Name']) {
                        $updateData['name'] = $validatedData['Name'];
                    }
                    
                    if (!empty($validatedData['Name']) && $client->prenom != $validatedData['Name']) {
                        $updateData['prenom'] = $validatedData['Name'];
                    }
                    
                    if (!empty($validatedData['telephone']) && $client->telephone != $validatedData['telephone']) {
                        $updateData['telephone'] = $validatedData['telephone'];
                    }
                    
                    // Update client if there are changes
                    if (!empty($updateData)) {
                        $client->update($updateData);
                    }
                }
                // If client doesn't exist, create a new account
                else if (!$client && !empty($validatedData['email'])) {
                    // Generate a random password
                    $password = \Illuminate\Support\Str::random(10);
                    
                    $client = \App\Models\Compte::create([
                        'name' => $validatedData['Name'] ?? 'Guest',
                        'prenom' => $validatedData['Name'] ?? 'Guest',
                        'age' => 20,
                        'email' => $validatedData['email'],
                        'password' => \Illuminate\Support\Facades\Hash::make($password),
                        'telephone' => $validatedData['telephone'] ?? '',
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
            $reservationData = [
                'id_terrain' => $validatedData['id_terrain'],
                'date' => $validatedData['date'],
                'heure' => $validatedData['heure'],
                'num_res' => $numRes
            ];

            if ($validatedData['type'] === 'admin') {
                $reservationData['etat'] = 'reserver';
                $reservationData['Name'] = $validatedData['Name'] ?? null;
                
                // If id_client is provided for admin reservation, include it
                if (isset($validatedData['id_client'])) {
                    $reservationData['id_client'] = $validatedData['id_client'];
                } else if ($client) {
                    $reservationData['id_client'] = $client->id_compte;
                }
            } else {
                $reservationData['id_client'] = $validatedData['id_client'] ?? ($client ? $client->id_compte : null);
                $reservationData['etat'] = 'en attente';
                $reservationData['Name'] = $validatedData['Name'] ?? ($client ? $client->name : null);
            }

            $reservation = Reservation::create($reservationData);

            return response()->json([
                'success' => true,
                'message' => 'Réservation enregistrée avec succès',
                'data' => new ReservationResource($reservation)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_client' => 'nullable|integer|exists:compte,id_compte',
                'id_terrain' => 'nullable|integer|exists:terrain,id_terrain',
                'date' => 'nullable|date',
                'heure' => 'nullable|date_format:H:i:s',
                'etat' => 'nullable|string|in:reserver,en attente',
                'Name' => 'nullable|string|max:255',
                'num_res' => 'nullable|string|max:255'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        try {
            // If changing date/time/terrain, check for conflicts
            if (isset($validatedData['date']) || isset($validatedData['heure']) || isset($validatedData['id_terrain'])) {
                $date = $validatedData['date'] ?? $reservation->date;
                $heure = $validatedData['heure'] ?? $reservation->heure;
                $id_terrain = $validatedData['id_terrain'] ?? $reservation->id_terrain;
                
                $existingReservation = Reservation::where('id_terrain', $id_terrain)
                    ->where('date', $date)
                    ->where('heure', $heure)
                    ->where('id_reservation', '!=', $id)
                    ->first();
                    
                if ($existingReservation && $existingReservation->etat === 'reserver') {
                    return response()->json([
                        'error' => true,
                        'message' => 'Cet horaire est déjà réservé pour ce terrain.'
                    ], 409);
                }
            }
            
            $reservation->update($validatedData);
            
            // Get updated reservation with related data
            $updatedReservation = DB::table('reservation')
                ->select([
                    'reservation.id_reservation',
                    'reservation.num_res',
                    'reservation.id_client',
                    'reservation.id_terrain',
                    'terrain.nom_terrain as terrain_nom',
                    'terrain.type as terrain_type',
                    'terrain.prix as terrain_prix',
                    DB::raw('COALESCE(compte.nom, reservation.Name) AS client_nom'),
                    DB::raw('COALESCE(compte.prenom, "") AS client_prenom'),
                    DB::raw('COALESCE(compte.telephone, "") AS client_telephone'),
                    DB::raw('COALESCE(compte.email, "") AS client_email'),
                    'reservation.date',
                    'reservation.heure',
                    'reservation.etat',
                    'reservation.created_at',
                    'reservation.updated_at'
                ])
                ->leftJoin('compte', 'reservation.id_client', '=', 'compte.id_compte')
                ->leftJoin('terrain', 'reservation.id_terrain', '=', 'terrain.id_terrain')
                ->where('reservation.id_reservation', $id)
                ->first();
                
            $result = [
                'id_reservation' => $updatedReservation->id_reservation,
                'num_res' => $updatedReservation->num_res,
                'client' => [
                    'id_client' => $updatedReservation->id_client,
                    'nom' => $updatedReservation->client_nom,
                    'prenom' => $updatedReservation->client_prenom,
                    'telephone' => $updatedReservation->client_telephone,
                    'email' => $updatedReservation->client_email,
                ],
                'terrain' => [
                    'id_terrain' => $updatedReservation->id_terrain,
                    'nom' => $updatedReservation->terrain_nom,
                    'type' => $updatedReservation->terrain_type,
                    'prix' => $updatedReservation->terrain_prix,
                ],
                'date' => $updatedReservation->date,
                'heure' => $updatedReservation->heure,
                'etat' => $updatedReservation->etat,
                'created_at' => $updatedReservation->created_at,
                'updated_at' => $updatedReservation->updated_at
            ];
            
            return response()->json([
                'message' => 'Reservation updated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Reservation',
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

    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'etat' => 'required|string|in:reserver,en attente'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        try {
            // Get the old status before updating
            $oldStatus = $reservation->etat;
            
            // Update the reservation status
            $reservation->update($validatedData);
            
            // If status changed to 'reserver', send confirmation email
            if ($oldStatus !== 'reserver' && $validatedData['etat'] === 'reserver') {
                // Try to get client email - first check if there's an associated account
                $clientEmail = null;
                $clientName = $reservation->Name ?? 'Client';
                
                if ($reservation->id_client) {
                    $client = Compte::find($reservation->id_client);
                    if ($client && $client->email) {
                        $clientEmail = $client->email;
                        $clientName = $client->name ?? $clientName;
                    }
                }
                
                // If no client email found from associated account, try to find it in the DB
                if (!$clientEmail) {
                    // Check if there's a reservation with matching name
                    $possibleClient = DB::table('compte')
                        ->where('name', 'like', '%' . $reservation->Name . '%')
                        ->orWhere('prenom', 'like', '%' . $reservation->Name . '%')
                        ->first();
                        
                    if ($possibleClient && $possibleClient->email) {
                        $clientEmail = $possibleClient->email;
                        $clientName = $possibleClient->name ?? $clientName;
                    }
                }
                
                // Send email if we have a client email
                if ($clientEmail) {
                    // Get terrain name
                    $terrain = DB::table('terrain')
                        ->where('id_terrain', $reservation->id_terrain)
                        ->value('nom_terrain') ?? 'Unknown';
                    
                    // Send the email
                    try {
                        Mail::to($clientEmail)->send(
                            new ReservationConfirmation(
                                $clientName,
                                $reservation->num_res,
                                $reservation->date,
                                $reservation->heure,
                                $terrain,
                                $reservation->etat
                            )
                        );
                        
                        Log::info("Reservation confirmation email sent to: " . $clientEmail);
                    } catch (\Exception $mailException) {
                        Log::error("Failed to send reservation confirmation email: " . $mailException->getMessage());
                        // Continue with the process even if email fails
                    }
                } else {
                    Log::warning("Could not find email for reservation ID: " . $reservation->id_reservation);
                }
            }
            
            // Get updated reservation with related data
            $updatedReservation = DB::table('reservation')
                ->select([
                    'reservation.id_reservation',
                    'reservation.num_res',
                    'reservation.id_client',
                    'reservation.id_terrain',
                    'terrain.nom_terrain as terrain_nom',
                    'terrain.type as terrain_type',
                    'terrain.prix as terrain_prix',
                    DB::raw('COALESCE(compte.nom, reservation.Name) AS client_nom'),
                    DB::raw('COALESCE(compte.prenom, "") AS client_prenom'),
                    DB::raw('COALESCE(compte.telephone, "") AS client_telephone'),
                    DB::raw('COALESCE(compte.email, "") AS client_email'),
                    'reservation.date',
                    'reservation.heure',
                    'reservation.etat',
                    'reservation.created_at',
                    'reservation.updated_at'
                ])
                ->leftJoin('compte', 'reservation.id_client', '=', 'compte.id_compte')
                ->leftJoin('terrain', 'reservation.id_terrain', '=', 'terrain.id_terrain')
                ->where('reservation.id_reservation', $id)
                ->first();
                
            $result = [
                'id_reservation' => $updatedReservation->id_reservation,
                'num_res' => $updatedReservation->num_res,
                'client' => [
                    'id_client' => $updatedReservation->id_client,
                    'nom' => $updatedReservation->client_nom,
                    'prenom' => $updatedReservation->client_prenom,
                    'telephone' => $updatedReservation->client_telephone,
                    'email' => $updatedReservation->client_email,
                ],
                'terrain' => [
                    'id_terrain' => $updatedReservation->id_terrain,
                    'nom' => $updatedReservation->terrain_nom,
                    'type' => $updatedReservation->terrain_type,
                    'prix' => $updatedReservation->terrain_prix,
                ],
                'date' => $updatedReservation->date,
                'heure' => $updatedReservation->heure,
                'etat' => $updatedReservation->etat,
                'created_at' => $updatedReservation->created_at,
                'updated_at' => $updatedReservation->updated_at
            ];
            
            return response()->json([
                'message' => 'Reservation status updated successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Reservation status',
                'error' => $e->getMessage()
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
        \Illuminate\Support\Facades\Log::debug('Admin - Reservation count check for client ' . $clientId, [
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
        
        $deletedCount = Reservation::where('etat', 'en attente')
            ->where('created_at', '<', $oneHourAgo)
            ->delete();
            
        \Illuminate\Support\Facades\Log::info('Deleted ' . $deletedCount . ' expired pending reservations older than 1 hour');
    }
} 