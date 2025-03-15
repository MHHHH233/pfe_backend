<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\RatingResource;

class RatingController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['ratingPlayer', 'ratedPlayer', 'ratedTeam'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:stars',
                'sort_order' => 'nullable|string|in:asc,desc',
                'stars' => 'nullable|string|in:1,2,3,4,5'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Rating::query();

        $this->applyFilters($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $ratings = $query->paginate($paginationSize);

        return RatingResource::collection($ratings);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['ratingPlayer', 'ratedPlayer', 'ratedTeam'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Rating::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $rating = $query->find($id);

        if (!$rating) {
            return response()->json(['message' => 'Rating not found'], 404);
        }

        return new RatingResource($rating);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_rating_player' => 'required|integer|exists:players,id_player',
                'id_rated_player' => 'nullable|integer|exists:players,id_player',
                'id_rated_team' => 'nullable|integer|exists:teams,id_teams',
                'stars' => 'required|string|in:1,2,3,4,5'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $rating = Rating::create($validatedData);
            return response()->json([
                'message' => 'Rating created successfully',
                'data' => new RatingResource($rating)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Rating',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_rating_player' => 'required|integer|exists:players,id_player',
                'id_rated_player' => 'nullable|integer|exists:players,id_player',
                'id_rated_team' => 'nullable|integer|exists:teams,id_teams',
                'stars' => 'required|string|in:1,2,3,4,5'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $rating = Rating::find($id);

        if (!$rating) {
            return response()->json(['message' => 'Rating not found'], 404);
        }

        try {
            $rating->update($validatedData);
            return response()->json([
                'message' => 'Rating updated successfully',
                'data' => new RatingResource($rating)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Rating',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $rating = Rating::find($id);

        if (!$rating) {
            return response()->json(['message' => 'Rating not found'], 404);
        }

        try {
            $rating->delete();
            return response()->json([
                'message' => 'Rating deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Rating',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('stars')) {
            $query->where('stars', $request->input('stars'));
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_rating');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['stars'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_rating', 'desc');
        }
    }
} 