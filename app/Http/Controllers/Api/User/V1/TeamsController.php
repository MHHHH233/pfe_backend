<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Models\Teams;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\User\V1\TeamsResource;

class TeamsController extends Controller
{
    /**
     * Display user's teams or search for teams.
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['captain', 'members', 'ratings'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'search' => 'nullable|string',
                'sort_by' => 'nullable|string|in:rating,total_matches',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Teams::query();

        if ($request->has('my_teams')) {
            // Show only teams where user is captain or member
            $query->where('id_captain', $request->user()->id_player)
                  ->orWhereHas('members', function($q) use ($request) {
                      $q->where('id_player', $request->user()->id_player);
                  });
        }

        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $teams = $query->get();

        return TeamsResource::collection($teams);
    }

    /**
     * Create a new team.
     *
     * @param Request $request
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'team_name' => 'required|string|max:100|unique:teams,team_name',
                'description' => 'nullable|string',
                'logo' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $validatedData['id_captain'] = $request->user()->id_player;
            $validatedData['rating'] = 0;
            $validatedData['total_matches'] = 0;

            $team = Teams::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Team created successfully',
                'data' => new TeamsResource($team)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update team details (captain only).
     *
     * @param Request $request
     * @param int $id
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'team_name' => 'required|string|max:100|unique:teams,team_name,' . $id . ',id_teams',
                'description' => 'nullable|string',
                'logo' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $team = Teams::where('id_teams', $id)
            ->where('id_captain', $request->user()->id_player)
            ->first();

        if (!$team) {
            return response()->json([
                'message' => 'Team not found or you are not the captain'
            ], 404);
        }

        try {
            $team->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Team updated successfully',
                'data' => new TeamsResource($team)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Join a team.
     *
     * @param Request $request
     * @param int $id
     */
    public function join($id): JsonResponse
    {
        $team = Teams::find($id);

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        try {
            // Check if already a member
            if ($team->members()->where('id_player', request()->user()->id_player)->exists()) {
                return response()->json([
                    'error' => true,
                    'message' => 'You are already a member of this team'
                ], 409);
            }

            $team->members()->attach(request()->user()->id_player);

            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the team'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to join team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Leave a team.
     *
     * @param int $id
     */
    public function leave($id): JsonResponse
    {
        $team = Teams::find($id);

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        try {
            if ($team->id_captain === request()->user()->id_player) {
                return response()->json([
                    'error' => true,
                    'message' => 'Team captain cannot leave the team'
                ], 400);
            }

            $team->members()->detach(request()->user()->id_player);

            return response()->json([
                'success' => true,
                'message' => 'Successfully left the team'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to leave team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply search to the query.
     *
     * @param Request $request
     * @param $query
     */
    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('team_name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
        }
    }

    /**
     * Apply sorting to the query.
     *
     * @param Request $request
     * @param $query
     */
    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'rating');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['rating', 'total_matches'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('rating', 'desc');
        }
    }
} 