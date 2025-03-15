<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademieProgramme;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\AcademieProgrammeResource;

class AcademieProgrammeController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['academie'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:jour,horaire',
                'sort_order' => 'nullable|string|in:asc,desc',
                'search' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = AcademieProgramme::query();

        $this->applySearch($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $programmes = $query->paginate($paginationSize);

        return AcademieProgrammeResource::collection($programmes);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['academie'];
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

        $query = AcademieProgramme::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $programme = $query->find($id);

        if (!$programme) {
            return response()->json(['message' => 'Programme not found'], 404);
        }

        return new AcademieProgrammeResource($programme);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_academie' => 'required|integer|exists:academie,id_academie',
                'jour' => 'required|string|max:255',
                'horaire' => 'required|string|max:255',
                'programme' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $programme = AcademieProgramme::create($validatedData);
            return response()->json([
                'message' => 'Programme created successfully',
                'data' => new AcademieProgrammeResource($programme)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Programme',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_academie' => 'required|integer|exists:academie,id_academie',
                'jour' => 'required|string|max:255',
                'horaire' => 'required|string|max:255',
                'programme' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $programme = AcademieProgramme::find($id);

        if (!$programme) {
            return response()->json(['message' => 'Programme not found'], 404);
        }

        try {
            $programme->update($validatedData);
            return response()->json([
                'message' => 'Programme updated successfully',
                'data' => new AcademieProgrammeResource($programme)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Programme',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $programme = AcademieProgramme::find($id);

        if (!$programme) {
            return response()->json(['message' => 'Programme not found'], 404);
        }

        try {
            $programme->delete();
            return response()->json([
                'message' => 'Programme deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Programme',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applySearch(Request $request, $query)
    {
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('jour', 'like', "%$search%")
                  ->orWhere('horaire', 'like', "%$search%")
                  ->orWhere('programme', 'like', "%$search%");
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'id_programme');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['jour', 'horaire'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('id_programme', 'desc');
        }
    }
}