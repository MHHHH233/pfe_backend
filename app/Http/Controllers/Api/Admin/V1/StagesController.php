<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Stages;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\StagesResource;

class StagesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['matches'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:stage_name',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Stages::query();

        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $stages = $query->paginate($paginationSize);

        return StagesResource::collection($stages);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['matches'];
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

        $query = Stages::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $stage = $query->find($id);

        if (!$stage) {
            return response()->json(['message' => 'Stage not found'], 404);
        }

        return new StagesResource($stage);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'stage_name' => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $stage = Stages::create($validatedData);
            return response()->json([
                'message' => 'Stage created successfully',
                'data' => new StagesResource($stage)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Stage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'stage_name' => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $stage = Stages::find($id);

        if (!$stage) {
            return response()->json(['message' => 'Stage not found'], 404);
        }

        try {
            $stage->update($validatedData);
            return response()->json([
                'message' => 'Stage updated successfully',
                'data' => new StagesResource($stage)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Stage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $stage = Stages::find($id);

        if (!$stage) {
            return response()->json(['message' => 'Stage not found'], 404);
        }

        try {
            $stage->delete();
            return response()->json([
                'message' => 'Stage deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Stage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('stage_name', 'like', "%$search%");
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_stage');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['stage_name'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_stage', 'desc');
        }
    }
}