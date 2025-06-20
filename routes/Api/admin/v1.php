<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\V1\ReservationController;
use App\Http\Controllers\Api\Admin\V1\CompteController;
use App\Http\Controllers\Api\Admin\V1\TournoiController;
use App\Http\Controllers\Api\Admin\V1\TerrainController;
use App\Http\Controllers\Api\Admin\V1\ReportedBugController;
use App\Http\Controllers\Api\Admin\V1\RatingController;
use App\Http\Controllers\Api\Admin\V1\PlayerRequestController;
use App\Http\Controllers\Api\Admin\V1\MatchesController;
use App\Http\Controllers\Api\Admin\V1\PlayersController;
use App\Http\Controllers\Api\Admin\V1\StagesController;
use App\Http\Controllers\Api\Admin\V1\TeamsController;
use App\Http\Controllers\Api\Admin\V1\TournamentController;
use App\Http\Controllers\Api\Admin\V1\TournoiTeamsController;
use App\Http\Controllers\Api\Admin\V1\AcademieActivitesController;
use App\Http\Controllers\Api\Admin\V1\AcademieCoachController;
use App\Http\Controllers\Api\Admin\V1\AcademieProgrammeController;
use App\Http\Controllers\Api\Admin\V1\ActivitesMembersController;
use App\Http\Controllers\Api\Admin\V1\AnalyticsController;
use App\Http\Controllers\Api\Admin\V1\AcademieController;

Route::prefix('admin')->as('admin.')->group(function () {
    Route::prefix('v1')->as('v1.')->group(function () {
        Route::apiResource('reservations', ReservationController::class);
        Route::patch('reservations/{id}/status', [ReservationController::class, 'updateStatus']);
        
        Route::apiResource('comptes', CompteController::class);
        Route::patch('comptes/{id}/reset-password', [CompteController::class, 'resetPassword']);
        Route::patch('comptes/{id}/update-role', [CompteController::class, 'updateRole']);
        

        Route::apiResource('tournois', TournoiController::class);
        Route::apiResource('terrains', TerrainController::class);
        Route::apiResource('reported-bugs', ReportedBugController::class);
        Route::apiResource('ratings', RatingController::class);
        Route::apiResource('player-requests', PlayerRequestController::class);
        Route::apiResource('matches', MatchesController::class);
        Route::apiResource('players', PlayersController::class);
        Route::apiResource('stages', StagesController::class);
        Route::apiResource('teams', TeamsController::class);
        
        // Add routes for each controller
        Route::apiResource('academie-activites', AcademieActivitesController::class);
        Route::apiResource('academie', AcademieController::class);
        Route::apiResource('academie-coaches', AcademieCoachController::class);
        Route::apiResource('academie-programmes', AcademieProgrammeController::class);
        Route::apiResource('activites-members', ActivitesMembersController::class);
        Route::apiResource('tournoi-teams', TournoiTeamsController::class);        
        
        
        Route::get('analytics', [AnalyticsController::class,'getAnalytics']);
    });
});