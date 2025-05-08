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
use App\Http\Controllers\Api\Admin\V1\AcademieMembersController;
use App\Http\Controllers\Api\Admin\V1\ReviewsController;
use App\Http\Resources\Admin\V1\PlayerRequestResource;

Route::prefix('admin')->as('admin.')->group(function () {
    Route::prefix('v1')->as('v1.')->group(function () {
        Route::apiResource('reservations', ReservationController::class);
        Route::patch('reservations/{id}/status', [ReservationController::class, 'updateStatus']);
        
        Route::apiResource('comptes', CompteController::class);
        Route::patch('comptes/{id}/reset-password', [CompteController::class, 'resetPassword']);
        Route::patch('comptes/{id}/update-role', [CompteController::class, 'assignRoles']);
        

        Route::apiResource('tournois', TournoiController::class);
        Route::apiResource('terrains', TerrainController::class);
        Route::apiResource('reported-bugs', ReportedBugController::class);
        Route::patch('reported-bugs/{id}/status', [ReportedBugController::class, 'updateStatus']);
        Route::apiResource('reviews', ReviewsController::class);
        Route::patch('reviews/{id}/status', [ReviewsController::class, 'updateStatus']);
        Route::apiResource('ratings', RatingController::class);
        Route::apiResource('player-requests', PlayerRequestController::class);
        Route::patch('player-requests/{id}/status', [PlayerRequestController::class, 'updateStatus']);
        Route::patch('player-requests/{id}/reject', [PlayerRequestController::class, 'reject']);
        Route::patch('player-requests/{id}/accept', [PlayerRequestController::class, 'accept']);
        Route::apiResource('matches', MatchesController::class);
        Route::apiResource('players', PlayersController::class);
        Route::apiResource('stages', StagesController::class);
        Route::apiResource('teams', TeamsController::class);
        
        // Add routes for each controller
        
        Route::apiResource('academie', AcademieController::class);
        Route::apiResource('academie-activites', AcademieActivitesController::class);
        Route::apiResource('academie-coaches', AcademieCoachController::class);
        Route::apiResource('academie-programmes', AcademieProgrammeController::class);
        Route::apiResource('academie-members', AcademieMembersController::class);
        Route::get('academie/{academieId}/members', [AcademieMembersController::class, 'getByAcademie']);
        Route::apiResource('activites-members', ActivitesMembersController::class);
        Route::apiResource('tournoi-teams', TournoiTeamsController::class);        
        
        
        Route::get('analytics', [AnalyticsController::class,'getAnalytics']);
    });
});