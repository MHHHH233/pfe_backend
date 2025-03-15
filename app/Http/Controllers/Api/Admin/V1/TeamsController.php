<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Teams;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\TeamsResource;

class TeamsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['captain', 'ratings'];
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
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Teams::query();

        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $teams = $query->paginate($paginationSize);

        return TeamsResource::collection($teams);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['captain', 'ratings'];
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

        $query = Teams::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $team = $query->find($id);

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        return new TeamsResource($team);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'capitain' => 'required|integer|exists:compte,id_compte',
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

        try {
            $team = Teams::create($validatedData);
            return response()->json([
                'message' => 'Team created successfully',
                'data' => new TeamsResource($team)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'capitain' => 'required|integer|exists:compte,id_compte',
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

        $team = Teams::find($id);

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        try {
            $team->update($validatedData);
            return response()->json([
                'message' => 'Team updated successfully',
                'data' => new TeamsResource($team)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $team = Teams::find($id);

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        try {
            $team->delete();
            return response()->json([
                'message' => 'Team deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('captain', function($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%");
            });
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_teams');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['rating', 'total_matches'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_teams', 'desc');
        }
    }
} 