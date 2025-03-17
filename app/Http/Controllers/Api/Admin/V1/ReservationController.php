<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\ReservationResource;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = DB::table('reservation')
                ->select([
                    'reservation.id_reservation',
                    'reservation.id_client',
                    DB::raw('COALESCE(compte.nom, reservation.Name) AS client'),
                    'reservation.id_terrain',
                    'reservation.date',
                    'reservation.heure',
                    'reservation.etat'
                ])
                ->leftJoin('compte', 'reservation.id_client', '=', 'compte.id_compte');

            // Apply any filters from the request
            if ($request->has('id_terrain')) {
                $query->where('id_terrain', $request->input('id_terrain'));
            }

            if ($request->has('date')) {
                $query->where('date', $request->input('date'));
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
                    'client' => $reservation->client,
                    'id_client' => $reservation->id_client,
                    'id_terrain' => $reservation->id_terrain,
                    'date' => $reservation->date,
                    'heure' => $reservation->heure,
                    'etat' => $reservation->etat
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
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['client', 'terrain'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Reservation::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $reservation = $query->find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        return new ReservationResource($reservation);
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
                'Name' => 'nullable|string|max:255'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
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

            // Create new reservation based on user type
            $reservationData = [
                'id_terrain' => $validatedData['id_terrain'],
                'date' => $validatedData['date'],
                'heure' => $validatedData['heure'],
            ];

            if ($validatedData['type'] === 'admin') {
                $reservationData['etat'] = 'reserver';
                $reservationData['Name'] = $validatedData['Name'] ?? null;
                
                // If id_client is provided for admin reservation, include it
                if (isset($validatedData['id_client'])) {
                    $reservationData['id_client'] = $validatedData['id_client'];
                }
            } else {
                $reservationData['id_client'] = $validatedData['id_client'];
                $reservationData['etat'] = 'en attente';
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
                'id_terrain' => 'required|integer|exists:terrain,id_terrain',
                'date' => 'required|date',
                'heure' => 'required|date_format:H:i:s',
                'etat' => 'required|string|in:reserver,en attente',
                'Name' => 'nullable|string|max:20'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        try {
            $reservation->update($validatedData);
            return response()->json([
                'message' => 'Reservation updated successfully',
                'data' => new ReservationResource($reservation)
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
            $reservation->update($validatedData);
            return response()->json([
                'message' => 'Reservation status updated successfully',
                'data' => new ReservationResource($reservation)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Reservation status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 