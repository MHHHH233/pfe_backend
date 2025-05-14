<?php

namespace App\Http\Controllers\api\user\V1;

use App\Http\Controllers\Controller;
use App\Models\Terrain;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\TerrainResource;

class TerrainController extends Controller
{
    

    public function index(Request $request)
    {
        try {
            $request->validate([
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:nom_terrain,capacite,type,prix',
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

        // Process images to add base URL if needed
        foreach ($terrains as $terrain) {
            if ($terrain->image_path && !str_starts_with($terrain->image_path, 'http')) {
                $terrain->image_path = $terrain->image_path;
            }
        }

        return TerrainResource::collection($terrains);
    }

    public function show($id)
    {
        $terrain = Terrain::find($id);

        if (!$terrain) {
            return response()->json(['message' => 'Terrain not found'], 404);
        }

        // Add base URL to image_path if it doesn't start with http
        if ($terrain->image_path && !str_starts_with($terrain->image_path, 'http')) {
            $terrain->image_path =$terrain->image_path;
        }

        return new TerrainResource($terrain);
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

        $allowedSortBy = ['nom_terrain', 'capacite', 'type', 'prix'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_terrain', 'desc');
        }
    }
} 