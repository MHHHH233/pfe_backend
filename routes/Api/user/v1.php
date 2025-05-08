<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\User\V1\ReservationController;
use App\Http\Controllers\Api\User\V1\CompteController;
use App\Http\Controllers\Api\User\V1\TournoiController;
use App\Http\Controllers\Api\User\V1\TerrainController;
use App\Http\Controllers\Api\User\V1\ReportedBugController;
use App\Http\Controllers\Api\User\V1\RatingController;
use App\Http\Controllers\Api\User\V1\PlayerRequestController;
use App\Http\Controllers\Api\User\V1\MatchesController;
use App\Http\Controllers\Api\User\V1\PlayersController;
use App\Http\Controllers\Api\User\V1\StagesController;
use App\Http\Controllers\Api\User\V1\TeamsController;
use App\Http\Controllers\Api\User\V1\TournamentController;
use App\Http\Controllers\Api\User\V1\TournoiTeamsController;
use App\Http\Controllers\Api\User\V1\AcademieActivitesController;
use App\Http\Controllers\Api\User\V1\AcademieCoachController;
use App\Http\Controllers\Api\User\V1\AcademieProgrammeController;
use App\Http\Controllers\Api\User\V1\ActivitesMembersController;
use App\Http\Controllers\Api\User\V1\AnalyticsController;
use App\Http\Controllers\Api\User\V1\AcademieController;
use App\Http\Controllers\Api\User\V1\AcademieMembersController;
use App\Http\Controllers\Api\User\V1\ReviewsController;

Route::prefix('user')->as('user.')->group(function () {
    Route::prefix('v1')->as('v1.')->group(function () {

        Route::get('reservations/upcoming', [ReservationController::class, 'upcoming']);
        Route::get('reservations/history', [ReservationController::class, 'history']);
        Route::get('reservations/week', [ReservationController::class, 'week']);
        Route::post('reservations/{id}/cancel', [ReservationController::class, 'cancel']);
        Route::apiResource('reservations', ReservationController::class);    
        
        Route::apiResource('comptes', CompteController::class);
        Route::get('profile', [CompteController::class, 'profile']); // GET /comptes/profile
        Route::put('updateProfile', [CompteController::class, 'updateProfile']); // PUT /comptes/updateProfile
        Route::post('changePassword', [CompteController::class, 'changePassword']); // POST /comptes/changePassword
        Route::post('reportBug', [CompteController::class, 'reportBug']); // POST /comptes/reportBug
        Route::get('activityHistory', [CompteController::class, 'activityHistory']); // GET /comptes/activityHistory
        Route::patch('comptes/{id}/reset-password', [CompteController::class, 'resetPassword']);        
        Route::get('notifications', [CompteController::class, 'notifications']); // GET /notifications
        Route::post('notifications/mark-read', [CompteController::class, 'markNotificationAsRead']); // POST /notifications/mark-read
        

        Route::apiResource('tournois', TournoiController::class);
        Route::apiResource('terrains', TerrainController::class);
        Route::apiResource('reported-bugs', ReportedBugController::class);
        Route::apiResource('ratings', RatingController::class);
        Route::apiResource('player-requests', PlayerRequestController::class);
        Route::apiResource('matches', MatchesController::class);
        Route::apiResource('players', PlayersController::class);
        Route::apiResource('stages', StagesController::class);
        
        
        Route::apiResource('teams', TeamsController::class);
        Route::post('teams/join', [TournoiTeamsController::class],'join');        
        Route::post('teams/leave', [TournoiTeamsController::class],'leave');        

        
        // Add routes for each controller
        Route::apiResource('academie-activites', AcademieActivitesController::class);
        Route::apiResource('academie', AcademieController::class);
        Route::apiResource('academie-coaches', AcademieCoachController::class);
        Route::apiResource('academie-programmes', AcademieProgrammeController::class);
        Route::apiResource('activites-members', ActivitesMembersController::class);

        // Academie members routes
        Route::post('academie-subscribe', [AcademieMembersController::class, 'subscribe']);
        Route::delete('academie-subscribe/{academieId}', [AcademieMembersController::class, 'cancelSubscription']);
        Route::get('my-academie-memberships', [AcademieMembersController::class, 'myMemberships']);
        Route::patch('academie-subscribe/{academieId}/plan', [AcademieMembersController::class, 'updatePlan']);

        Route::apiResource('tournoi-teams', TournoiTeamsController::class);        
        Route::get('tournoiStats', [TournoiTeamsController::class],'getStats');        
        Route::post('tournoi-teams/withdraw', [TournoiTeamsController::class],'withdraw');        
        Route::post('tournoi-teams/register', [TournoiTeamsController::class],'register');        
        
        Route::apiResource('reviews', ReviewsController::class)->only(['index', 'store', 'destroy']);
                
    });
});