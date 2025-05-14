<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compte;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function redirectToGoogle()
    {
        try {
            // Generate the redirect URL using Socialite
            $redirectUrl = Socialite::driver('google')
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'status' => true,
                'url' => $redirectUrl,
            ]);
        } catch (Exception $e) {
            Log::error('Error redirecting to Google', ['message' => $e->getMessage()]);
            return response()->json([
                'status' => false,
                'message' => 'Failed to initiate Google authentication.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Google callback with code parameter.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            // Log callback data
            Log::info('Google callback received', $request->all());

            // Ensure the code parameter exists
            if (!$request->has('code')) {
                return response()->json([
                    'status' => false,
                    'code' => 400,
                    'message' => 'Authorization code is missing',
                ], 400);
            }

            // Get Google OAuth configuration
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');
            $redirectUri = config('services.google.redirect');

            // Exchange authorization code for access token
            $tokenResponse = Http::post('https://oauth2.googleapis.com/token', [
                'code' => $request->code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                Log::error('Failed to get token from Google', [
                    'response' => $tokenResponse->json(),
                    'status' => $tokenResponse->status()
                ]);
                
                return response()->json([
                    'status' => false,
                    'code' => 500,
                    'message' => 'Failed to exchange authorization code for token',
                    'error' => $tokenResponse->body()
                ], 500);
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];
            
            // Get user info with the access token
            $userResponse = Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');
            
            if (!$userResponse->successful()) {
                Log::error('Failed to get user info from Google', [
                    'response' => $userResponse->json(),
                    'status' => $userResponse->status()
                ]);
                
                return response()->json([
                    'status' => false,
                    'code' => 500,
                    'message' => 'Failed to retrieve user information from Google',
                    'error' => $userResponse->body()
                ], 500);
            }
            
            $googleUser = $userResponse->json();
            
            Log::info('Google user data', [
                'id' => $googleUser['id'],
                'email' => $googleUser['email'],
                'name' => $googleUser['name'],
            ]);

            // First check if the email already exists
            $existingUser = Compte::where('email', $googleUser['email'])->first();
            
            if ($existingUser) {
                // User exists, update Google ID if needed
                if (!$existingUser->google_id) {
                    $existingUser->google_id = $googleUser['id'];
                    $existingUser->google_avatar = $googleUser['picture'] ?? null;
                    $existingUser->save();
                }
                
                $user = $existingUser;
                
                // Log detailed existing user info for debugging
                Log::info('Existing user details:', [
                    'id' => $user->id_compte,
                    'email' => $user->email,
                    'role_column' => $user->role,
                    'roles_in_permission' => $user->getRoleNames()->toArray(),
                    'has_admin_role' => $user->hasRole('admin')
                ]);
            } else {
                // Create new user
                $user = Compte::create([
                    'nom' => $googleUser['family_name'] ?? $googleUser['name'],
                    'prenom' => $googleUser['given_name'] ?? '',
                    'email' => $googleUser['email'],
                    'google_id' => $googleUser['id'],
                    'google_avatar' => $googleUser['picture'] ?? null,
                    'pfp' => $googleUser['picture'] ?? null,
                    'password' => Hash::make(rand(1000000, 9999999)),
                    'age' => '',
                    'telephone' => '',
                    'role' => 'user', // Default role for new users
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                // Assign default role for new users
                $user->assignRole('user');
                Log::info('New user created with role: user');
            }

            // Ensure role is properly synced with permission system
            if ($user->role === 'admin') {
                // Force sync the admin role regardless of current permissions
                $user->syncRoles(['admin']);
                Log::info('Synced admin role from database to permission system');
            } else if ($user->role === 'user') {
                $user->syncRoles(['user']);
                Log::info('Synced user role from database to permission system');
            }

            // Generate token
            $token = $user->createToken('google-auth')->plainTextToken;

            // Force refresh user from database to ensure role changes are loaded
            $user = Compte::find($user->id_compte);
            
            // Get roles after syncing but don't use in response (for debugging only)
            $roleNames = $user->getRoleNames();
            Log::info('User roles after sync: ', ['roles' => $roleNames->toArray(), 'db_role' => $user->role]);

            // Add empty roles array to match login response format
            $user->roles = [];
            
            // Load all relationships to ensure we have a complete user object
            $user->load(['player', 'reservations', 'reviews']);

            // Fetch additional data (academie memberships and teams)
            $academieMemberships = \App\Models\AcademieMembers::where('id_compte', $user->id_compte)
                ->select('id_member', 'id_academie', 'subscription_plan', 'status')
                ->get();
            
            $hasAcademieMembership = $academieMemberships->count() > 0;
            
            // Get user's teams where they are captain
            $teams = \App\Models\Teams::where('capitain', $user->id_compte)->get();
            $hasTeams = $teams->count() > 0;

            // Prepare response data to exactly match login format
            $responseData = [
                'status' => true,
                'code' => 200,
                'message' => 'Login successful!',
                'data' => [
                    'user_token' => $token,
                    'user' => $user,
                    'roles' => [],
                    'token_type' => 'Bearer',
                    'has_academie_membership' => $hasAcademieMembership,
                    'academie_memberships' => $academieMemberships,
                    'has_teams' => $hasTeams,
                    'teams' => $teams
                ],
            ];
            
            // Add debug info if requested
            if ($request->has('debug')) {
                $responseData['debug'] = [
                    'db_role' => $user->role,
                    'permission_roles' => $roleNames->toArray(),
                    'has_admin_role_direct' => $user->hasRole('admin'),
                    'database_id' => $user->id_compte
                ];
            }

            // Check if the request wants JSON response (API client) or redirect (browser)
            if ($request->wantsJson() || $request->has('api')) {
                return response()->json($responseData);
            } else {
                // For browser clients, redirect to frontend
                $redirectUrl = 'http://localhost:3000/auth/google/callback';
                $redirectUrl .= '?token=' . urlencode($token);
                $redirectUrl .= '&user_id=' . urlencode($user->id_compte);
                $redirectUrl .= '&email=' . urlencode($user->email);
                $redirectUrl .= '&name=' . urlencode($user->prenom . ' ' . $user->nom);
                $redirectUrl .= '&first_name=' . urlencode($user->prenom);
                $redirectUrl .= '&last_name=' . urlencode($user->nom);
                $redirectUrl .= '&avatar=' . urlencode($user->pfp);
                $redirectUrl .= '&role=' . urlencode($user->role);
                $redirectUrl .= '&status=success';
                
                return redirect()->away($redirectUrl);
            }
        } catch (Exception $e) {
            // Log error details
            Log::error('Google authentication error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => false,
                'code' => 500,
                'message' => 'Error authenticating with Google',
                'error' => $e->getMessage(),
                'debug' => [
                    'trace' => $e->getTraceAsString(),
                    'request_data' => $request->all(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }
}
