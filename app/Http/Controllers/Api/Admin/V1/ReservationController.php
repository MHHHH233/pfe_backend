<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Compte;
use App\Mail\ReservationConfirmation;
use App\Mail\PaymentConfirmation;
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
                    'reservation.advance_payment',
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
                // Check if there's a payment for this reservation
                $payment = DB::table('payments')
                    ->where('id_reservation', $reservation->id_reservation)
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                $paymentInfo = null;
                if ($payment) {
                    $paymentInfo = [
                        'id_payment' => $payment->id_payment,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'payment_method' => $payment->payment_method,
                        'currency' => $payment->currency,
                        'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                    ];
                }
                
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
                    'advance_payment' => $reservation->advance_payment,
                    'payment' => $paymentInfo,
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
                    'reservation.advance_payment',
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

            // Get related payment information
            $payment = DB::table('payments')
                ->where('id_reservation', $id)
                ->orderBy('created_at', 'desc')
                ->first();
                
            $paymentInfo = null;
            if ($payment) {
                $paymentInfo = [
                    'id_payment' => $payment->id_payment,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'currency' => $payment->currency,
                    'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                    'payment_details' => $payment->payment_details ? json_decode($payment->payment_details) : null,
                    'created_at' => $payment->created_at
                ];
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
                'advance_payment' => $reservation->advance_payment,
                'payment' => $paymentInfo,
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
                'payment_method' => 'nullable|string|in:cash,online,stripe',
                'payment_status' => 'nullable|string|in:paid,partially_paid,unpaid',
                'advance_payment' => 'nullable|numeric',
                'price' => 'nullable|numeric',
                'payment_intent_id' => 'nullable|string',
                'amount' => 'nullable|numeric',
                'currency' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            // Delete pending reservations that are older than 1 hour
            $this->deleteExpiredPendingReservations();

            // For admin type, skip the maximum daily reservations check
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
                // If admin type, allow overriding existing reservations that are not already reserved
                if ($validatedData['type'] === 'admin') {
                    if ($existingReservation->etat === 'reserver') {
                        return response()->json([
                            'error' => true,
                            'message' => 'Cet horaire est déjà réservé pour ce terrain.'
                        ], 409);
                    }
                    // If the existing reservation is 'en attente', we'll override it
                } else {
                    // For regular users, check normally
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

            // Add advance_payment if provided
            if (isset($validatedData['advance_payment'])) {
                $reservationData['advance_payment'] = $validatedData['advance_payment'];
            } else if (isset($validatedData['amount']) && $validatedData['payment_method'] === 'stripe') {
                // For Stripe payments, use the amount as advance_payment if advance_payment is not set
                $reservationData['advance_payment'] = $validatedData['amount'];
            }

            // Admin reservations status depends on payment method and status
            if ($validatedData['type'] === 'admin') {
                // For Stripe payments with 'paid' status, set etat to 'reserver'
                if (isset($validatedData['payment_method']) && 
                    $validatedData['payment_method'] === 'stripe' && 
                    isset($validatedData['payment_status']) && 
                    $validatedData['payment_status'] === 'paid') {
                    $reservationData['etat'] = 'reserver';
                }
                // If admin type with advance_payment set and payment_status is partially_paid, set status to 'en attente'
                else if (isset($validatedData['advance_payment']) && 
                    $validatedData['advance_payment'] > 0 && 
                    isset($validatedData['payment_status']) && 
                    $validatedData['payment_status'] === 'partially_paid') {
                    // Check if price equals advance_payment for cash payments
                    if (isset($validatedData['payment_method']) && 
                        $validatedData['payment_method'] === 'cash' && 
                        isset($validatedData['price']) && 
                        $validatedData['price'] == $validatedData['advance_payment']) {
                        $reservationData['etat'] = 'reserver';
                    } else {
                        $reservationData['etat'] = 'en attente';
                    }
                } else {
                    $reservationData['etat'] = 'reserver';
                }
                
                $reservationData['Name'] = $validatedData['Name'] ?? null;
                
                // If id_client is provided for admin reservation, include it
                if (isset($validatedData['id_client'])) {
                    $reservationData['id_client'] = $validatedData['id_client'];
                } else if ($client) {
                    $reservationData['id_client'] = $client->id_compte;
                }
            } else {
                $reservationData['id_client'] = $validatedData['id_client'] ?? ($client ? $client->id_compte : null);
                
                // For regular users, set status based on payment method
                if (isset($validatedData['payment_method']) && $validatedData['payment_method'] === 'online') {
                    $reservationData['etat'] = 'reserver';
                } else {
                    $reservationData['etat'] = 'en attente';
                }
                
                $reservationData['Name'] = $validatedData['Name'] ?? ($client ? $client->name : null);
            }

            // If there's an existing pending reservation and this is an admin reservation, delete the old one
            if ($existingReservation && $validatedData['type'] === 'admin' && $existingReservation->etat === 'en attente') {
                $existingReservation->delete();
                Log::info('Admin reservation replaced a pending reservation for terrain ' . $validatedData['id_terrain'] . ' at ' . $validatedData['date'] . ' ' . $validatedData['heure']);
            }

            $reservation = Reservation::create($reservationData);
            
            // Create a payment record if advance_payment or Stripe payment is provided
            if ((isset($validatedData['advance_payment']) && $validatedData['advance_payment'] > 0) ||
                (isset($validatedData['payment_method']) && $validatedData['payment_method'] === 'stripe' && isset($validatedData['amount']))) {
                
                $paymentStatus = 'pending';
                
                // Set payment status based on payment_status
                if (isset($validatedData['payment_status'])) {
                    $paymentStatus = $validatedData['payment_status'] === 'paid' ? 'completed' : 'pending';
                }
                
                $paymentMethod = isset($validatedData['payment_method']) ? 
                    $validatedData['payment_method'] : 
                    'cash';
                
                $fullPrice = isset($validatedData['price']) ? $validatedData['price'] : null;
                
                // Get terrain price if full price is not provided
                if (!$fullPrice) {
                    $terrain = \App\Models\Terrain::find($validatedData['id_terrain']);
                    $fullPrice = $terrain ? $terrain->prix : 0;
                }
                
                $amount = isset($validatedData['advance_payment']) ? 
                    $validatedData['advance_payment'] : 
                    (isset($validatedData['amount']) ? $validatedData['amount'] : 0);
                
                $currency = isset($validatedData['currency']) ? 
                    $validatedData['currency'] : 
                    'MAD';
                
                // Create payment record
                $payment = \App\Models\Payment::create([
                    'amount' => $amount,
                    'status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
                    'currency' => $currency,
                    'id_reservation' => $reservation->id_reservation,
                    'id_compte' => $reservationData['id_client'] ?? null,
                    'stripe_payment_intent_id' => $validatedData['payment_intent_id'] ?? null,
                    'payment_details' => json_encode([
                        'full_price' => $fullPrice,
                        'advance_payment' => $amount,
                        'remaining' => $fullPrice - $amount,
                        'type' => $amount == $fullPrice ? 'full_payment' : 'advance_payment',
                        'payment_intent_id' => $validatedData['payment_intent_id'] ?? null
                    ])
                ]);
                
                Log::info('Payment record created for reservation', [
                    'reservation_id' => $reservation->id_reservation,
                    'payment_id' => $payment->id_payment,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'payment_intent_id' => $payment->stripe_payment_intent_id
                ]);
                
                // Send payment confirmation email if status is 'paid'
                if (isset($validatedData['payment_status']) && $validatedData['payment_status'] === 'paid' && !empty($validatedData['email'])) {
                    // Get terrain name
                    $terrain = \App\Models\Terrain::find($validatedData['id_terrain']);
                    $terrainName = $terrain ? $terrain->nom_terrain : 'Unknown';
                    
                    $this->sendPaymentConfirmationEmail(
                        $validatedData['email'],
                        $validatedData['Name'] ?? 'Client',
                        $reservation->num_res,
                        $reservation->date,
                        $reservation->heure,
                        $terrainName,
                        $reservation->etat,
                        $paymentMethod,
                        $amount,
                        $currency
                    );
                    
                    Log::info("Stripe payment confirmation email sent to: " . $validatedData['email']);
                }
            }
            
            // Log reservation creation details
            Log::debug('Admin - Reservation created', [
                'id_reservation' => $reservation->id_reservation,
                'id_client' => $reservation->id_client,
                'date' => $reservation->date,
                'heure' => $reservation->heure,
                'etat' => $reservation->etat,
                'type' => $validatedData['type'],
                'payment_method' => $validatedData['payment_method'] ?? 'none',
                'advance_payment' => $validatedData['advance_payment'] ?? null,
                'stripe_payment_intent_id' => $validatedData['payment_intent_id'] ?? null
            ]);
            
            $response = [
                'success' => true,
                'message' => 'Réservation enregistrée avec succès',
                'data' => new ReservationResource($reservation)
            ];
            
            // Add expiration warning for pending reservations
            if ($reservationData['etat'] === 'en attente') {
                $response['expiration_warning'] = 'Attention: La réservation est en attente et sera automatiquement annulée après 1 heure si elle n\'est pas confirmée.';
            }

            return response()->json($response, 201);

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
                'num_res' => 'nullable|string|max:255',
                'advance_payment' => 'nullable|numeric',
                'payment_status' => 'nullable|string|in:paid,partially_paid,unpaid',
                'payment_method' => 'nullable|string|in:cash,online,stripe',
                'price' => 'nullable|numeric',
                'payment_intent_id' => 'nullable|string',
                'amount' => 'nullable|numeric',
                'currency' => 'nullable|string',
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
            
            // Check if there's a payment update
            $paymentUpdate = false;
            
            // If advance_payment is updated
            $advancePaymentUpdated = isset($validatedData['advance_payment']) && 
                                     $validatedData['advance_payment'] != $reservation->advance_payment;
                                     
            // If Stripe payment is provided
            $stripePaymentProvided = isset($validatedData['payment_method']) && 
                                     $validatedData['payment_method'] === 'stripe' && 
                                     isset($validatedData['payment_intent_id']);
                                     
            if ($advancePaymentUpdated || $stripePaymentProvided) {
                $paymentUpdate = true;
            }
            
            // For Stripe payments with 'paid' status, set etat to 'reserver'
            if (isset($validatedData['payment_method']) && 
                $validatedData['payment_method'] === 'stripe' && 
                isset($validatedData['payment_status']) && 
                $validatedData['payment_status'] === 'paid') {
                $validatedData['etat'] = 'reserver';
            }
            // If advance_payment is provided and payment_status is partially_paid, set status to 'en attente'
            else if (isset($validatedData['advance_payment']) && 
                $validatedData['advance_payment'] > 0 && 
                isset($validatedData['payment_status']) && 
                $validatedData['payment_status'] === 'partially_paid') {
                $validatedData['etat'] = 'en attente';
            }
            
            // If payment is made with Stripe, update advance_payment from amount if not specified
            if (!isset($validatedData['advance_payment']) && 
                isset($validatedData['amount']) && 
                isset($validatedData['payment_method']) && 
                $validatedData['payment_method'] === 'stripe') {
                $validatedData['advance_payment'] = $validatedData['amount'];
            }
                                     
            // Update the reservation
            $reservation->update($validatedData);
            
            // If payment details need to be updated
            if ($paymentUpdate) {
                $paymentStatus = isset($validatedData['payment_status']) ? 
                    ($validatedData['payment_status'] === 'paid' ? 'completed' : 'pending') : 
                    'pending';
                
                $paymentMethod = isset($validatedData['payment_method']) ? 
                    $validatedData['payment_method'] : 
                    'cash';
                    
                $fullPrice = isset($validatedData['price']) ? $validatedData['price'] : null;
                
                // Get terrain price if full price is not provided
                if (!$fullPrice) {
                    $terrain = \App\Models\Terrain::find($reservation->id_terrain);
                    $fullPrice = $terrain ? $terrain->prix : 0;
                }
                
                $amount = isset($validatedData['advance_payment']) ? 
                    $validatedData['advance_payment'] : 
                    (isset($validatedData['amount']) ? $validatedData['amount'] : 0);
                
                $currency = isset($validatedData['currency']) ? 
                    $validatedData['currency'] : 
                    'MAD';
                
                // Check if payment record already exists
                $payment = \App\Models\Payment::where('id_reservation', $id)->first();
                
                if ($payment) {
                    // Update existing payment
                    $paymentUpdateData = [
                        'amount' => $amount,
                        'status' => $paymentStatus,
                        'payment_method' => $paymentMethod,
                        'currency' => $currency,
                        'payment_details' => json_encode([
                            'full_price' => $fullPrice,
                            'advance_payment' => $amount,
                            'remaining' => $fullPrice - $amount,
                            'type' => $amount == $fullPrice ? 'full_payment' : 'advance_payment',
                            'updated_at' => now()->toDateTimeString()
                        ])
                    ];
                    
                    // Add payment_intent_id if provided
                    if (isset($validatedData['payment_intent_id'])) {
                        $paymentUpdateData['stripe_payment_intent_id'] = $validatedData['payment_intent_id'];
                        // Update payment details to include payment_intent_id
                        $paymentDetails = json_decode($paymentUpdateData['payment_details'], true);
                        $paymentDetails['payment_intent_id'] = $validatedData['payment_intent_id'];
                        $paymentUpdateData['payment_details'] = json_encode($paymentDetails);
                    }
                    
                    $payment->update($paymentUpdateData);
                    
                    Log::info('Payment record updated for reservation', [
                        'reservation_id' => $id,
                        'payment_id' => $payment->id_payment,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'payment_method' => $payment->payment_method,
                        'stripe_payment_intent_id' => $payment->stripe_payment_intent_id
                    ]);
                } else {
                    // Create new payment record
                    $paymentData = [
                        'amount' => $amount,
                        'status' => $paymentStatus,
                        'payment_method' => $paymentMethod,
                        'currency' => $currency,
                        'id_reservation' => $id,
                        'id_compte' => $reservation->id_client,
                        'payment_details' => json_encode([
                            'full_price' => $fullPrice,
                            'advance_payment' => $amount,
                            'remaining' => $fullPrice - $amount,
                            'type' => $amount == $fullPrice ? 'full_payment' : 'advance_payment'
                        ])
                    ];
                    
                    // Add payment_intent_id if provided
                    if (isset($validatedData['payment_intent_id'])) {
                        $paymentData['stripe_payment_intent_id'] = $validatedData['payment_intent_id'];
                        // Update payment details to include payment_intent_id
                        $paymentDetails = json_decode($paymentData['payment_details'], true);
                        $paymentDetails['payment_intent_id'] = $validatedData['payment_intent_id'];
                        $paymentData['payment_details'] = json_encode($paymentDetails);
                    }
                    
                    $payment = \App\Models\Payment::create($paymentData);
                    
                    Log::info('Payment record created for existing reservation', [
                        'reservation_id' => $id,
                        'payment_id' => $payment->id_payment,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'payment_method' => $payment->payment_method,
                        'stripe_payment_intent_id' => $payment->stripe_payment_intent_id
                    ]);
                }
                
                // Send payment confirmation email if status is 'paid'
                if (isset($validatedData['payment_status']) && 
                    $validatedData['payment_status'] === 'paid' && 
                    $reservation->client && 
                    $reservation->client->email) {
                    
                    // Get terrain name
                    $terrain = \App\Models\Terrain::find($reservation->id_terrain);
                    $terrainName = $terrain ? $terrain->nom_terrain : 'Unknown';
                    
                    $this->sendPaymentConfirmationEmail(
                        $reservation->client->email,
                        $reservation->client->nom ?? $reservation->Name ?? 'Client',
                        $reservation->num_res,
                        $reservation->date,
                        $reservation->heure,
                        $terrainName,
                        $reservation->etat,
                        $paymentMethod,
                        $amount,
                        $currency
                    );
                    
                    Log::info("Payment confirmation email sent to: " . $reservation->client->email);
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
                    'reservation.advance_payment',
                    'reservation.created_at',
                    'reservation.updated_at'
                ])
                ->leftJoin('compte', 'reservation.id_client', '=', 'compte.id_compte')
                ->leftJoin('terrain', 'reservation.id_terrain', '=', 'terrain.id_terrain')
                ->where('reservation.id_reservation', $id)
                ->first();
                
            // Get payment information
            $payment = DB::table('payments')
                ->where('id_reservation', $id)
                ->orderBy('created_at', 'desc')
                ->first();
                
            $paymentInfo = null;
            if ($payment) {
                $paymentInfo = [
                    'id_payment' => $payment->id_payment,
                    'amount' => $payment->amount,
                    'status' => $payment->status,
                    'payment_method' => $payment->payment_method,
                    'currency' => $payment->currency,
                    'stripe_payment_intent_id' => $payment->stripe_payment_intent_id,
                    'payment_details' => $payment->payment_details ? json_decode($payment->payment_details) : null,
                    'created_at' => $payment->created_at
                ];
            }
                
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
                'advance_payment' => $updatedReservation->advance_payment,
                'payment' => $paymentInfo,
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
                    // Get terrain name and price
                    $terrain = DB::table('terrain')
                        ->where('id_terrain', $reservation->id_terrain)
                        ->first();
                    
                    $terrainName = $terrain ? $terrain->nom_terrain : 'Unknown';
                    $terrainPrice = $terrain ? $terrain->prix : null;
                    
                    // Check if there's a payment record associated with this reservation
                    $payment = \App\Models\Payment::where('id_reservation', $reservation->id_reservation)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    $amount = null;
                    $currency = 'MAD';
                    $paymentMethod = 'cash';
                    
                    if ($payment) {
                        $amount = $payment->amount;
                        $currency = $payment->currency;
                        $paymentMethod = $payment->payment_method;
                    } elseif ($terrainPrice) {
                        $amount = $terrainPrice;
                    }
                    
                    // Send payment confirmation email with invoice
                    $this->sendPaymentConfirmationEmail(
                        $clientEmail,
                        $clientName,
                        $reservation->num_res,
                        $reservation->date,
                        $reservation->heure,
                        $terrainName,
                        $reservation->etat,
                        $paymentMethod,
                        $amount,
                        $currency
                    );
                    
                    Log::info("Reservation confirmation email with invoice sent to: " . $clientEmail);
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
     * Send payment confirmation email with invoice
     *
     * @param string $email
     * @param string $name
     * @param string $numRes
     * @param string $date
     * @param string $time
     * @param string $terrain
     * @param string $status
     * @param string $payment_method
     * @param float|null $amount
     * @param string|null $currency
     * @return void
     */
    protected function sendPaymentConfirmationEmail(
        string $email,
        string $name,
        string $numRes,
        string $date,
        string $time,
        string $terrain,
        string $status,
        string $payment_method,
        ?float $amount = null,
        ?string $currency = null
    ): void {
        try {
            Mail::to($email)->send(new \App\Mail\PaymentConfirmation(
                $name,
                $numRes,
                $date,
                $time,
                $terrain,
                $status,
                $payment_method,
                $amount,
                $currency
            ));
        } catch (\Exception $e) {
            Log::error("Failed to send payment confirmation email: " . $e->getMessage());
            // We're just logging the error, not stopping execution
        }
    }

    /**
     * Check if a user has reached the maximum number of reservations for a day (2 max)
     * Admins can bypass this limit
     *
     * @param int $clientId
     * @param string $date
     * @return bool
     */
    protected function hasReachedMaxDailyReservations($clientId, $date): bool
    {
        // Check if user is admin - admins can make unlimited reservations
        $user = Compte::find($clientId);
        if ($user && $user->hasRole('admin')) {
            return false; // Admins bypass the limit
        }
        
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