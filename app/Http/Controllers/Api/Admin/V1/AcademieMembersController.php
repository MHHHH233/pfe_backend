<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademieMembers;
use App\Models\Academie;
use App\Models\Compte;
use App\Http\Resources\Admin\V1\AcademieMembersResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AcademieMembersController extends Controller
{
    /**
     * Display a listing of the academie members.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $academieMembers = AcademieMembers::with(['compte', 'academie'])->get();
            
            return response()->json([
                'success' => true,
                'data' => AcademieMembersResource::collection($academieMembers),
                'message' => 'Academie members retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve academie members: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created academie member in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_compte' => 'required|exists:compte,id_compte',
                'id_academie' => 'required|exists:academie,id_academie',
                'status' => 'nullable|in:active,inactive',
                'subscription_plan' => 'required|in:base,premium',
                'date_joined' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if member already exists
            $existingMember = AcademieMembers::where('id_compte', $request->id_compte)
                ->where('id_academie', $request->id_academie)
                ->first();

            if ($existingMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user is already a member of this academy'
                ], 409);
            }

            $data = $request->all();
            if (!isset($data['date_joined'])) {
                $data['date_joined'] = Carbon::now();
            }

            $academieMember = AcademieMembers::create($data);

            return response()->json([
                'success' => true,
                'data' => new AcademieMembersResource($academieMember),
                'message' => 'Academie member created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create academie member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified academie member.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $academieMember = AcademieMembers::with(['compte', 'academie'])->find($id);

            if (!$academieMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academie member not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new AcademieMembersResource($academieMember),
                'message' => 'Academie member retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve academie member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified academie member in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_compte' => 'exists:compte,id_compte',
                'id_academie' => 'exists:academie,id_academie',
                'status' => 'nullable|in:active,inactive',
                'subscription_plan' => 'nullable|in:base,premium',
                'date_joined' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $academieMember = AcademieMembers::find($id);

            if (!$academieMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academie member not found'
                ], 404);
            }

            $academieMember->update($request->all());

            return response()->json([
                'success' => true,
                'data' => new AcademieMembersResource($academieMember),
                'message' => 'Academie member updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update academie member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified academie member from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $academieMember = AcademieMembers::find($id);

            if (!$academieMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academie member not found'
                ], 404);
            }

            $academieMember->delete();

            return response()->json([
                'success' => true,
                'message' => 'Academie member deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete academie member: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get members by academie.
     *
     * @param  int  $academieId
     * @return \Illuminate\Http\Response
     */
    public function getByAcademie($academieId)
    {
        try {
            $academie = Academie::find($academieId);

            if (!$academie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academie not found'
                ], 404);
            }

            $members = AcademieMembers::with('compte')
                        ->where('id_academie', $academieId)
                        ->get();

            return response()->json([
                'success' => true,
                'data' => AcademieMembersResource::collection($members),
                'message' => 'Academie members retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve academie members: ' . $e->getMessage()
            ], 500);
        }
    }
} 