<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Mail_Rspw extends Mailable
{
    use Queueable, SerializesModels;
    public $data = [];

    public function __construct($id,$name,$code) {
        $this->data['id'] = $id;
        $this->data['name'] = $name;
        $this->data['code'] = $code;
    }

    public function envelope(): Envelope {
        return new Envelope( subject: 'Password Reset', );
    }

    public function content(): Content {
        return new Content(
            view: 'mail.resetpassword',
            with: $this->data
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
