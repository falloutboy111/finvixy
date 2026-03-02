<?php

namespace App\Services;

use App\Models\EmailOtp;
use App\Models\User;
use App\Notifications\SendEmailOtp;

class EmailOtpService
{
    /**
     * Generate and send an email OTP to the user.
     */
    public function send(User $user): EmailOtp
    {
        // Invalidate any existing unused OTPs
        EmailOtp::query()
            ->where('user_id', $user->id)
            ->where('used', false)
            ->update(['used' => true]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = EmailOtp::query()->create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

        $user->notify(new SendEmailOtp($code));

        return $otp;
    }

    /**
     * Verify an OTP code for a user.
     */
    public function verify(User $user, string $code): bool
    {
        $otp = EmailOtp::query()
            ->where('user_id', $user->id)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return false;
        }

        $otp->update(['used' => true]);

        return true;
    }

    /**
     * Check if a user should be required to use email 2FA.
     */
    public function isRequiredForUser(User $user): bool
    {
        return $user->hasEmail2faEnabled();
    }

    /**
     * Check if a user should be prompted to enable email 2FA (7 days after signup).
     */
    public function shouldPromptSetup(User $user): bool
    {
        return $user->shouldPromptEmail2fa();
    }
}
