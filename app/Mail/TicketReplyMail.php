<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public TicketMessage $ticketMessage,
        public string $trackingUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.reply_subject', ['reference' => $this->ticket->reference]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.ticket-reply',
            with: [
                'ticket' => $this->ticket,
                'ticketMessage' => $this->ticketMessage,
                'trackingUrl' => $this->trackingUrl,
            ],
        );
    }
}
