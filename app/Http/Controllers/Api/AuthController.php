<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthResource;
use App\Http\Resources\user\V1\CompteResource;
use App\Models\Compte;
use App\Models\AcademieMembers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\File;

class AuthController extends Controller
{
    public function getRandomProfilePicture()
    {
        $path = public_path('images/default_pfp');
        $files = File::files($path);
        
        if (count($files) === 0) {
            return 'images/default_pfp/default.png';
        }
        $baseUrl = 'http://127.0.0.1:8000/';
        $randomFile = $files[array_rand($files)];
        return $baseUrl . 'images/default_pfp/' . $randomFile->getFilename();
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'age' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:compte',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'code' => 422,
                'message' => 'validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = Compte::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'age' => $request->age,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telephone' => $request->telephone,
            'pfp' => $this->getRandomProfilePicture(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Change this line - assign 'user' role instead of 'sanctum'
        $user->assignRole('user');

        return response()->json([
            'status' => true,
            'code' => 201,
            'message' => 'User registered successfully',
            'data' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = Compte::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(
                [
                    'status' => false,
                    'code' => 401,
                    'message' => 'The provided credentials are incorrect.',
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.'],
                    ],
                ],
                401,
            );
            
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        $roles = $user->getRoleNames();
        
        // Get user's academie memberships with details
        $academieMemberships = AcademieMembers::where('id_compte', $user->id_compte)
            ->select('id_member', 'id_academie', 'subscription_plan', 'status')
            ->get();
        
        $hasAcademieMembership = $academieMemberships->count() > 0;
        
        // Get user's teams where they are captain
        $teams = \App\Models\Teams::where('capitain', $user->id_compte)->get();
        $hasTeams = $teams->count() > 0;
        
        return response()->json(
            [
                'status' => true,
                'code' => 200,
                'message' => 'Login successful!',
                'data' => [
                    'user_token' => $token,
                    'user' => $user,
                    'roles' => $roles,
                    'token_type' => 'Bearer',
                    'has_academie_membership' => $hasAcademieMembership,
                    'academie_memberships' => $academieMemberships,
                    'has_teams' => $hasTeams,
                    'teams' => $teams
                ],
            ],
            200,
        );
    }

    public function logout(Request $request)
    {
        // Check if the user is authenticated
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated. Please log in first.',
            ], 401);
        }
    
        // Get the current token
        $token = $request->user()->currentAccessToken();
    
        // Check if the token exists
        if (!$token) {
            return response()->json([
                'message' => 'No active session found.',
            ], 404);
        }
    
        // Delete the token
        $token->delete();
    
        return response()->json([
            'message' => 'Logged out successfully!',
        ]);
    }

    public function me(Request $request)
    {
        return CompteResource::collection([$request->user()]);
    }

    public function getUserByToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        // Find the token in the personal access tokens table
        $token = PersonalAccessToken::findToken($request->token);

        if (!$token) {
            return response()->json(
                [
                    'status' => false,
                    'code' => 401,
                    'message' => 'The provided token is invalid or expired.',
                    'errors' => [
                        'token' => ['The provided token is invalid or expired.'],
                    ],
                ],
                401,
            );
        }

        // Retrieve the user associated with the token
        $user = $token->tokenable;
        $roles = $user->getRoleNames();
        
        // Get user's academie memberships with details
        $academieMemberships = AcademieMembers::where('id_compte', $user->id_compte)
            ->select('id_member', 'id_academie', 'subscription_plan', 'status')
            ->get();
        
        $hasAcademieMembership = $academieMemberships->count() > 0;
        
        // Get user's teams where they are captain
        $teams = \App\Models\Teams::where('capitain', $user->id_compte)->get();
        $hasTeams = $teams->count() > 0;
        
        return response()->json(
            [
                'status' => true,
                'code' => 200,
                'message' => 'User retrieved successfully!',
                'data' => [
                    'user_token' => $request->token,
                    'user' => $user,
                    'roles' => $roles,
                    'token_type' => 'Bearer',
                    'has_academie_membership' => $hasAcademieMembership,
                    'academie_memberships' => $academieMemberships,
                    'has_teams' => $hasTeams,
                    'teams' => $teams
                ],
            ],
            200,
        );
    }
}
