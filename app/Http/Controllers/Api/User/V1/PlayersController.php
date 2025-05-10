<?php

namespace App\Http\Controllers\api\user\V1;


use App\Http\Controllers\Controller;
use App\Models\Players;
use App\Models\Teams;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\PlayersResource;

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
                        $validIncludes = ['compte', 'ratings', 'teams', 'stats'];
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
                'position' => 'nullable|string',
                'user_id' => 'nullable|exists:compte,id_compte'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Players::query();

        // Always include compte relationship for better search results
        $query->with('compte');

        // Filter by user_id if provided
        if ($request->has('user_id')) {
            $query->where('id_compte', $request->input('user_id'));
        }
        
        $this->applyFilters($request, $query);
        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            
            // Remove 'compte' if it's already included
            if (($key = array_search('compte', $includes)) !== false) {
                unset($includes[$key]);
            }
            
            if (!empty($includes)) {
                $query->with($includes);
            }
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
                        $validIncludes = ['compte', 'ratings', 'teams', 'stats'];
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
        
        // Always include compte relationship
        $query->with('compte');

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            
            // Remove 'compte' if it's already included
            if (($key = array_search('compte', $includes)) !== false) {
                unset($includes[$key]);
            }
            
            if (!empty($includes)) {
                $query->with($includes);
            }
        }

        $player = $query->find($id);

        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        return new PlayersResource($player);
    }

    /**
     * Store a newly created player.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_compte' => 'required|exists:compte,id_compte',
                'position' => 'required|string',
                'starting_time' => 'nullable|date_format:H:i:s',
                'finishing_time' => 'nullable|date_format:H:i:s',
                'misses' => 'nullable|integer',
                'invites_accepted' => 'nullable|integer',
                'invites_refused' => 'nullable|integer',
                'total_invites' => 'nullable|integer'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            // Set default values for new player
            $validatedData['rating'] = 0;
            $validatedData['total_matches'] = 0;
            
            $player = Players::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Player created successfully',
                'data' => new PlayersResource($player)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create player',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified player.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'position' => 'nullable|string',
                'starting_time' => 'nullable|date_format:H:i:s',
                'finishing_time' => 'nullable|date_format:H:i:s',
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

        // Ensure user can only update their own profile
        if ($player->id_compte != $request->user()->id_compte) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $player->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Player updated successfully',
                'data' => new PlayersResource($player)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update player',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified player.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $player = Players::find($id);

        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        // Ensure user can only delete their own profile
        if ($player->id_compte != request()->user()->id_compte) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $player->delete();

            return response()->json([
                'success' => true,
                'message' => 'Player deleted successfully'
            ], 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete player',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add player to a team.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function addToTeam(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'team_id' => 'required|exists:teams,id_teams',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $player = Players::find($id);
        $team = Teams::find($validatedData['team_id']);

        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        // Check if player is already in the team
        if ($player->teams()->where('id_teams', $team->id_teams)->exists()) {
            return response()->json([
                'error' => true,
                'message' => 'Player is already a member of this team'
            ], 409);
        }

        try {
            $player->teams()->attach($team->id_teams);

            return response()->json([
                'success' => true,
                'message' => 'Player added to team successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add player to team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove player from a team.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function removeFromTeam(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'team_id' => 'required|exists:teams,id_teams',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $player = Players::find($id);
        $team = Teams::find($validatedData['team_id']);

        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        // Check if player is in the team
        if (!$player->teams()->where('id_teams', $team->id_teams)->exists()) {
            return response()->json([
                'error' => true,
                'message' => 'Player is not a member of this team'
            ], 409);
        }

        // Check if player is the captain (cannot remove captain)
        if ($team->capitain === $player->id_player) {
            return response()->json([
                'error' => true,
                'message' => 'Team captain cannot be removed from the team'
            ], 400);
        }

        try {
            $player->teams()->detach($team->id_teams);

            return response()->json([
                'success' => true,
                'message' => 'Player removed from team successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove player from team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get teams for a player.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getTeams($id): JsonResponse
    {
        $player = Players::with('teams')->find($id);

        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $player->teams
        ]);
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
                  ->orWhere('prenom', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere(function($subQuery) use ($search) {
                      // Search for full name (nom + prenom)
                      $subQuery->whereRaw("CONCAT(nom, ' ', prenom) like ?", ["%$search%"])
                              ->orWhereRaw("CONCAT(prenom, ' ', nom) like ?", ["%$search%"]);
                  });
            })
            ->orWhere('position', 'like', "%$search%"); // Also search by position
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