<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketSolvedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket, public string $csatUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.solved_subject', ['reference' => $this->ticket->reference]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ticket-solved',
            with: [
                'ticket' => $this->ticket,
                'csatUrl' => $this->csatUrl,
            ],
        );
    }
}
