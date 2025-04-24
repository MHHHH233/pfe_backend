<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\User\V1\CompteResource;

class CompteController extends Controller
{
    /**
     * Get authenticated user's profile.
     *
     * @param Request $request
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            // Load player profile, reservations, and player requests
            $user->load([
                'player',
                'reservations',
                'player.sentRequests',
                'player.receivedRequests'
            ]);

            return new CompteResource($user);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user's profile.
     *
     * @param Request $request
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'nom' => 'required|string|max:50',
                'prenom' => 'required|string|max:50',
                'email' => 'required|email|unique:compte,email,' . $request->user()->id_compte . ',id_compte',
                'telephone' => 'required|string|max:20',
                'date_naissance' => 'required|date',
                'age' => 'required|integer',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $user = $request->user();
            $user->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => new CompteResource($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change user's password.
     *
     * @param Request $request
     */
    public function changePassword(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $user = $request->user();

            if (!Hash::check($validatedData['current_password'], $user->password)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            $user->update([
                'password' => Hash::make($validatedData['new_password'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to change password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user's notification preferences.
     *
     * @param Request $request
     */
    public function updateNotificationPreferences(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'email_notifications' => 'required|boolean',
                'push_notifications' => 'required|boolean'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $user = $request->user();
            $user->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully',
                'data' => new CompteResource($user)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update notification preferences',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's activity history.
     *
     * @param Request $request
     */
    public function activityHistory(Request $request)
    {
        try {
            $user = $request->user();

            $activities = $user->load([
                'reservations' => function($query) {
                    $query->orderBy('date', 'desc')
                          ->orderBy('heure', 'desc');
                },
                'player.ratings',
                'player.teams'
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'reservations' => $activities->reservations,
                    'ratings' => $activities->player->ratings,
                    'teams' => $activities->player->teams
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch activity history',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Report a bug.
     *
     * @param Request $request
     */
    public function reportBug(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'description' => 'required|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $bug = $request->user()->reportedBugs()->create([
                'description' => $validatedData['description']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bug reported successfully',
                'data' => $bug
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to report bug',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}