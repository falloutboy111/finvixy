<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UnlimitedScanMilestone extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $organisationName,
        public int $totalReceiptsThisMonth,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Unlimited Scan Milestone: {$this->organisationName} reached {$this->totalReceiptsThisMonth} receipts this month",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.unlimited-scan-milestone',
        );
    }

    /** @return array<int, \Illuminate\Mail\Mailables\Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
