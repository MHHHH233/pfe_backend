<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Models\PlayerTeam;
use App\Models\Players;
use App\Models\Teams;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\User\V1\PlayerTeamResource;
use App\Models\PlayerRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        $playerTeams = $query->paginate(10);

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
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        // Check if player exists - removed authentication check
        $player = Players::find($validatedData['player_id']);
        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        // Check if team exists
        $team = Teams::find($validatedData['team_id']);
        if (!$team) {
            return response()->json(['message' => 'Team not found'], 404);
        }

        // Check if player is already in any team
        $existingTeam = PlayerTeam::where('id_player', $validatedData['player_id'])
            ->where('status', PlayerTeam::STATUS_ACCEPTED)
            ->first();

        if ($existingTeam) {
            return response()->json([
                'error' => true,
                'message' => 'Player is already a member of another team'
            ], 409);
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
            // If the player is the captain, set status to accepted directly
            $status = PlayerTeam::STATUS_PENDING;
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
                'message' => 'Team join request sent successfully',
                'data' => new PlayerTeamResource($playerTeam)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to request team join',
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

        // Check if the user is authorized to view this relationship
        $player = Players::find($playerTeam->id_player);
        if (!$player || $player->id_compte != $request->user()->id_compte) {
            // Allow team captains to view their team members
            $team = Teams::find($playerTeam->id_teams);
            if (!$team || $team->capitain != $request->user()->id_player) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        return new PlayerTeamResource($playerTeam);
    }

    /**
     * Remove the specified player-team relationship.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy($id, Request $request): JsonResponse
    {
        $playerTeam = PlayerTeam::find($id);

        if (!$playerTeam) {
            return response()->json(['message' => 'Player-Team relationship not found'], 404);
        }

        // Check if user is authorized (either the player or the team captain)
        $player = Players::find($playerTeam->id_player);
        $team = Teams::find($playerTeam->id_teams);

        $isPlayer = $player && $player->id_compte == $request->user()->id_compte;
        $isCaptain = $team && $team->capitain == $request->user()->id_player;

        if (!$isPlayer && !$isCaptain) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Cannot remove team captain
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
     * Accept a team invitation
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function acceptInvitation($id, Request $request): JsonResponse
    {
        $playerTeam = PlayerTeam::find($id);

        if (!$playerTeam) {
            return response()->json(['message' => 'Team invitation not found'], 404);
        }

        // Check if this invitation belongs to the authenticated user
        $player = Players::find($playerTeam->id_player);
        if (!$player || $player->id_compte != $request->user()->id_compte) {
            return response()->json(['message' => 'Unauthorized'], 403);
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
     * Refuse a team invitation
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function refuseInvitation($id, Request $request): JsonResponse
    {
        $playerTeam = PlayerTeam::find($id);

        if (!$playerTeam) {
            return response()->json(['message' => 'Team invitation not found'], 404);
        }

        // Check if this invitation belongs to the authenticated user
        $player = Players::find($playerTeam->id_player);
        if (!$player || $player->id_compte != $request->user()->id_compte) {
            return response()->json(['message' => 'Unauthorized'], 403);
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
     * Process a team join request (for team captains)
     * 
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function processJoinRequest($id, Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|in:accepted,refused',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $playerTeam = PlayerTeam::find($id);

        if (!$playerTeam) {
            return response()->json(['message' => 'Join request not found'], 404);
        }

        // Ensure this is a pending request
        if ($playerTeam->status !== PlayerTeam::STATUS_PENDING) {
            return response()->json([
                'error' => true,
                'message' => 'This request is not in pending state'
            ], 400);
        }

        // Check if user is the team captain
        $team = Teams::find($playerTeam->id_teams);
        if (!$team || $team->capitain != $request->user()->id_player) {
            return response()->json(['message' => 'Only team captain can process join requests'], 403);
        }

        try {
            $playerTeam->status = $validatedData['status'];
            $playerTeam->save();

            $message = $validatedData['status'] === PlayerTeam::STATUS_ACCEPTED 
                ? 'Join request accepted successfully' 
                : 'Join request refused successfully';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => new PlayerTeamResource($playerTeam)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process join request',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Invite a player to join a team.
     *
     * @param Request $request
     * @param int $id Team ID
     * @return JsonResponse
     */
    public function invite(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'player_id' => 'required|exists:players,id_player',
                'message' => 'nullable|string|max:255',
                'expires_at' => 'nullable|date|after:now',
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
        $authPlayer = Players::where('id_compte', $request->user()->id_compte)->first();
        if (!$authPlayer || $team->capitain != $authPlayer->id_player) {
            return response()->json(['message' => 'Only team captain can send invitations'], 403);
        }

        // Get the player to invite
        $player = Players::find($validatedData['player_id']);
        if (!$player) {
            return response()->json(['message' => 'Player not found'], 404);
        }

        // Check if player is already in the team
        $existingMembership = PlayerTeam::where('id_player', $player->id_player)
            ->where('id_teams', $team->id_teams)
            ->exists();

        if ($existingMembership) {
            return response()->json([
                'message' => 'Player is already a member of this team'
            ], 409);
        }

        // Create direct entry in player_team with pending status 
        try {
            DB::beginTransaction();

            // Create the player_team entry with pending status
            $playerTeam = PlayerTeam::create([
                'id_player' => $player->id_player,
                'id_teams' => $team->id_teams,
                'status' => PlayerTeam::STATUS_PENDING
            ]);

            // Also create the invitation record in the requests table for compatibility
            $invitation = new PlayerRequest();
            $invitation->sender = $authPlayer->id_player;
            $invitation->receiver = $player->id_player;
            $invitation->message = $validatedData['message'] ?? "You've been invited to join team: {$team->team_name}";
            $invitation->status = PlayerRequest::STATUS_PENDING;
            $invitation->expires_at = $validatedData['expires_at'] ?? Carbon::now()->addDays(7);
            $invitation->match_date = Carbon::now(); // Using current date as this is not for a match
            $invitation->starting_time = Carbon::now()->format('H:i:s');
            $invitation->team_id = $team->id_teams;
            $invitation->request_type = PlayerRequest::TYPE_TEAM; // Set request type to team invitation
            $invitation->player_team_id = $playerTeam->id; // Link to the player_team record

            $invitation->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invitation sent successfully',
                'data' => new PlayerTeamResource($playerTeam)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to send invitation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending invitations for the authenticated user.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getPendingInvitations(Request $request)
    {
        // Get the player ID for the authenticated user
        $player = Players::where('id_compte', $request->user()->id_compte)->first();
        
        if (!$player) {
            return response()->json(['message' => 'Player profile not found'], 404);
        }

        $query = PlayerTeam::where('id_player', $player->id_player)
            ->where('status', PlayerTeam::STATUS_PENDING)
            ->with(['team']);

        $invitations = $query->paginate(10);

        return PlayerTeamResource::collection($invitations);
    }

    /**
     * Get pending join requests for teams where user is captain.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function getPendingJoinRequests(Request $request)
    {
        // Get the player ID for the authenticated user
        $player = Players::where('id_compte', $request->user()->id_compte)->first();
        
        if (!$player) {
            return response()->json(['message' => 'Player profile not found'], 404);
        }

        // Get teams where the player is captain
        $teams = Teams::where('capitain', $player->id_player)->pluck('id_teams');
        
        if ($teams->isEmpty()) {
            return response()->json(['message' => 'You are not a captain of any team'], 400);
        }

        $query = PlayerTeam::whereIn('id_teams', $teams)
            ->where('status', PlayerTeam::STATUS_PENDING)
            ->with(['player', 'player.compte']);

        $joinRequests = $query->paginate(10);

        return PlayerTeamResource::collection($joinRequests);
    }

    /**
     * Get a specific member of a team
     *
     * @param int $id Team ID
     * @param int $id_player Player ID
     * @param Request $request
     * @return JsonResponse
     */
    public function getMember($id, $id_player, Request $request): JsonResponse
    {
        // Find the player-team relationship
        $playerTeam = PlayerTeam::where('id_teams', $id)
            ->where('id_player', $id_player)
            ->where('status', PlayerTeam::STATUS_ACCEPTED)
            ->with(['player', 'team'])
            ->first();

        if (!$playerTeam) {
            return response()->json(['message' => 'Team member not found'], 404);
        }

        // Check if the authenticated user has permission to view this information
        $authPlayer = Players::where('id_compte', $request->user()->id_compte)->first();
        
        // Allow access if the user is:
        // 1. The team captain
        // 2. The member being viewed
        // 3. Another team member
        $isTeamCaptain = $playerTeam->team->capitain === $authPlayer->id_player;
        $isRequestedMember = $id_player === $authPlayer->id_player;
        $isTeamMember = PlayerTeam::where('id_teams', $id)
            ->where('id_player', $authPlayer->id_player)
            ->where('status', PlayerTeam::STATUS_ACCEPTED)
            ->exists();

        if (!$isTeamCaptain && !$isRequestedMember && !$isTeamMember) {
            return response()->json(['message' => 'Unauthorized to view this team member'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new PlayerTeamResource($playerTeam)
        ]);
    }

    /**
     * Remove a member from a team
     *
     * @param int $id Team ID
     * @param int $id_player Player ID to remove
     * @param Request $request
     * @return JsonResponse
     */
    public function removeMember($id, $id_player, Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'captain_id' => 'required|exists:players,id_player'
            ]);

            // Find the player-team relationship
            $playerTeam = PlayerTeam::where('id_teams', $id)
                ->where('id_player', $id_player)
                ->where('status', PlayerTeam::STATUS_ACCEPTED)
                ->with(['team'])
                ->first();

            if (!$playerTeam) {
                return response()->json(['message' => 'Team member not found'], 404);
            }

            // Get the team to check captain
            $team = Teams::find($id);
            if (!$team) {
                return response()->json(['message' => 'Team not found'], 404);
            }

            // Get all player profiles associated with the authenticated user's account
            $authPlayerIds = Players::where('id_compte', $request->user()->id_compte)
                ->pluck('id_player')
                ->toArray();

            if (empty($authPlayerIds)) {
                return response()->json(['message' => 'Player profile not found'], 404);
            }

            // Verify that the provided captain ID matches the team's captain
            if ($team->capitain != $validatedData['captain_id']) {
                return response()->json([
                    'message' => 'Invalid captain ID provided',
                    'debug' => [
                        'provided_captain_id' => $validatedData['captain_id'],
                        'actual_captain_id' => $team->capitain
                    ]
                ], 400);
            }

            // Only allow team captain or the member themselves to remove the member
            $isTeamCaptain = in_array($validatedData['captain_id'], $authPlayerIds);
            $isSelfRemoval = in_array($id_player, $authPlayerIds);

            // Debug information
            Log::info('Remove Member Authorization Check', [
                'auth_player_ids' => $authPlayerIds,
                'team_captain_id' => $team->capitain,
                'provided_captain_id' => $validatedData['captain_id'],
                'is_captain' => $isTeamCaptain,
                'target_player_id' => $id_player,
                'is_self_removal' => $isSelfRemoval
            ]);

            if (!$isTeamCaptain && !$isSelfRemoval) {
                return response()->json([
                    'message' => 'Unauthorized to remove team members',
                    'debug' => [
                        'is_captain' => $isTeamCaptain,
                        'is_self_removal' => $isSelfRemoval,
                        'your_ids' => $authPlayerIds,
                        'captain_id' => $team->capitain,
                        'provided_captain_id' => $validatedData['captain_id']
                    ]
                ], 403);
            }

            // Cannot remove the team captain
            if ($team->capitain == $id_player) {
                return response()->json([
                    'message' => 'Cannot remove the team captain. Transfer captaincy first.'
                ], 400);
            }

            try {
                $playerTeam->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Team member removed successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to remove team member',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }
    }
} 