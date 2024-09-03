<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationCode;
    public $baseUrl;
    public $action;

    public function __construct($user, $verificationCode, $baseUrl, $action)
    {
        $this->user = $user;
        $this->verificationCode = $verificationCode;
        $this->baseUrl = $baseUrl;
        $this->action = $action;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verification Code Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = '';

        if ($this->action == 'verify') {

            $view = 'verificationView';

        }else{
            $view = 'restPasswordView';
        }

        return new Content(
            view: $view,
            with:[
                'user' => $this->user,
                'verificationCode' => $this->verificationCode,
                'baseUrl' => $this->baseUrl
            ]
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
