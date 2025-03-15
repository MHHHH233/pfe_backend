<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthResource;
use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:compte',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'code' => 422,
                    'message' => 'validation error',
                    'errors' => $validator->errors(),
                ],
                422,
            );
        }

        $user = Compte::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'age' => $request->age,
            'email' => $request->email,
            'password' => Hash::make($request->password),                        
            'telephone' => $request->telephone,            
        ]);

        // Assign the specified role to the new user
        $user->assignRole('user');
        $user->input('date_inscription', now());

        return response()->json(['status' => true, 'code' => 201, 'message' => 'User registered successfully','data'=>$user], 201);
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
                ],
            ],
            200,
        );
    }
}
