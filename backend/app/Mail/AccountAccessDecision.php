<?php

namespace App\Mail;

use App\Models\AccountAccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountAccessDecision extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AccountAccessRequest $accessRequest,
        public ?string $setupUrl,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->setupUrl
            ? 'Sua solicitação de acesso foi aprovada'
            : 'Atualização da sua solicitação de acesso';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.account-access-decision');
    }
}
