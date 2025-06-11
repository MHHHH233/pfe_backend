<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\AcademieMembers;
use App\Http\Controllers\Api\User\V1\PaymentController;
use Illuminate\Support\Facades\Log;

class StripeController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // Set default values
            $amount = $request->amount ?? 1000;
            $currency = strtolower($request->currency ?? 'usd');
            
            // Handle minimum amount requirements for different currencies
            // MAD requires special handling
            if ($currency === 'mad') {
                // For MAD, convert to a higher amount to meet Stripe's minimum requirement
                // 1 MAD = approximately 0.09 EUR, so we need at least 550 MAD to meet the 50 cents minimum
                if ($amount < 550) {
                    $amount = 550; // Minimum amount that converts to at least 50 cents
                }
            }
            
            $intent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => $currency,
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => $request->metadata ?? [],
            ]);

            return response()->json([
                'clientSecret' => $intent->client_secret,
                'amount' => $amount,
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function retrievePaymentIntent(Request $request, $paymentIntentId)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        
        try {
            $intent = PaymentIntent::retrieve($paymentIntentId);
            return response()->json([
                'status' => $intent->status,
                'amount' => $intent->amount,
                'currency' => $intent->currency,
                'client_secret' => $intent->client_secret,
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Handle Stripe webhook events
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function webhook(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch(SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Stripe webhook signature error: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handleSuccessfulPayment($paymentIntent);
                break;
            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handleFailedPayment($paymentIntent);
                break;
            default:
                Log::info('Unhandled Stripe event: ' . $event->type);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle successful payment
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return void
     */
    protected function handleSuccessfulPayment($paymentIntent)
    {
        try {
            // Update payment status in database
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();
            
            if (!$payment) {
                Log::warning('Payment not found for PaymentIntent: ' . $paymentIntent->id);
                return;
            }
            
            // Get payment method details from Stripe
            if ($paymentIntent->payment_method) {
                try {
                    Stripe::setApiKey(env('STRIPE_SECRET'));
                    $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentIntent->payment_method);
                    
                    // Store payment method ID
                    $payment->stripe_payment_method_id = $paymentMethod->id;
                    
                    // Store payment method details in payment_details JSON
                    $paymentDetails = json_decode($payment->payment_details, true) ?: [];
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
                    
                    Log::info('Payment method details stored for payment: ' . $payment->id_payment);
                } catch (\Exception $e) {
                    Log::error('Error retrieving payment method: ' . $e->getMessage());
                }
            }
            
            // Update payment status
            $payment->status = 'completed';
            $payment->save();
            
            // Check payment type and update related records
            if ($payment->id_reservation) {
                $reservation = Reservation::find($payment->id_reservation);
                if ($reservation) {
                    $reservation->etat = 'reserver';
                    $reservation->save();
                    Log::info('Reservation status updated after payment: ' . $payment->id_reservation);
                }
            } elseif ($payment->id_academie) {
                // Find the membership record
                $paymentDetails = json_decode($payment->payment_details, true);
                $memberId = $paymentDetails['member_id'] ?? null;
                
                if ($memberId) {
                    $member = AcademieMembers::find($memberId);
                    if ($member) {
                        $member->status = 'active';
                        $member->save();
                        Log::info('Academy membership activated after payment: ' . $memberId);
                    }
                }
            }
            
            Log::info('Payment marked as completed: ' . $payment->id_payment);
        } catch (\Exception $e) {
            Log::error('Error handling successful payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle failed payment
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     * @return void
     */
    protected function handleFailedPayment($paymentIntent)
    {
        try {
            // Update payment status in database
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();
            
            if (!$payment) {
                Log::warning('Payment not found for failed PaymentIntent: ' . $paymentIntent->id);
                return;
            }
            
            // Update payment status
            $payment->status = 'failed';
            $payment->save();
            
            Log::info('Payment marked as failed: ' . $payment->id_payment);
        } catch (\Exception $e) {
            Log::error('Error handling failed payment: ' . $e->getMessage());
        }
    }

    /**
     * Attach a payment method to a payment intent
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function attachPaymentMethod(Request $request)
    {
        try {
            $request->validate([
                'payment_intent_id' => 'required|string',
                'payment_method_id' => 'required|string',
            ]);

            Stripe::setApiKey(env('STRIPE_SECRET'));

            // First, verify that the payment intent exists
            try {
                $intent = PaymentIntent::retrieve($request->payment_intent_id);
            } catch (\Exception $e) {
                Log::error('Error retrieving payment intent: ' . $e->getMessage());
                return response()->json([
                    'error' => 'Payment intent not found'
                ], 404);
            }

            // Update the payment intent with the payment method
            $intent->update([
                'payment_method' => $request->payment_method_id
            ]);

            // Also update our local record
            $payment = Payment::where('stripe_payment_intent_id', $request->payment_intent_id)->first();
            
            if ($payment) {
                $payment->stripe_payment_method_id = $request->payment_method_id;
                
                // Retrieve payment method details
                $paymentMethod = \Stripe\PaymentMethod::retrieve($request->payment_method_id);
                
                // Store payment method details in payment_details JSON
                $paymentDetails = json_decode($payment->payment_details, true) ?: [];
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
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment method attached successfully',
                'payment_intent' => [
                    'id' => $intent->id,
                    'client_secret' => $intent->client_secret,
                    'status' => $intent->status
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error attaching payment method: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}

