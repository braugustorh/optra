<?php

namespace App\Mail;

use App\Models\Answer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;
    public $data; // Almacena todos los datos

    public function __construct(array $data)
    {
        $this->data = $data; // Recibe el array completo
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('sedyco@adcentrales.com', 'SEDyCO adc'),
            subject: 'Nuevo mensaje de contacto desde la Plataforma',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form',
            with: [
                'name' => $this->data['name'],
                'terminal' => $this->data['terminal'] ?? null, // Usa null si no existe
                'email' => $this->data['email'],
                'message_form' => $this->data['message'],
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
