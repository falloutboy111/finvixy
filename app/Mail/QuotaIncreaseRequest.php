<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuotaIncreaseRequest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $currentLimit,
        public int $currentUsage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quota Increase Request — '.$this->user->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quota-increase-request',
        );
    }
}
