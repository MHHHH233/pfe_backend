<?php

namespace App\Http\Controllers\api\user\V1;


use App\Http\Controllers\Controller;
use App\Models\ActivitesMembers;
use App\Models\AcademieActivites;
use App\Models\AcademieMembers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\user\V1\ActivitesMembersResource;

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

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'id_activites' => 'required|integer|exists:academie_activites,id_activites',
                'id_member_ref' => 'required|integer|exists:academie_members,id_member',
                'date_joined' => 'required|date'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        }

        // Check if the member is already in this activity
        $existingMember = ActivitesMembers::where('id_activites', $validatedData['id_activites'])
            ->where('id_member_ref', $validatedData['id_member_ref'])
            ->first();
            
        if ($existingMember) {
            return response()->json([
                'message' => 'This member is already in this activity',
                'data' => new ActivitesMembersResource($existingMember)
            ], 409); // 409 Conflict
        }

        try {
            // Get the academy ID of the activity
            $activity = AcademieActivites::find($validatedData['id_activites']);
            if (!$activity) {
                return response()->json([
                    'message' => 'Activity not found'
                ], 404);
            }

            // Check if the academie_member belongs to the same academy as the activity
            $academyMember = AcademieMembers::find($validatedData['id_member_ref']);
            if (!$academyMember) {
                return response()->json([
                    'message' => 'Academy member not found'
                ], 404);
            }
            
            if ($academyMember->id_academie != $activity->id_academie) {
                return response()->json([
                    'message' => 'You must be a member of the academy before joining its activities',
                    'academie_id' => $activity->id_academie
                ], 403);
            }

            // Create the member record
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

    public function destroy($id): JsonResponse
    {
        $member = ActivitesMembers::find($id);

        if (!$member) {
            return response()->json(['message' => 'Activity Member not found'], 404);
        }

        // Optional: Add authorization check here if needed
        // For example, check if the current user is the owner of this record or has admin rights
        // If(auth()->user()->id !== $member->id_compte && !auth()->user()->isAdmin()) {
        //     return response()->json(['message' => 'Unauthorized to delete this record'], 403);
        // }

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
} 