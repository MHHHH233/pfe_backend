<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Players;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\PlayersResource;

class PlayersController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['compte', 'ratings'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:rating,total_matches',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
                'position' => 'nullable|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Players::query();

        $this->applyFilters($request, $query);
        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $players = $query->paginate($paginationSize);

        return PlayersResource::collection($players);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['compte', 'ratings'];
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

        $query = Players::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $player = $query->find($id);

        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        return new PlayersResource($player);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_compte' => [
                    'required',
                    'integer',
                    'exists:compte,id_compte',
                    'unique:players,id_compte',
                ],
                'position' => 'required|string|max:50',
                'total_matches' => 'nullable|integer',
                'rating' => 'nullable|integer',
                'starting_time' => 'nullable|date_format:H:i:s',
                'finishing_time' => 'nullable|date_format:H:i:s|after:starting_time',
                'misses' => 'nullable|integer',
                'invites_accepted' => 'nullable|integer',
                'invites_refused' => 'nullable|integer',
                'total_invites' => 'nullable|integer'
            ]);

            try {
                $player = Players::create($validatedData);
                return response()->json([
                    'message' => 'Player created successfully',
                    'data' => new PlayersResource($player)
                ], 201);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to create Player',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['id_compte']) && str_contains(implode(' ', $errors['id_compte']), 'taken')) {
                return response()->json([
                    'error' => 'A player with this account already exists.',
                    'details' => $errors
                ], 422);
            }
            return response()->json(['error' => $errors], 400);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_compte' => 'required|integer|exists:compte,id_compte',
                'position' => 'required|string|max:50',
                'total_matches' => 'nullable|integer',
                'rating' => 'nullable|integer',
                'starting_time' => 'nullable|date_format:H:i:s',
                'finishing_time' => 'nullable|date_format:H:i:s|after:starting_time',
                'misses' => 'nullable|integer',
                'invites_accepted' => 'nullable|integer',
                'invites_refused' => 'nullable|integer',
                'total_invites' => 'nullable|integer'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $player = Players::find($id);

        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        try {
            $player->update($validatedData);
            return response()->json([
                'message' => 'Player updated successfully',
                'data' => new PlayersResource($player)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Player',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $player = Players::find($id);

        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        try {
            $player->delete();
            return response()->json([
                'message' => 'Player deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Player',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('position')) {
            $query->where('position', $request->input('position'));
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('compte', function($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%");
            });
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_player');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['rating', 'total_matches'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_player', 'desc');
        }
    }
} 