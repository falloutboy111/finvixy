<?php

namespace App\Mail;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class QuotaExceededMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $used;

    public int $limit;

    public string $planName;

    public string $daysRemaining;

    public string $upgradeUrl;

    public function __construct(
        public User $user,
        int $usedCount,
        int $limitCount,
        public ?Organisation $organisation = null,
    ) {
        $this->used = $usedCount;
        $this->limit = $limitCount;
        $this->planName = $user->plan?->name ?? 'Free Plan';

        // Calculate days remaining in month
        $endOfMonth = Carbon::now()->endOfMonth();
        $daysLeft = (int) Carbon::now()->diffInDays($endOfMonth, false);
        $this->daysRemaining = max(0, $daysLeft) . ' days';

        // Build upgrade URL
        $this->upgradeUrl = config('app.url') . '/settings/billing';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📊 Upgrade Your Plan - Receipt Quota Exceeded',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quota-exceeded',
            with: [
                'user' => $this->user,
                'used' => $this->used,
                'limit' => $this->limit,
                'planName' => $this->planName,
                'daysRemaining' => $this->daysRemaining,
                'upgradeUrl' => $this->upgradeUrl,
            ],
        );
    }
}
