<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\TournoiTeams;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\TournoiTeamsResource;

class TournoiTeamsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['tournoi', 'captain'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:team_name,descrption',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = TournoiTeams::query();

        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $teams = $query->paginate($paginationSize);

        return TournoiTeamsResource::collection($teams);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['tournoi', 'captain'];
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

        $query = TournoiTeams::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $team = $query->find($id);

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        return new TournoiTeamsResource($team);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_tournoi' => 'required|integer|exists:tournoi,id_tournoi',
                'team_name' => 'required|string|max:255',
                'descrption' => 'nullable|string',
                'capitain' => 'required|integer|exists:compte,id_compte',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $team = TournoiTeams::create($validatedData);
            return response()->json([
                'message' => 'Team created successfully',
                'data' => new TournoiTeamsResource($team)
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
                'id_tournoi' => 'required|integer|exists:tournoi,id_tournoi',
                'team_name' => 'required|string|max:255',
                'descrption' => 'nullable|string',
                'capitain' => 'required|integer|exists:compte,id_compte',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $team = TournoiTeams::find($id);

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        try {
            $team->update($validatedData);
            return response()->json([
                'message' => 'Team updated successfully',
                'data' => new TournoiTeamsResource($team)
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
        $team = TournoiTeams::find($id);

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
            $query->where('team_name', 'like', "%$search%")
                  ->orWhere('descrption', 'like', "%$search%");
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_teams');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['team_name', 'descrption'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_teams', 'desc');
        }
    }
}