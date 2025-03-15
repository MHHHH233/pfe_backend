<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\Terrain;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\TerrainResource;

class TerrainController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:nom_terrain,capacite,type',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
                'type' => 'nullable|string|in:indoor,outdoor',
                'capacite' => 'nullable|string|in:5v5,6v6,7v7',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Terrain::query();

        $this->applyFilters($request, $query);
        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        $paginationSize = $request->input('paginationSize', 10);
        $terrains = $query->paginate($paginationSize);

        return TerrainResource::collection($terrains);
    }

    public function show($id)
    {
        $terrain = Terrain::find($id);

        if (!$terrain) {
            return response()->json(['message' => 'Terrain not found'], 404);
        }

        return new TerrainResource($terrain);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'nom_terrain' => 'required|string|max:100',
                'capacite' => 'required|string|in:5v5,6v6,7v7',
                'type' => 'required|string|in:indoor,outdoor'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $terrain = Terrain::create($validatedData);
            return response()->json([
                'message' => 'Terrain created successfully',
                'data' => new TerrainResource($terrain)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Terrain',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'nom_terrain' => 'required|string|max:100',
                'capacite' => 'required|string|in:5v5,6v6,7v7',
                'type' => 'required|string|in:indoor,outdoor'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $terrain = Terrain::find($id);

        if (!$terrain) {
            return response()->json(['message' => 'Terrain not found'], 404);
        }

        try {
            $terrain->update($validatedData);
            return response()->json([
                'message' => 'Terrain updated successfully',
                'data' => new TerrainResource($terrain)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Terrain',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $terrain = Terrain::find($id);

        if (!$terrain) {
            return response()->json(['message' => 'Terrain not found'], 404);
        }

        try {
            $terrain->delete();
            return response()->json([
                'message' => 'Terrain deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Terrain',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('capacite')) {
            $query->where('capacite', $request->input('capacite'));
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('nom_terrain', 'like', "%$search%");
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_terrain');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['nom_terrain', 'capacite', 'type'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_terrain', 'desc');
        }
    }
} 