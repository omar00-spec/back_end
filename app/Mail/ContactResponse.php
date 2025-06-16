<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ContactResponse extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;
    public $responseText;

    /**
     * Create a new message instance.
     */
    public function __construct(Contact $contact, string $responseText)
    {
        $this->contact = $contact;
        $this->responseText = $responseText;
        
        // Ajouter des logs pour le débogage
        Log::info('ContactResponse créé', [
            'contact_id' => $contact->id,
            'email' => $contact->email,
            'response_length' => strlen($responseText)
        ]);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        Log::info('ContactResponse::build appelé', [
            'email' => $this->contact->email
        ]);
        
        return $this->subject('Réponse à votre message - ACOS Football Academy')
                    ->to($this->contact->email) // S'assurer que le destinataire est bien défini
                    ->view('emails.contact-response')
                    ->with([
                        'name' => $this->contact->name,
                        'originalMessage' => $this->contact->message,
                        'response' => $this->responseText
                    ]);
    }
} 