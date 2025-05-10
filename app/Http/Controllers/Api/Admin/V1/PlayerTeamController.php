<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\PlayerTeam;
use App\Models\Players;
use App\Models\Teams;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\PlayerTeamResource;

class PlayerTeamController extends Controller
{
    /**
     * Display a listing of player-team relationships.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'player_id' => 'nullable|exists:players,id_player',
                'team_id' => 'nullable|exists:teams,id_teams',
                'status' => 'nullable|in:pending,accepted,refused',
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['player', 'team', 'player.compte'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = PlayerTeam::query();

        // Filter by player_id if provided
        if ($request->has('player_id')) {
            $query->where('id_player', $request->input('player_id'));
        }

        // Filter by team_id if provided
        if ($request->has('team_id')) {
            $query->where('id_teams', $request->input('team_id'));
        }

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Include relationships if requested
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $playerTeams = $query->paginate($paginationSize);

        return PlayerTeamResource::collection($playerTeams);
    }

    /**
     * Store a newly created player-team relationship.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'player_id' => 'required|exists:players,id_player',
                'team_id' => 'required|exists:teams,id_teams',
                'status' => 'nullable|in:pending,accepted,refused',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        // Check if player exists
        $player = Players::find($validatedData['player_id']);
        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        // Check if team exists
        $team = Teams::find($validatedData['team_id']);
        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        // Check if the relationship already exists
        $exists = PlayerTeam::where('id_player', $validatedData['player_id'])
            ->where('id_teams', $validatedData['team_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => true,
                'message' => 'Player is already a member of this team'
            ], 409);
        }

        try {
            $status = $validatedData['status'] ?? PlayerTeam::STATUS_PENDING;
            
            // If player is the captain, automatically set status to accepted
            if ($team->capitain == $validatedData['player_id']) {
                $status = PlayerTeam::STATUS_ACCEPTED;
            }
            
            $playerTeam = PlayerTeam::create([
                'id_player' => $validatedData['player_id'],
                'id_teams' => $validatedData['team_id'],
                'status' => $status,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Player added to team successfully',
                'data' => new PlayerTeamResource($playerTeam)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add player to team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified player-team relationship.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse|PlayerTeamResource
     */
    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['player', 'team', 'player.compte'];
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

        $query = PlayerTeam::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $playerTeam = $query->find($id);

        if (!$playerTeam) {
            return response()->json(['message' => 'Player-Team relationship not found'], 404);
        }

