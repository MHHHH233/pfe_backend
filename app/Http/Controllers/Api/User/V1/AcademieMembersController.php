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
                'payment_method' => 'required|in:cash,online',
                'amount' => 'required_if:payment_method,online|nullable|numeric',
                'currency' => 'required_if:payment_method,online|nullable|string|size:3',
                'payment_method_id' => 'nullable|string',
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

            // Determine membership status based on payment method
            $membershipStatus = 'pending';
            if ($request->payment_method === 'online') {
                $membershipStatus = 'active';
            }

            // Create new membership
            $member = new AcademieMembers;
            $member->id_compte = $compte->id_compte;
            $member->id_academie = $request->id_academie;
            $member->subscription_plan = $request->subscription_plan;
            $member->status = $membershipStatus;
            $member->date_joined = Carbon::now();
            $member->save();

            // If payment method is online, create a payment record
            $payment = null;
            if ($request->payment_method === 'online') {
                // Determine amount based on subscription plan
                $planPrice = $request->subscription_plan === 'premium' 
                    ? ($academie->plan_premium ?? 19.99) 
                    : ($academie->plan_base ?? 9.99);
                
                $amount = $request->amount ?? $planPrice;
                $currency = $request->currency ?? 'usd';

                // Create Stripe payment intent
                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                $paymentIntentData = [
                    'amount' => round($amount * 100), // Convert to cents
                    'currency' => $currency,
                    'automatic_payment_methods' => ['enabled' => true],
                    'metadata' => [
                        'payment_type' => 'academie_subscription',
                        'id_academie' => $request->id_academie,
                        'id_membre' => $member->id_member,
                        'id_compte' => $compte->id_compte,
                        'plan' => $request->subscription_plan
                    ],
                ];
                
                // If payment method ID is provided, attach it to the payment intent
                if ($request->has('payment_method_id') && !empty($request->payment_method_id)) {
                    $paymentIntentData['payment_method'] = $request->payment_method_id;
                }
                
                $paymentIntent = \Stripe\PaymentIntent::create($paymentIntentData);

                // Create payment record
                $payment = new \App\Models\Payment([
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'stripe_payment_method_id' => $request->payment_method_id ?? null,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => 'pending',
                    'payment_method' => 'stripe',
                    'id_academie' => $request->id_academie,
                    'id_compte' => $compte->id_compte,
                    'payment_details' => json_encode([
                        'payment_intent' => $paymentIntent->id,
                        'client_secret' => $paymentIntent->client_secret,
                        'subscription_plan' => $request->subscription_plan,
                        'member_id' => $member->id_member
                    ]),
                ]);
                $payment->save();
                
                // If payment method ID is provided, get payment method details and store them
                if ($request->has('payment_method_id') && !empty($request->payment_method_id)) {
                    try {
                        $paymentMethod = \Stripe\PaymentMethod::retrieve($request->payment_method_id);
                        
                        // Update payment details with payment method info
                        $paymentDetails = json_decode($payment->payment_details, true);
                        $paymentDetails['payment_method'] = [
                            'id' => $paymentMethod->id,
                            'type' => $paymentMethod->type,
                            'brand' => $paymentMethod->type === 'card' ? $paymentMethod->card->brand : null,
                            'last4' => $paymentMethod->type === 'card' ? $paymentMethod->card->last4 : null,
                            'exp_month' => $paymentMethod->type === 'card' ? $paymentMethod->card->exp_month : null,
                            'exp_year' => $paymentMethod->type === 'card' ? $paymentMethod->card->exp_year : null,
                            'billing_name' => $paymentMethod->billing_details->name,
                            'billing_email' => $paymentMethod->billing_details->email,
                            'billing_phone' => $paymentMethod->billing_details->phone
                        ];
                        $payment->payment_details = json_encode($paymentDetails);
                        $payment->save();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Error retrieving payment method details: " . $e->getMessage());
                    }
                }
            }

            $response = [
                'success' => true,
                'data' => new AcademieMembersResource($member),
                'message' => 'Successfully subscribed to academy'
            ];

            // Add payment info to response if available
            if ($payment) {
                $response['payment'] = [
                    'id_payment' => $payment->id_payment,
                    'client_secret' => json_decode($payment->payment_details)->client_secret,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'status' => $payment->status
                ];
            }

            return response()->json($response, 201);
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