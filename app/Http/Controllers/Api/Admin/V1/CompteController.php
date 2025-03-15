<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Compte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\CompteResource;

class CompteController extends Controller
{
    /**
     * Get all users with optional filtering and pagination
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Compte::query();

            // Apply filters
            $this->applyFilters($request, $query);
            
            // Apply search
            $this->applySearch($request, $query);
            
            // Apply sorting
            $this->applySorting($request, $query);

            // Apply pagination
            $perPage = $request->input('per_page', 10);
            $users = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => CompteResource::collection($users),
                'meta' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific user's details
     */
    public function show($id): JsonResponse
    {
        try {
            $user = Compte::with(['player', 'reservations'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => new CompteResource($user)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'nom' => 'required|string|max:50',
                'prenom' => 'required|string|max:50',
                'email' => 'required|email|unique:compte,email',
                'password' => 'required|string|min:8',
                'telephone' => 'required|string|max:20',
                'age' => 'required|integer',                
                'role' => 'required|string|in:admin,user'
            ]);

            $validatedData['password'] = Hash::make($validatedData['password']);
            $user = Compte::create($validatedData);
            $user->assignRole($validatedData['role']);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => new CompteResource($user)
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a user's details
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = Compte::findOrFail($id);
            
            $validatedData = $request->validate([
                'nom' => 'string|max:50',
                'prenom' => 'string|max:50',
                'email' => 'email|unique:compte,email,' . $id . ',id_compte',
                'telephone' => 'string|max:20',
                'age' => 'integer',                                
                'role' => 'string|in:admin,user',                
            ]);

            $user->update($validatedData);

            if (isset($validatedData['role'])) {
                $user->syncRoles([$validatedData['role']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => new CompteResource($user)
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

 /**
     * Assign roles to a user.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function assignRoles(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'roles' => 'required|array', // Roles to assign
                'roles.*' => 'string|exists:roles,name', // Validate each role
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $user = Compte::find($id);

        if (!$user) {
            return response()->json(['message' => 'compte not found'], 404);
        }

        try {
            $user->syncRoles($validatedData['roles']); // Sync roles
            return response()->json([
                'message' => 'Roles assigned successfully',
                'data' => new CompteResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign roles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a user
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = Compte::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset a user's password
     */
    public function resetPassword(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            $user = Compte::findOrFail($id);
            $user->update([
                'password' => Hash::make($validatedData['new_password'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply filters to the query
     */
    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('role')) {
            $query->whereHas('roles', function($q) use ($request) {
                $q->where('name', $request->input('role'));
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
    }

    /**
     * Apply search to the query
     */
    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenom', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('telephone', 'like', "%$search%");
            });
        }
    }

    /**
     * Apply sorting to the query
     */
    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_compte');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['nom', 'prenom', 'email', 'date_inscription', 'id_compte'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_compte', 'desc');
        }
    }
} 