<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Models\Teams;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\User\V1\TeamsResource;
use Illuminate\Support\Facades\DB;

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
                        $validIncludes = ['captain', 'members', 'ratings', 'player_team'];
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
                'my_teams' => 'nullable|in:true,false,1,0',
                'debug' => 'nullable|in:true,false,1,0',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Teams::query();

        // Get the authenticated user
        $user = $request->user();
        $userId = $user ? $user->id_compte : null;

        // Enable debugging if requested
        $debug = $request->has('debug') && in_array($request->input('debug'), ['true', '1']);
        $debugInfo = [];
        
        if ($debug) {
            $debugInfo['request_headers'] = $request->headers->all();
            $debugInfo['user_id'] = $userId;
            $debugInfo['authenticated'] = !is_null($user);
        }

        if ($request->has('my_teams') && 
            in_array($request->input('my_teams'), ['true', '1']) && 
            $userId) {
            
            // First get the player record for this user
            $player = \App\Models\Players::where('id_compte', $userId)->first();
            
            if ($debug) {
                $debugInfo['player'] = $player;
            }
            
            if ($player) {
                $playerId = $player->id_player;
                
                if ($debug) {
                    $debugInfo['player_id'] = $playerId;
                }
                
                // Show only teams where user is captain or member
                $query->where(function($q) use ($playerId) {
                    $q->where('capitain', $playerId)
                      ->orWhereHas('members', function($innerQ) use ($playerId) {
                          $innerQ->where('players.id_player', $playerId);
                      });
                });
            } else if ($debug) {
                $debugInfo['player_error'] = 'No player found for this user account';
            }
        } else {
            // Just list all teams if not filtering by my_teams
            if ($debug) {
                $debugInfo['query_type'] = 'all_teams';
            }
        }

        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        if ($debug) {
            $debugInfo['sql_query'] = $query->toSql();
            $debugInfo['query_bindings'] = $query->getBindings();
        }

        $teams = $query->get();
        
        if ($debug) {
            $debugInfo['teams_count'] = $teams->count();
        }
        
        // Add members count to each team
        foreach ($teams as $team) {
            $team->members_count = $team->members()->wherePivot('status', 'accepted')->count();
        }

        // Return debug info if requested
        if ($debug) {
            return response()->json([
                'debug' => $debugInfo,
                'data' => TeamsResource::collection($teams)
            ]);
        }

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
                'capitain' => 'required|exists:compte,id_compte',
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
        
        // Check if the captain already has a team
        $capitainId = $validatedData['capitain'];
        $existingTeam = Teams::where('capitain', $capitainId)->first();
        
        if ($existingTeam) {
            return response()->json([
                'error' => true,
                'message' => 'This player is already a captain of another team'
            ], 400);
        }

        try {
            if (!isset($validatedData['capitain'])) {
                $validatedData['capitain'] = $request->user()->id_compte;
            }
            
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
                'capitain' => 'nullable|exists:compte,id_compte',
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

        $team = Teams::where('id_teams', $id)
            ->where('capitain', $request->user()->id_compte)
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
            if ($team->capitain === request()->user()->id_player) {
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

    /**
     * Display the specified team.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['captain', 'members', 'ratings', 'player_team'];
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

        $query = Teams::where('id_teams', $id);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $team = $query->first();

        if (!$team) {
            return response()->json([
                'message' => 'Team not found'
            ], 404);
        }
        
        // Add members count
        $team->members_count = $team->members()->wherePivot('status', 'accepted')->count();

        return new TeamsResource($team);
    }

    /**
     * Add a member to a team (captain only).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function addMember(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'player_id' => 'required|exists:players,id_player',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        // Get the team
        $team = Teams::find($id);
        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        // Check if the authenticated user is the team captain
        $user = $request->user();
        $player = \App\Models\Players::where('id_compte', $user->id_compte)->first();
        
        if (!$player || $team->capitain != $player->id_player) {
            return response()->json(['message' => 'Only team captain can add members'], 403);
        }

        // Check if the player is already a member
        if ($team->members()->where('players.id_player', $validatedData['player_id'])->exists()) {
            return response()->json([
                'error' => true,
                'message' => 'Player is already a member of this team'
            ], 409);
        }

        try {
            // Add the player to the team
            $team->members()->attach($validatedData['player_id']);

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
     * Remove a member from a team (captain only).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function removeMember(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'player_id' => 'required|exists:players,id_player',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        // Get the team
        $team = Teams::find($id);
        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        // Check if the authenticated user is the team captain
        $user = $request->user();
        
        if ($team->capitain != $user->id_compte) {
            return response()->json(['message' => 'Only team captain can remove members'], 403);
        }

        // Cannot remove the captain
        if ($validatedData['player_id'] == $team->capitain) {
            return response()->json([
                'error' => true,
                'message' => 'Cannot remove the team captain'
            ], 400);
        }

        // Check if the player is a member
        if (!$team->members()->where('players.id_player', $validatedData['player_id'])->exists()) {
            return response()->json([
                'error' => true,
                'message' => 'Player is not a member of this team'
            ], 404);
        }

        try {
            // Remove the player from the team
            $team->members()->detach($validatedData['player_id']);

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
     * Get team members.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getMembers($id): JsonResponse
    {
        // Get the team
        $team = Teams::with(['members' => function($query) {
            $query->wherePivot('status', 'accepted');
        }])->find($id);
        
        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'captain_id' => $team->capitain,
                'members' => $team->members,
                'members_count' => $team->members->count()
            ]
        ]);
    }

    /**
     * Transfer team captaincy (captain only).
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function transferCaptaincy(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'new_captain_id' => 'required|exists:players,id_player',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        // Get the team
        $team = Teams::find($id);
        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        // Check if the authenticated user is the team captain
        $user = $request->user();
        $player = \App\Models\Players::where('id_compte', $user->id_compte)->first();
        
        if (!$player || $team->capitain != $player->id_player) {
            return response()->json(['message' => 'Only team captain can transfer captaincy'], 403);
        }

        // Check if the new captain is a member
        if (!$team->members()->where('players.id_player', $validatedData['new_captain_id'])->exists() &&
            $team->capitain != $validatedData['new_captain_id']) {
            return response()->json([
                'error' => true,
                'message' => 'New captain must be a team member'
            ], 400);
        }

        try {
            // Update the team captain
            $team->capitain = $validatedData['new_captain_id'];
            $team->save();

            return response()->json([
                'success' => true,
                'message' => 'Team captaincy transferred successfully',
                'data' => new TeamsResource($team)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to transfer team captaincy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get team info for a specific player (either as captain or member).
     * Returns team info if player has a team, otherwise returns an appropriate message.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myTeam(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'player_id' => 'required|exists:players,id_player',
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['captain', 'members', 'ratings', 'player_team'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ]
            ]);

            $playerId = $validatedData['player_id'];
            
            // Get player info with their account details
            $player = \App\Models\Players::with('compte')->find($playerId);
            if (!$player) {
                return response()->json(['message' => 'Player not found'], 404);
            }
            
            // First try to find a team where the player's account is captain
            $team = Teams::where('capitain', $player->id_compte)->first();
            $isCaptain = !is_null($team);
            
            // If not a captain, look for team membership
            if (!$team) {
                $playerTeam = \App\Models\PlayerTeam::where('id_player', $playerId)
                    ->where('status', 'accepted')
                    ->first();
                
                if ($playerTeam) {
                    $team = Teams::find($playerTeam->id_teams);
                }
                // If no team found, return message
                else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Player does not belong to any team'
                    ], 404);
                }
            }
            
            // Always load members with their compte relationship
            $team->load(['members.compte']);
            
            // Load additional includes if requested
            if ($request->has('include')) {
                $includes = explode(',', $request->input('include'));
                // Remove 'members' since we've already loaded it with compte
                $includes = array_diff($includes, ['members']);
                if (!empty($includes)) {
                    $team->load($includes);
                }
            }
            
            // Count members
            $membersCount = $team->members()
                ->wherePivot('status', 'accepted')
                ->count();
            
            // Get members with their user IDs
            $members = $team->members->map(function($member) {
                return [
                    'id_player' => $member->id_player,
                    'id_compte' => $member->compte->id_compte,
                    'nom' => $member->compte->nom,
                    'prenom' => $member->compte->prenom,
                    'position' => $member->position,
                    'rating' => $member->rating,
                    'total_matches' => $member->total_matches,
                    'starting_time' => $member->starting_time,
                    'finishing_time' => $member->finishing_time,
                    'misses' => $member->misses,
                    'invites_accepted' => $member->invites_accepted,
                    'invites_refused' => $member->invites_refused,
                    'total_invites' => $member->total_invites
                ];
            });
            
            // Transform resource
            $teamResource = new TeamsResource($team);
            $teamData = $teamResource->toArray($request);
            $teamData['members'] = $members;
            $teamData['starting_time'] = $team->starting_time;
            $teamData['finishing_time'] = $team->finishing_time;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'team' => $teamData,
                    'is_captain' => $isCaptain,
                    'members_count' => $membersCount,
                    'player_id' => $playerId
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve team information',
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

        $team->delete();

        return response()->json(['message' => 'Team deleted successfully']);
    }
} 