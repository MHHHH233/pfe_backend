<?php

namespace App\Http\Controllers\Api\user\V1;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\ReservationResource;
use Illuminate\Support\Facades\DB;

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
                    $password = \Illuminate\Support\Str::random(10);
                    
                    $client = \App\Models\Compte::create([
                        'name' => $name ?? 'Guest',
                        'prenom' => $name ?? 'Guest',
                        'age' => 20,
                        'email' => $email,
                        'password' => \Illuminate\Support\Facades\Hash::make($password),
                        'telephone' => $telephone ?? '',
                        'pfp' => app(\App\Http\Controllers\Api\AuthController::class)->getRandomProfilePicture(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    // Assign user role
                    $client->assignRole('user');
                    
                    // TODO: Send email with account details and password
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
} 