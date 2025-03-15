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
use Illuminate\Http\JsonResponse;

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
        ]);
    }
}