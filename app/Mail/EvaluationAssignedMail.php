<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EvaluationAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    // Recibimos la entidad (User o Candidate), el token y opcionalmente la fecha límite
    public function __construct(
        public $evaluable,
        public string $token,
        public $expiresAt = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación a Evaluación Psicométrica - SEDYCO',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.evaluation-assigned', // Esta vista la crearemos abajo
        );
    }
}
