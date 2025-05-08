<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Models\AcademieMembers;
use App\Models\Academie;
use App\Models\Compte;
use App\Models\ActivitesMembers;
use App\Http\Resources\user\V1\AcademieMembersResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AcademieMembersController extends Controller
{
    /**
     * Subscribe to an academy.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function subscribe(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_academie' => 'required|exists:academie,id_academie',
                'subscription_plan' => 'required|in:base,premium',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $compte = Compte::where('email', $user->email)->first();

            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'message' => 'User account not found'
                ], 404);
            }

            // Check if already a member
            $existingMember = AcademieMembers::where('id_compte', $compte->id_compte)
                ->where('id_academie', $request->id_academie)
                ->first();

            if ($existingMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already a member of this academy'
                ], 409);
            }

            $academie = Academie::find($request->id_academie);
            if (!$academie) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academy not found'
                ], 404);
            }

            // Create new membership
            $member = new AcademieMembers;
            $member->id_compte = $compte->id_compte;
            $member->id_academie = $request->id_academie;
            $member->subscription_plan = $request->subscription_plan;
            $member->status = 'active';
            $member->date_joined = Carbon::now();
            $member->save();

            return response()->json([
                'success' => true,
                'data' => new AcademieMembersResource($member),
                'message' => 'Successfully subscribed to academy'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to subscribe to academy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel academy membership.
     *
     * @param  int  $academieId
     * @return \Illuminate\Http\Response
     */
    public function cancelSubscription($academieId)
    {
        try {
            $user = Auth::user();
            $compte = Compte::where('email', $user->email)->first();

            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'message' => 'User account not found'
                ], 404);
            }

            $membership = AcademieMembers::where('id_compte', $compte->id_compte)
                ->where('id_academie', $academieId)
                ->first();

            if (!$membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this academy'
                ], 404);
            }

            // Get the member ID before deleting
            $memberId = $membership->id_member;

            // Delete activities memberships first using the correct foreign key
            ActivitesMembers::whereHas('activity', function($query) use ($academieId) {
                $query->where('id_academie', $academieId);
            })->where('id_member_ref', $memberId)->delete();

            // Then delete the membership
            $membership->delete();

            return response()->json([
                'success' => true,
                'message' => 'Membership cancelled successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel membership: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's academy memberships.
     *
     * @return \Illuminate\Http\Response
     */
    public function myMemberships()
    {
        try {
            $user = Auth::user();
            $compte = Compte::where('email', $user->email)->first();

            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'message' => 'User account not found'
                ], 404);
            }

            $memberships = AcademieMembers::with('academie')
                ->where('id_compte', $compte->id_compte)
                ->get();

            return response()->json([
                'success' => true,
                'data' => AcademieMembersResource::collection($memberships),
                'message' => 'Memberships retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve memberships: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update membership plan.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $academieId
     * @return \Illuminate\Http\Response
     */
    public function updatePlan(Request $request, $academieId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'subscription_plan' => 'required|in:base,premium',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $compte = Compte::where('email', $user->email)->first();

            if (!$compte) {
                return response()->json([
                    'success' => false,
                    'message' => 'User account not found'
                ], 404);
            }

            $membership = AcademieMembers::where('id_compte', $compte->id_compte)
                ->where('id_academie', $academieId)
                ->first();

            if (!$membership) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a member of this academy'
                ], 404);
            }

            $membership->subscription_plan = $request->subscription_plan;
            $membership->save();

            return response()->json([
                'success' => true,
                'data' => new AcademieMembersResource($membership),
                'message' => 'Subscription plan updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subscription plan: ' . $e->getMessage()
            ], 500);
        }
    }
} 