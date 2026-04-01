<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class TrustedDeviceService
{
    public const COOKIE_NAME = 'finvixy_trusted_device';

    public const DEFAULT_DAYS = 7;

    public const EXTENDED_DAYS = 30;

    /**
     * Issue a new trusted device token and return the cookie.
     */
    public function issueCookie(User $user, bool $rememberLong = false): Cookie
    {
        $token = Str::random(60);
        $days = $rememberLong ? self::EXTENDED_DAYS : self::DEFAULT_DAYS;
        $expiresAt = now()->addDays($days)->timestamp;

        $devices = $user->trusted_devices ?? [];

        // Prune expired tokens
        $devices = array_values(array_filter($devices, fn ($d) => $d['expires_at'] > now()->timestamp));

        $devices[] = [
            'token' => hash('sha256', $token),
            'expires_at' => $expiresAt,
        ];

        // Keep at most 10 trusted devices per user
        if (count($devices) > 10) {
            $devices = array_slice($devices, -10);
        }

        $user->update(['trusted_devices' => $devices]);

        return cookie(
            name: self::COOKIE_NAME,
            value: $token,
            minutes: $days * 60 * 24,
            secure: request()->isSecure(),
            httpOnly: true,
            sameSite: 'Lax',
        );
    }

    /**
     * Check if the request's device cookie is trusted for the given user.
     */
    public function isTrusted(Request $request, User $user): bool
    {
        $cookieValue = $request->cookie(self::COOKIE_NAME);

        if (! $cookieValue) {
            return false;
        }

        $hash = hash('sha256', $cookieValue);
        $now = now()->timestamp;

        $devices = $user->trusted_devices ?? [];

        return collect($devices)->contains(fn ($d) => $d['token'] === $hash && $d['expires_at'] > $now);
    }

    /**
     * Revoke all trusted devices for a user (e.g. on password change).
     */
    public function revokeAll(User $user): void
    {
        $user->update(['trusted_devices' => []]);
    }
}
