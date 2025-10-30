<?php

namespace App\Mail;

use App\Models\Compte;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompteCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $compte;
    public $client;
    public $motDePasse;

    /**
     * Create a new message instance.
     */
    public function __construct(Compte $compte, Client $client, string $motDePasse = null)
    {
        $this->compte = $compte;
        $this->client = $client;
        $this->motDePasse = $motDePasse;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte bancaire a été créé - Banque Example',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.compte-created',
            with: [
                'compte' => $this->compte,
                'client' => $this->client,
                'motDePasse' => $this->motDePasse,
            ],
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