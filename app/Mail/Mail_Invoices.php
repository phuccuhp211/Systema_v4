<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Mail_Invoices extends Mailable
{
    use Queueable, SerializesModels;

    public $data = [];
    public function __construct($name,$mail,$addr,$number,$notice,$mxn,$date,$pmmt,$ship,$total,$ntotal=null,$coupon=null) {
        $this->data['name'] = $name;
        $this->data['mail'] = $mail;
        $this->data['addr'] = $addr;
        $this->data['number'] = $number;
        $this->data['notice'] = $notice;
        $this->data['mxn'] = $mxn;
        $this->data['date'] = $date;
        $this->data['pmmt'] = $pmmt;
        $this->data['ship'] = $ship;
        $this->data['total'] = $total;
        $this->data['ntotal'] = $ntotal;
        $this->data['coupon'] = $coupon;
    }

    public function envelope(): Envelope {
        return new Envelope( subject: 'E-Invoice', );
    }

    public function content(): Content {
        return new Content(
            view: 'mail.einvoice',
            with: $this->data
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array {
        return [];
    }
}