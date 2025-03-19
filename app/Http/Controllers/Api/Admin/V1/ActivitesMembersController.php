<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivitesMembers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Admin\V1\ActivitesMembersResource;

class ActivitesMembersController extends Controller
{
    public function index(Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['activite', 'member'];
                        $includes = explode(',', $value);
                        foreach ($includes as $include) {
                            if (!in_array($include, $validIncludes)) {
                                $fail('The selected ' . $attribute . ' is invalid.');
                            }
                        }
                    },
                ],
                'paginationSize' => 'nullable|integer|min:1',
                'sort_by' => 'nullable|string|in:date_joined',
                'sort_order' => 'nullable|string|in:asc,desc',
                'id_activite' => 'nullable|integer'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $query = ActivitesMembers::query();

        $this->applyFilters($request, $query);
        $this->applySorting($request, $query);

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $paginationSize = $request->input('paginationSize', 10);
        $members = $query->paginate($paginationSize);

        return ActivitesMembersResource::collection($members);
    }

    public function show($id, Request $request)
    {
        try {
            $request->validate([
                'include' => [
                    'nullable',
                    'string',
                    function ($attribute, $value, $fail) {
                        $validIncludes = ['activite', 'member'];
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

        $query = ActivitesMembers::query();

        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $member = $query->find($id);

        if (!$member) {
            return response()->json(['message' => 'Activity Member not found'], 404);
        }

        return new ActivitesMembersResource($member);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_activites' => 'required|integer|exists:academie_activites,id_activites',
                'id_compte' => 'required|integer|exists:compte,id_compte',
                'date_joined' => 'required|date'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        try {
            $member = ActivitesMembers::create($validatedData);
            return response()->json([
                'message' => 'Activity Member created successfully',
                'data' => new ActivitesMembersResource($member)
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create Activity Member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_activites' => 'required|integer|exists:academie_activites,id_activites',
                'id_compte' => 'required|integer|exists:compte,id_compte',
                'date_joined' => 'required|date'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        $member = ActivitesMembers::find($id);

        if (!$member) {
            return response()->json(['message' => 'Activity Member not found'], 404);
        }

        try {
            $member->update($validatedData);
            return response()->json([
                'message' => 'Activity Member updated successfully',
                'data' => new ActivitesMembersResource($member)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update Activity Member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $member = ActivitesMembers::find($id);

        if (!$member) {
            return response()->json(['message' => 'Activity Member not found'], 404);
        }

        try {
            $member->delete();
            return response()->json([
                'message' => 'Activity Member deleted successfully'
            ], 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete Activity Member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected function applyFilters(Request $request, $query)
    {
        if ($request->has('id_activite')) {
            $query->where('id_activites', $request->input('id_activite'));
        }
    }

    protected function applySorting(Request $request, $query)
    {
        $sortBy = $request->input('sort_by', 'date_joined');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortBy = ['date_joined'];
        $allowedSortOrder = ['asc', 'desc'];

        if (in_array($sortBy, $allowedSortBy) && in_array($sortOrder, $allowedSortOrder)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('date_joined', 'desc');
        }
    }
}