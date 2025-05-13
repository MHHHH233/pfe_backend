<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\User\V1\CompteResource;
use Illuminate\Support\Facades\DB;

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
            
            return new CompteResource($user->load(['player.sentRequests', 'player.receivedRequests', 'reservations', 'reviews']));
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
                // 'date_naissance' => 'required|date',
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
                'player.teams',
                'reviews' => function($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'reservations' => $activities->reservations,
                    'ratings' => $activities->player ? $activities->player->ratings : [],
                    'teams' => $activities->player ? $activities->player->teams : [],
                    'reviews' => $activities->reviews
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

    /**
     * Get user's notifications.
     *
     * @param Request $request
     */
    public function notifications(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $notifications = $user->notifications()
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return response()->json([
                'success' => true,
                'data' => $notifications
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch notifications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read.
     *
     * @param Request $request
     */
    public function markNotificationAsRead(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'notification_id' => 'required|exists:notifications,id'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $user = $request->user();
            $notification = $user->notifications()->findOrFail($validatedData['notification_id']);
            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to mark notification as read',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user account.
     *
     * @param Request $request
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $user = $request->user();

            // Verify password before deletion
            if (!Hash::check($validatedData['password'], $user->password)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Password is incorrect'
                ], 400);
            }

            // Begin transaction to ensure all related data is deleted properly
            DB::beginTransaction();
            
            // Load the player relationship
            $player = $user->player;
            
            if ($player) {
                // Delete player's sent friend requests
                if ($player->sentRequests()->exists()) {
                    $player->sentRequests()->delete();
                }
                
                // Delete player's received friend requests
                if ($player->receivedRequests()->exists()) {
                    $player->receivedRequests()->delete();
                }
                
                // Remove player from teams
                if ($player->teams()->exists()) {
                    $player->teams()->detach();
                }
                
                // Remove player from academies
                if ($player->academies()->exists()) {
                    $player->academies()->detach();
                }
                
                // Delete player ratings/reviews
                if ($player->ratings()->exists()) {
                    $player->ratings()->delete();
                }
                
                // Delete the player record
                $player->delete();
            }
            
            // Delete user's reservations
            if ($user->reservations()->exists()) {
                $user->reservations()->delete();
            }
            
            // Delete user's reviews
            if ($user->reviews()->exists()) {
                $user->reviews()->delete();
            }
            
            // Delete user's notifications
            if ($user->notifications()->exists()) {
                $user->notifications()->delete();
            }
            
            // Delete user's reported bugs
            if ($user->reportedBugs()->exists()) {
                $user->reportedBugs()->delete();
            }
            
            // Revoke all tokens
            $user->tokens()->delete();
            
            // Finally delete the user account
            $user->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Account and all related data deleted successfully'
            ]);

        } catch (\Exception $e) {
            // Rollback if anything fails
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to delete account',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 