        return new PlayerTeamResource($playerTeam);
    }

    /**
     * Update the specified player-team relationship.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        $playerTeam = PlayerTeam::find($id);

        if (!$playerTeam) {
            return response()->json(['message' => 'Player-Team relationship not found'], 404);
        }

        try {
            $validatedData = $request->validate([
                'status' => 'nullable|in:pending,accepted,refused',
            ]);

            if (isset($validatedData['status'])) {
                $playerTeam->status = $validatedData['status'];
            }

            $playerTeam->save();

            return response()->json([
                'success' => true,
                'message' => 'Player-Team relationship updated successfully',
                'data' => new PlayerTeamResource($playerTeam)
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Player-Team relationship',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified player-team relationship.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        $playerTeam = PlayerTeam::find($id);

        if (!$playerTeam) {
            return response()->json(['message' => 'Player-Team relationship not found'], 404);
        }

        // Check if player is the captain (cannot remove captain)
        $player = Players::find($playerTeam->id_player);
        $team = Teams::find($playerTeam->id_teams);

        if ($player && $team && $team->capitain == $player->id_player) {
            return response()->json([
                'error' => true,
                'message' => 'Team captain cannot be removed from the team'
            ], 400);
        }

        try {
            $playerTeam->delete();

            return response()->json([
                'success' => true,
                'message' => 'Player removed from team successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove player from team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept a pending player-team invitation.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function accept($id): JsonResponse
    {
        $playerTeam = PlayerTeam::find($id);

        if (!$playerTeam) {
            return response()->json(['message' => 'Player-Team relationship not found'], 404);
        }

        if ($playerTeam->status !== PlayerTeam::STATUS_PENDING) {
            return response()->json([
                'error' => true,
                'message' => 'Cannot accept invitation that is not in pending state'
            ], 400);
        }

        try {
            $playerTeam->status = PlayerTeam::STATUS_ACCEPTED;
            $playerTeam->save();

            return response()->json([
                'success' => true,
                'message' => 'Team invitation accepted successfully',
                'data' => new PlayerTeamResource($playerTeam)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to accept team invitation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Refuse a pending player-team invitation.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function refuse($id): JsonResponse
    {
        $playerTeam = PlayerTeam::find($id);

        if (!$playerTeam) {
            return response()->json(['message' => 'Player-Team relationship not found'], 404);
        }

        if ($playerTeam->status !== PlayerTeam::STATUS_PENDING) {
            return response()->json([
                'error' => true,
                'message' => 'Cannot refuse invitation that is not in pending state'
            ], 400);
        }

        try {
            $playerTeam->status = PlayerTeam::STATUS_REFUSED;
            $playerTeam->save();

            return response()->json([
                'success' => true,
                'message' => 'Team invitation refused successfully',
                'data' => new PlayerTeamResource($playerTeam)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to refuse team invitation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk add players to a team.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAdd(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'team_id' => 'required|exists:teams,id_teams',
                'player_ids' => 'required|array',
                'player_ids.*' => 'required|exists:players,id_player',
                'status' => 'nullable|in:pending,accepted,refused',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $team = Teams::find($validatedData['team_id']);
        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        $status = $validatedData['status'] ?? PlayerTeam::STATUS_PENDING;
        $addedCount = 0;
        $alreadyInTeamCount = 0;
        $errors = [];

        foreach ($validatedData['player_ids'] as $playerId) {
            // Check if the relationship already exists
            $exists = PlayerTeam::where('id_player', $playerId)
                ->where('id_teams', $validatedData['team_id'])
                ->exists();

            if ($exists) {
                $alreadyInTeamCount++;
                continue;
            }

            try {
                // If player is the captain, automatically set status to accepted
                $currentStatus = $status;
                if ($team->capitain == $playerId) {
                    $currentStatus = PlayerTeam::STATUS_ACCEPTED;
                }
                
                PlayerTeam::create([
                    'id_player' => $playerId,
                    'id_teams' => $validatedData['team_id'],
                    'status' => $currentStatus
                ]);
                $addedCount++;
            } catch (\Exception $e) {
                $errors[] = "Failed to add player ID $playerId: " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "$addedCount players added to team successfully. $alreadyInTeamCount players were already in the team.",
            'errors' => $errors
        ]);
    }

    /**
     * Bulk remove players from a team.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkRemove(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'team_id' => 'required|exists:teams,id_teams',
                'player_ids' => 'required|array',
                'player_ids.*' => 'required|exists:players,id_player',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $team = Teams::find($validatedData['team_id']);
        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        // Check if any of the players is the captain
        if (in_array($team->capitain, $validatedData['player_ids'])) {
            return response()->json([
                'error' => true,
                'message' => 'Team captain cannot be removed from the team'
            ], 400);
        }

        try {
            $removedCount = PlayerTeam::where('id_teams', $validatedData['team_id'])
                ->whereIn('id_player', $validatedData['player_ids'])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "$removedCount players removed from team successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove players from team',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Bulk update player-team status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'player_team_ids' => 'required|array',
                'player_team_ids.*' => 'required|exists:player_team,id',
                'status' => 'required|in:pending,accepted,refused',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $updatedCount = PlayerTeam::whereIn('id', $validatedData['player_team_ids'])
                ->update(['status' => $validatedData['status']]);

            return response()->json([
                'success' => true,
                'message' => "$updatedCount player-team relationships updated successfully"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update player-team status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 