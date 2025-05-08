<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Models\Reviews;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\User\V1\ReviewsResource;

class ReviewsController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $reviews = Reviews::with('compte')->get();
            return response()->json([
                'data' => ReviewsResource::collection($reviews)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch reviews',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_compte' => 'required|integer|exists:compte,id_compte',
                'name' => 'required|string',
                'description' => 'required|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $review = Reviews::create($validatedData);
            return response()->json([
                'message' => 'Review created successfully',
                'data' => new ReviewsResource($review)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $review = Reviews::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        try {
            $review->delete();
            return response()->json([
                'message' => 'Review deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Review',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 