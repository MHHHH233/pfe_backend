<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Reservation;
use App\Models\Payment;

class InvoiceController extends Controller
{
    /**
     * Generate and download a PDF invoice for a reservation
     *
     * @param Request $request
     * @param string $reservationNumber
     * @return Response
     */
    public function download(Request $request, string $reservationNumber)
    {
        // Find the reservation by reservation number
        $reservation = Reservation::where('num_res', $reservationNumber)->first();
        
        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found'], 404);
        }
        
        // Get terrain details
        $terrain = DB::table('terrain')
            ->where('id_terrain', $reservation->id_terrain)
            ->first();
            
        $terrainName = $terrain ? $terrain->nom_terrain : 'Unknown';
        
        // Get client name
        $name = $reservation->Name;
        
        if ($reservation->id_client) {
            $client = DB::table('compte')
                ->where('id_compte', $reservation->id_client)
                ->first();
                
            if ($client) {
                $name = $client->nom . ' ' . $client->prenom;
            }
        }
        
        // Get payment details
        $payment = Payment::where('id_reservation', $reservation->id_reservation)
            ->orderBy('created_at', 'desc')
            ->first();
            
        $amount = $terrain ? $terrain->prix : 0;
        $currency = 'MAD';
        $paymentMethod = 'cash';
        
        if ($payment) {
            $amount = $payment->amount;
            $currency = $payment->currency;
            $paymentMethod = $payment->payment_method;
        }
        
        // Prepare data for the PDF
        $data = [
            'name' => $name,
            'numRes' => $reservation->num_res,
            'date' => $reservation->date,
            'time' => $reservation->heure,
            'terrain' => $terrainName,
            'status' => $reservation->etat,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'currency' => $currency,
        ];
        
        // Generate the PDF
        $pdf = PDF::loadView('pdfs.invoice', $data);
        
        // Set filename
        $filename = 'invoice_' . $reservation->num_res . '.pdf';
        
        // Return the PDF as a download
        return $pdf->download($filename);
    }

    /**
     * Generate and stream a PDF invoice for a reservation
     *
     * @param Request $request
     * @param string $reservationNumber
     * @return Response
     */
    public function view(Request $request, string $reservationNumber)
    {
        // Find the reservation by reservation number
        $reservation = Reservation::where('num_res', $reservationNumber)->first();
        
        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found'], 404);
        }
        
        // Get terrain details
        $terrain = DB::table('terrain')
            ->where('id_terrain', $reservation->id_terrain)
            ->first();
            
        $terrainName = $terrain ? $terrain->nom_terrain : 'Unknown';
        
        // Get client name
        $name = $reservation->Name;
        
        if ($reservation->id_client) {
            $client = DB::table('compte')
                ->where('id_compte', $reservation->id_client)
                ->first();
                
            if ($client) {
                $name = $client->nom . ' ' . $client->prenom;
            }
        }
        
        // Get payment details
        $payment = Payment::where('id_reservation', $reservation->id_reservation)
            ->orderBy('created_at', 'desc')
            ->first();
            
        $amount = $terrain ? $terrain->prix : 0;
        $currency = 'MAD';
        $paymentMethod = 'cash';
        
        if ($payment) {
            $amount = $payment->amount;
            $currency = $payment->currency;
            $paymentMethod = $payment->payment_method;
        }
        
        // Prepare data for the PDF
        $data = [
            'name' => $name,
            'numRes' => $reservation->num_res,
            'date' => $reservation->date,
            'time' => $reservation->heure,
            'terrain' => $terrainName,
            'status' => $reservation->etat,
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'currency' => $currency,
        ];
        
        // Generate the PDF
        $pdf = PDF::loadView('pdfs.invoice', $data);
        
        // Stream the PDF
        return $pdf->stream('invoice_' . $reservation->num_res . '.pdf');
    }
} 