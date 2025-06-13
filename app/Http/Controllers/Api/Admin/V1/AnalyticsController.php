<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Compte;
use App\Models\Tournoi;
use App\Models\Terrain;
use App\Models\ReportedBug;
use App\Models\Rating;
use App\Models\PlayerRequest;
use App\Models\Matches;
use App\Models\Players;
use App\Models\Stages;
use App\Models\Teams;
use App\Models\AcademieActivites;
use App\Models\AcademieCoach;
use App\Models\AcademieProgramme;
use App\Models\ActivitesMembers;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AnalyticsController extends Controller
{
    public function getAnalytics(): JsonResponse
    {
        $totalReservations = Reservation::count();
        $totalComptes = Compte::count();
        $totalTournois = Tournoi::count();
        $totalTerrains = Terrain::count();
        $totalReportedBugs = ReportedBug::count();
        $totalRatings = Rating::count();
        $totalPlayerRequests = PlayerRequest::count();
        $totalMatches = Matches::count();
        $totalPlayers = Players::count();
        $totalStages = Stages::count();
        $totalTeams = Teams::count();
        $totalAcademieActivites = AcademieActivites::count();
        $totalAcademieCoaches = AcademieCoach::count();
        $totalAcademieProgrammes = AcademieProgramme::count();
        $totalActivitesMembers = ActivitesMembers::count();
        
        // Calculate total revenue
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $todayRevenue = Payment::where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum('amount');

        return response()->json([
            'total_reservations' => $totalReservations,
            'total_comptes' => $totalComptes,
            'total_tournois' => $totalTournois,
            'total_terrains' => $totalTerrains,
            'total_reported_bugs' => $totalReportedBugs,
            'total_ratings' => $totalRatings,
            'total_player_requests' => $totalPlayerRequests,
            'total_matches' => $totalMatches,
            'total_players' => $totalPlayers,
            'total_stages' => $totalStages,
            'total_teams' => $totalTeams,
            'total_academie_activites' => $totalAcademieActivites,
            'total_academie_coaches' => $totalAcademieCoaches,
            'total_academie_programmes' => $totalAcademieProgrammes,
            'total_activites_members' => $totalActivitesMembers,
            'total_revenue' => $totalRevenue,
            'today_revenue' => $todayRevenue,
        ]);
    }
    
    /**
     * Get analytics within a date range
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAnalyticsByDateRange(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            
            $totalReservations = Reservation::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalComptes = Compte::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalTournois = Tournoi::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalTerrains = Terrain::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalReportedBugs = ReportedBug::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalRatings = Rating::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalPlayerRequests = PlayerRequest::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalMatches = Matches::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalPlayers = Players::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalStages = Stages::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalTeams = Teams::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalAcademieActivites = AcademieActivites::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalAcademieCoaches = AcademieCoach::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalAcademieProgrammes = AcademieProgramme::whereBetween('created_at', [$startDate, $endDate])->count();
            $totalActivitesMembers = ActivitesMembers::whereBetween('created_at', [$startDate, $endDate])->count();
            
            // Calculate revenue for the date range
            $totalRevenue = Payment::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');
                
            // Daily revenue breakdown
            $dailyRevenue = Payment::where('status', 'completed')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(amount) as daily_revenue')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return response()->json([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_reservations' => $totalReservations,
                'total_comptes' => $totalComptes,
                'total_tournois' => $totalTournois,
                'total_terrains' => $totalTerrains,
                'total_reported_bugs' => $totalReportedBugs,
                'total_ratings' => $totalRatings,
                'total_player_requests' => $totalPlayerRequests,
                'total_matches' => $totalMatches,
                'total_players' => $totalPlayers,
                'total_stages' => $totalStages,
                'total_teams' => $totalTeams,
                'total_academie_activites' => $totalAcademieActivites,
                'total_academie_coaches' => $totalAcademieCoaches,
                'total_academie_programmes' => $totalAcademieProgrammes,
                'total_activites_members' => $totalActivitesMembers,
                'total_revenue' => $totalRevenue,
                'daily_revenue' => $dailyRevenue,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch analytics by date range',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get notification analytics
     * 
     * @return JsonResponse
     */
    public function getNotificationAnalytics(): JsonResponse
    {
        try {
            // Recent activity data that can serve as notifications
            $recentReservations = Reservation::select(
                    'id_reservation', 'id_client', 'id_terrain', 'date', 'heure',
                    DB::raw('DATE(created_at) as created_date')
                )
                ->with(['client:id_compte,nom,prenom', 'terrain:id_terrain,nom_terrain'])
                ->where('created_at', '>=', now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($reservation) {
                    return [
                        'id' => $reservation->id_reservation,
                        'type' => 'reservation',
                        'message' => "New reservation by {$reservation->client->nom} {$reservation->client->prenom}",
                        'terrain' => $reservation->terrain->nom_terrain,
                        'date' => $reservation->created_date,
                        'reservation_date' => $reservation->date,
                        'created_at' => $reservation->created_at
                    ];
                });
                
            $recentPlayerRequests = PlayerRequest::select(
                    'id_request', 'sender', 'receiver', 'status', 'request_type',
                    DB::raw('DATE(created_at) as created_date')
                )
                ->with([
                    'sender' => function($query) {
                        $query->with('compte:id_compte,nom,prenom');
                    },
                    'receiver' => function($query) {
                        $query->with('compte:id_compte,nom,prenom');
                    }
                ])
                ->where('created_at', '>=', now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id_request,
                        'type' => 'player_request',
                        'request_type' => $request->request_type,
                        'message' => "New {$request->request_type} request",
                        'status' => $request->status,
                        'date' => $request->created_date,
                        'created_at' => $request->created_at
                    ];
                });
                
            $recentMatches = Matches::select(
                    'id_match', 'id_tournoi', 'team1_id', 'team2_id', 'score_team1', 'score_team2',
                    DB::raw('DATE(created_at) as created_date')
                )
                ->with(['tournoi:id_tournoi,name'])
                ->where('created_at', '>=', now()->subDays(7))
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($match) {
                    $message = "New match scheduled";
                    if ($match->score_team1 !== null && $match->score_team2 !== null) {
                        $message = "Match completed - Score: {$match->score_team1}-{$match->score_team2}";
                    }
                    return [
                        'id' => $match->id_match,
                        'type' => 'match',
                        'message' => $message,
                        'tournament' => $match->tournoi->name ?? 'Unknown Tournament',
                        'date' => $match->created_date,
                        'created_at' => $match->created_at
                    ];
                });
                
            // Combine all recent activities as notifications
            $allNotifications = $recentReservations
                ->concat($recentPlayerRequests)
                ->concat($recentMatches)
                ->sortByDesc('created_at')
                ->values()
                ->all();
                
            // Get activity counts by type for the last 7 days
            $activityCounts = [
                'reservations' => Reservation::where('created_at', '>=', now()->subDays(7))->count(),
                'player_requests' => PlayerRequest::where('created_at', '>=', now()->subDays(7))->count(),
                'matches' => Matches::where('created_at', '>=', now()->subDays(7))->count(),
                'new_players' => Players::where('created_at', '>=', now()->subDays(7))->count(),
                'new_teams' => Teams::where('created_at', '>=', now()->subDays(7))->count(),
            ];
                
            return response()->json([
                'notifications' => $allNotifications,
                'activity_counts' => $activityCounts,
                'unread_count' => count($allNotifications), // Simulating unread notifications
                'total_activities' => array_sum($activityCounts),
                'activity_distribution' => [
                    ['type' => 'Reservations', 'count' => $activityCounts['reservations']],
                    ['type' => 'Player Requests', 'count' => $activityCounts['player_requests']],
                    ['type' => 'Matches', 'count' => $activityCounts['matches']],
                    ['type' => 'New Players', 'count' => $activityCounts['new_players']],
                    ['type' => 'New Teams', 'count' => $activityCounts['new_teams']],
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch activity analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     * 
     * @return JsonResponse
     */
    public function markAllNotificationsAsRead(): JsonResponse
    {
        try {
            // Mark all database notifications as read
            DatabaseNotification::whereNull('read_at')->update(['read_at' => now()]);

            return response()->json([
                'message' => 'All notifications marked as read successfully',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark notifications as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}