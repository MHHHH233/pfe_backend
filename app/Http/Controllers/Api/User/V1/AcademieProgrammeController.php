<?php

namespace App\Http\Controllers\api\user\V1;


use App\Http\Controllers\Controller;
use App\Models\AcademieProgramme;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\AcademieProgrammeResource;

class AcademieProgrammeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:jour',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
                'id_academie' => 'nullable|integer',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = AcademieProgramme::query();

        $this->applyFilters($request, $query);
        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        $paginationSize = $request->input('paginationSize', 10);
        $programmes = $query->paginate($paginationSize);

        return AcademieProgrammeResource::collection($programmes);
    }

    public function show($id, Request $request)
    {
        $programme = AcademieProgramme::find($id);

        if (!$programme) {
            return response()->json(['message' => 'Programme not found'], 404);
        }

        return new AcademieProgrammeResource($programme);
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
            $query->where('programme', 'like', "%$search%");
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'jour');
        $sortOrder = $request->input('sort_order', 'asc');

        $allowedSortBy = ['jour'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('jour', 'asc');
        }
    }
}