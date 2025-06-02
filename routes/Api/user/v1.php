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
use App\Http\Controllers\Api\User\V1\PlayerTeamController;
use App\Http\Controllers\Api\User\V1\SocialMediaController;
use App\Http\Controllers\Api\User\V1\ContactController;
use App\Http\Controllers\Api\User\V1\ChatbotController;
use App\Http\Controllers\Api\User\V1\PaymentController;

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
        Route::post('comptes/{id}/deleteAccount', [CompteController::class, 'deleteAccount']); // DELETE /comptes/deleteAccount
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
        
        // Social Media routes
        Route::get('social-media', [SocialMediaController::class, 'index']);
        
        // Contact routes
        Route::post('contact', [ContactController::class, 'store']);
        
        // Player requests routes
        Route::get('player-requests/player', [PlayerRequestController::class, 'getPlayerRequests']);
        Route::post('player-requests/{id}/accept', [PlayerRequestController::class, 'accept']);
        Route::post('player-requests/{id}/cancel', [PlayerRequestController::class, 'cancel']);
        Route::apiResource('player-requests', PlayerRequestController::class);
        
        Route::apiResource('matches', MatchesController::class);
        
        // Players routes
        Route::apiResource('players', PlayersController::class);
        Route::post('players/{id}/teams', [PlayersController::class, 'addToTeam']);
        Route::delete('players/{id}/teams', [PlayersController::class, 'removeFromTeam']);
        Route::get('players/{id}/teams', [PlayersController::class, 'getTeams']);
        
        // Player-Team routes
        Route::apiResource('player-teams', PlayerTeamController::class);
        Route::post('player-teams/{id}/invite', [PlayerTeamController::class, 'invite']);
        Route::post('player-teams/{id}/accept', [PlayerTeamController::class, 'acceptInvitation']);
        Route::post('player-teams/{id}/refuse', [PlayerTeamController::class, 'refuseInvitation']);
        Route::post('player-teams/{id}/process', [PlayerTeamController::class, 'processJoinRequest']);
        Route::get('pending-invitations', [PlayerTeamController::class, 'getPendingInvitations']);
        Route::get('pending-join-requests', [PlayerTeamController::class, 'getPendingJoinRequests']);
        Route::get('player-teams/{id}/members/{id_player}', [PlayerTeamController::class, 'getMember']);
        Route::delete('player-teams/{id}/members/{id_player}', [PlayerTeamController::class, 'removeMember']);
        
        Route::apiResource('stages', StagesController::class);
        
        
        // Add routes for each controller
        Route::apiResource('academie-activites', AcademieActivitesController::class);
        Route::apiResource('academie', AcademieController::class);
        Route::apiResource('academie-coaches', AcademieCoachController::class);
        Route::apiResource('academie-programmes', AcademieProgrammeController::class);
        Route::apiResource('activites-members', ActivitesMembersController::class);
        Route::get('activites-members/member-activites/{id_member}', [ActivitesMembersController::class, 'getActivitesIn']);

        // Academie members routes
        Route::post('academie-subscribe', [AcademieMembersController::class, 'subscribe']);
        Route::delete('academie-subscribe/{academieId}', [AcademieMembersController::class, 'cancelSubscription']);
        Route::get('my-academie-memberships', [AcademieMembersController::class, 'myMemberships']);
        Route::patch('academie-subscribe/{academieId}/plan', [AcademieMembersController::class, 'updatePlan']);

        // Teams management routes
        Route::apiResource('teams', TeamsController::class);
        Route::post('teams/my-team', [TeamsController::class, 'myTeam']);
        Route::post('teams/{id}/members', [TeamsController::class, 'addMember']);
        Route::delete('teams/{id}/members', [TeamsController::class, 'removeMember']);
        Route::get('teams/{id}/members', [TeamsController::class, 'getMembers']);
        Route::post('teams/{id}/transfer-captaincy', [TeamsController::class, 'transferCaptaincy']);
        Route::post('teams/join', [TournoiTeamsController::class, 'join']);        
        Route::post('teams/leave', [TournoiTeamsController::class, 'leave']);        

        Route::apiResource('tournoi-teams', TournoiTeamsController::class);        
        Route::get('tournoiStats/{id_tournoi}/{id_teams}', [TournoiTeamsController::class, 'getStats']);        
        Route::post('tournoi-teams/withdraw/{id_tournoi}/{id_teams}', [TournoiTeamsController::class, 'withdraw']);        
        Route::post('tournoi-teams/register', [TournoiTeamsController::class, 'register']);
        
        Route::apiResource('reviews', ReviewsController::class)->only(['index', 'store', 'destroy']);
        
        // Chatbot routes with rate limiting
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('chatbot/send', [ChatbotController::class, 'sendMessage']);
            Route::post('chatbot/clear', [ChatbotController::class, 'clearConversation']);
            Route::get('chatbot/models', [ChatbotController::class, 'getAvailableModels']);
        });
                
        // Add routes for payments
        Route::middleware('auth:sanctum')->prefix('payments')->group(function () {
            Route::post('/', [PaymentController::class, 'store']);
            Route::get('/{id}', [PaymentController::class, 'show']);
        });
    });
});