<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\ReportedBug;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\ReportedBugResource;

class ReportedBugController extends Controller
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
                'sort_by' => 'nullable|string|in:id_bug',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = ReportedBug::query();

        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $bugs = $query->paginate($paginationSize);

        return ReportedBugResource::collection($bugs);
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

        $query = ReportedBug::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $bug = $query->find($id);

        if (!$bug) {
            return response()->json(['message' => 'Bug report not found'], 404);
        }

        return new ReportedBugResource($bug);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_compte' => 'required|integer|exists:compte,id_compte',
                'description' => 'required|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $bug = ReportedBug::create($validatedData);
            return response()->json([
                'message' => 'Bug report created successfully',
                'data' => new ReportedBugResource($bug)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Bug report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_compte' => 'required|integer|exists:compte,id_compte',
                'description' => 'required|string'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $bug = ReportedBug::find($id);

        if (!$bug) {
            return response()->json(['message' => 'Bug report not found'], 404);
        }

        try {
            $bug->update($validatedData);
            return response()->json([
                'message' => 'Bug report updated successfully',
                'data' => new ReportedBugResource($bug)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Bug report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $bug = ReportedBug::find($id);

        if (!$bug) {
            return response()->json(['message' => 'Bug report not found'], 404);
        }

        try {
            $bug->delete();
            return response()->json([
                'message' => 'Bug report deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Bug report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('description', 'like', "%$search%");
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_bug');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['id_bug'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_bug', 'desc');
        }
    }
} 