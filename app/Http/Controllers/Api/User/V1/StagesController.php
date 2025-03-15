<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Models\Stages;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\User\V1\StagesResource;

class StagesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:stage_name', // Use 'stage_name' instead of 'name'
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
                'stage' => 'nullable|integer',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Stages::query();

        $this->applyFilters($request, $query);
        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        $paginationSize = $request->input('paginationSize', 10);
        $stages = $query->paginate($paginationSize);

        return StagesResource::collection($stages);
    }

    public function show($id, Request $request)
    {
        $stage = Stages::find($id);

        if (!$stage) {
            return response()->json(['message' => 'Stage not found'], 404);
        }

        return new StagesResource($stage);
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('stage_name')) { // Use 'stage_name' instead of 'name'
            $query->where('stage_name', $request->input('stage_name'));
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('stage_name', 'like', "%$search%"); // Use 'stage_name' instead of 'name'
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'stage_name'); // Use 'stage_name' instead of 'name'
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSortBy = ['stage_name']; // Use 'stage_name' instead of 'name'
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('stage_name', 'asc'); // Use 'stage_name' instead of 'name'
        }
    }
}