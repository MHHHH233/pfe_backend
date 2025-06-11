<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Models\TournoiTeams;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\TournoiTeamsResource;

class TournoiTeamsController extends Controller
{
    /**
     * Display teams in a tournament.
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
                        $validIncludes = ['tournoi', 'team', 'matches'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'id_tournoi' => 'required|integer|exists:tournoi,id_tournoi',
                'sort_by' => 'nullable|string|in:points,goals_scored,goals_conceded',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = TournoiTeams::where('id_tournoi', $request->input('id_tournoi'));

        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $teams = $query->get();

        return TournoiTeamsResource::collection($teams);
    }

    /**
     * Register team for tournament.
     *
     * @param Request $request
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_tournoi' => 'required|integer|exists:tournoi,id_tournoi',
                'id_teams' => 'required|integer|exists:teams,id_teams',
                'team_name' => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            // Get the team to get the captain ID
            $team = \App\Models\Teams::find($validatedData['id_teams']);
            if (!$team) {
                return response()->json([
                    'error' => true,
                    'message' => 'Team not found'
                ], 404);
            }

            // Check if a team with this name is already registered in this tournament
            $existsByName = TournoiTeams::where('id_tournoi', $validatedData['id_tournoi'])
                ->where('team_name', $validatedData['team_name'])
                ->exists();

            if ($existsByName) {
                return response()->json([
                    'error' => true,
                    'message' => 'A team with this name is already registered for this tournament'
                ], 409);
            }

            // Create the tournament team
            $tournoiTeam = new TournoiTeams();
            $tournoiTeam->id_tournoi = $validatedData['id_tournoi'];
            $tournoiTeam->team_name = $validatedData['team_name'];
            $tournoiTeam->capitain = $team->capitain;
            // You can include a description if necessary
            // $tournoiTeam->descrption = $request->description ?? null;
            $tournoiTeam->save();

            return response()->json([
                'success' => true,
                'message' => 'Team registered successfully',
                'data' => new TournoiTeamsResource($tournoiTeam)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to register team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Withdraw team from tournament.
     *
     * @param int $id_tournoi
     * @param int $id_teams
     */
    public function withdraw($id_tournoi, $id_teams): JsonResponse
    {
        try {
            $tournoiTeam = TournoiTeams::where('id_tournoi', $id_tournoi)
                ->where('id_teams', $id_teams)
                ->first();

            if (!$tournoiTeam) {
                return response()->json([
                    'message' => 'Team is not registered in this tournament'
                ], 404);
            }

            $tournoiTeam->delete();

            return response()->json([
                'success' => true,
                'message' => 'Team withdrawn successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to withdraw team',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get team's tournament statistics.
     *
     * @param int $id_tournoi
     * @param int $id_teams
     */
    public function getStats($id_tournoi, $id_teams): JsonResponse
    {
        try {
            $stats = TournoiTeams::where('id_tournoi', $id_tournoi)
                ->where('id_teams', $id_teams)
                ->first();

            if (!$stats) {
                return response()->json([
                    'message' => 'Team stats not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'points' => $stats->points,
                    'goals_scored' => $stats->goals_scored,
                    'goals_conceded' => $stats->goals_conceded,
                    'goal_difference' => $stats->goals_scored - $stats->goals_conceded,
                    'matches_played' => $stats->matches()->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch team stats',
                'error' => $e->getMessage()
            ], 500);
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
        $sortBy = $request->input('sort_by', 'points');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['points', 'goals_scored', 'goals_conceded'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('points', 'desc')
                  ->orderBy('goals_scored', 'desc');
        }
    }
} 