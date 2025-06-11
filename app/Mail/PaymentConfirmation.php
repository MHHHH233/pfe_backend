<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PaymentConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $numRes;
    public $date;
    public $time;
    public $terrain;
    public $status;
    public $payment_method;
    public $amount;
    public $currency;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $name,
        string $numRes,
        string $date,
        string $time,
        string $terrain,
        string $status,
        string $payment_method,
        ?float $amount = null,
        ?string $currency = null
    ) {
        $this->name = $name;
        $this->numRes = $numRes;
        $this->date = $date;
        $this->time = $time;
        $this->terrain = $terrain;
        $this->status = $status;
        $this->payment_method = $payment_method;
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Confirmation - Terrana FC',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-confirmation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Generate PDF invoice
        $data = [
            'name' => $this->name,
            'numRes' => $this->numRes,
            'date' => $this->date,
            'time' => $this->time,
            'terrain' => $this->terrain,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'amount' => $this->amount,
            'currency' => $this->currency ?? 'MAD',
        ];

        $pdf = PDF::loadView('pdfs.invoice', $data);
        $filename = 'invoice_' . $this->numRes . '.pdf';
        $pdfPath = 'invoices/' . $filename;
        
        // Store the PDF in a temporary location
        Storage::put('public/' . $pdfPath, $pdf->output());
        
        return [
            Attachment::fromStorage('public/' . $pdfPath)
                ->as($filename)
                ->withMime('application/pdf'),
        ];
    }
} 