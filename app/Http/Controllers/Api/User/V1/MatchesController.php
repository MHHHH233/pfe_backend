<?php

namespace App\Http\Controllers\api\user\V1;


use App\Http\Controllers\Controller;
use App\Models\Matches;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\MatchesResource;

class MatchesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['tournoi', 'team1', 'team2', 'stage'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:match_date',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
                'stage' => 'nullable|integer',
                'id_tournoi' => 'nullable|integer'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = Matches::query();

        $this->applyFilters($request, $query);
        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $matches = $query->paginate($paginationSize);

        return MatchesResource::collection($matches);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['tournoi', 'team1', 'team2', 'stage'];
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

        $query = Matches::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $match = $query->find($id);

        if (!$match) {
            return response()->json(['message' => 'Match not found'], 404);
        }

        return new MatchesResource($match);
    }


    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('stage')) {
            $query->where('stage', $request->input('stage'));
        }

        if ($request->has('id_tournoi')) {
            $query->where('id_tournoi', $request->input('id_tournoi'));
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('team1', function($q) use ($search) {
                $q->where('team_name', 'like', "%$search%");
            })->orWhereHas('team2', function($q) use ($search) {
                $q->where('team_name', 'like', "%$search%");
            });
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'match_date');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['match_date'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('match_date', 'desc');
        }
    }
} 