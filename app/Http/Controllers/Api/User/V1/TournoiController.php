<?php

namespace App\Http\Controllers\api\user\V1;

use App\Http\Controllers\Controller;
use App\Models\Tournoi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\TournoiResource;

class TournoiController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['teams', 'matches'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:name,date_debut,date_fin,frais_entree',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
                'type' => 'nullable|string|in:5v5,6v6,7v7,9v9,11v11'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Tournoi::query();

        $this->applyFilters($request, $query);
        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $tournois = $query->paginate($paginationSize);

        return TournoiResource::collection($tournois);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['teams', 'matches'];
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

        $query = Tournoi::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $tournoi = $query->find($id);

        if (!$tournoi) {
            return response()->json(['message' => 'Tournament not found'], 404);
        }

        return new TournoiResource($tournoi);
    }



    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_tournoi');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['name', 'date_debut', 'date_fin', 'frais_entree'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_tournoi', 'desc');
        }
    }
} 