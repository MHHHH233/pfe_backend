<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Reviews;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\ReviewsResource;

class ReviewsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['compte'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:id_review,name,created_at,status',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
                'status' => 'nullable|string|in:pending,approved,rejected'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Reviews::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $this->applySearch($request, $query);
        $this->applyFilters($request, $query);
        $this->applySorting($request, $query);

        $paginationSize = $request->input('paginationSize', 10);
        $reviews = $query->paginate($paginationSize);

        return ReviewsResource::collection($reviews);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['compte'];
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

        $query = Reviews::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $review = $query->find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        return new ReviewsResource($review);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_compte' => 'required|integer|exists:compte,id_compte',
                'name' => 'required|string',
                'description' => 'required|string',
                'status' => 'required|string|in:pending,approved,rejected'
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

    public function update(Request $request, $id): JsonResponse
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

        $review = Reviews::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        try {
            $review->update($validatedData);
            return response()->json([
                'message' => 'Review updated successfully',
                'data' => new ReviewsResource($review)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Review',
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

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
            });
        }
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_review');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['id_review', 'name', 'created_at', 'status'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_review', 'desc');
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|string|in:' . implode(',', [
                    Reviews::STATUS_PENDING,
                    Reviews::STATUS_APPROVED,
                    Reviews::STATUS_REJECTED
                ])
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $review = Reviews::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        try {
            $review->update(['status' => $validatedData['status']]);
            return response()->json([
                'message' => 'Review status updated successfully',
                'data' => new ReviewsResource($review)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Review status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 