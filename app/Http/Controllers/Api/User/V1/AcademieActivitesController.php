<?php

namespace App\Http\Controllers\api\user\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademieActivites;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\AcademieActivitesResource;

class AcademieActivitesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['academie', 'members'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],                
                'sort_by' => 'nullable|string|in:title,date_debut,date_fin',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
                'id_academie' => 'nullable|integer'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = AcademieActivites::query();

        $this->applyFilters($request, $query);
        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $activities = $query->paginate($paginationSize);

        return AcademieActivitesResource::collection($activities);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['academie', 'members'];
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

        $query = AcademieActivites::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $activity = $query->find($id);

        if (!$activity) {
            return response()->json(['message' => 'Activity not found'], 404);
        }

        return new AcademieActivitesResource($activity);
    }
   
    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('id_academie')) {
            $query->where('id_academie', $request->input('id_academie'));
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_activites');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['title', 'date_debut', 'date_fin'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_activites', 'desc');
        }
    }
} 