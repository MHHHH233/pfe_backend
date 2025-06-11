<?php

namespace App\Http\Controllers\Api\User\V1;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Compte;
use App\Http\Resources\user\V1\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Create a new payment intent.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
                'currency' => 'required|string|size:3',
                'payment_method' => 'required|string|in:stripe,card',
                'id_reservation' => 'nullable|exists:reservation,id_reservation',
                'id_academie' => 'nullable|exists:academie,id_academie',
                'payment_type' => 'required|string|in:reservation,academie_subscription',
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

            // Create a payment intent with Stripe
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $paymentIntent = PaymentIntent::create([
                'amount' => round($request->amount * 100), // Convert to cents
                'currency' => $request->currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'payment_type' => $request->payment_type,
                    'id_compte' => $compte->id_compte,
                    'id_reservation' => $request->id_reservation ?? null,
                    'id_academie' => $request->id_academie ?? null,
                ],
            ]);

            // Create a pending payment record in the database
            $payment = new Payment();
            $payment->stripe_payment_intent_id = $paymentIntent->id;
            $payment->amount = $request->amount;
            $payment->currency = $request->currency;
            $payment->status = 'pending';
            $payment->payment_method = $request->payment_method;
            $payment->id_compte = $compte->id_compte;
            
            if ($request->payment_type === 'reservation' && $request->id_reservation) {
                $payment->id_reservation = $request->id_reservation;
            } elseif ($request->payment_type === 'academie_subscription' && $request->id_academie) {
                $payment->id_academie = $request->id_academie;
            }
            
            $payment->payment_details = json_encode([
                'payment_intent' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
            ]);
            
            $payment->save();

            return response()->json([
                'success' => true,
                'data' => new PaymentResource($payment),
                'client_secret' => $paymentIntent->client_secret,
                'message' => 'Payment intent created successfully'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Payment creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified payment.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $compte = Compte::where('email', $user->email)->first();

            $payment = Payment::where('id_payment', $id)
                ->where('id_compte', $compte->id_compte)
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found or unauthorized'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => new PaymentResource($payment),
                'message' => 'Payment retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Payment retrieval error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update payment status after webhook confirmation
     *
     * @param  string  $paymentIntentId
     * @param  string  $status
     * @return bool
     */
    public static function updatePaymentStatus($paymentIntentId, $status)
    {
        try {
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();
            
            if ($payment) {
                $payment->status = $status;
                $payment->save();
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Payment status update error: ' . $e->getMessage());
            return false;
        }
    }
} 