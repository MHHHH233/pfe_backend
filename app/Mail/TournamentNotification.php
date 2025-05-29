<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TournamentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $tournamentName;
    public $description;
    public $type;
    public $capacity;
    public $dateStart;
    public $dateEnd;
    public $fee;
    public $award;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $name,
        string $tournamentName,
        string $description,
        string $type,
        int $capacity,
        string $dateStart,
        string $dateEnd,
        string $fee,
        string $award
    ) {
        $this->name = $name;
        $this->tournamentName = $tournamentName;
        $this->description = $description;
        $this->type = $type;
        $this->capacity = $capacity;
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
        $this->fee = $fee;
        $this->award = $award;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Tournament: ' . $this->tournamentName,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tournament-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
} 