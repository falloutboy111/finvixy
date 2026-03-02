<?php

namespace App\Http\Middleware;

use App\Services\EmailOtpService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailOtpVerified
{
    public function __construct(
        private readonly EmailOtpService $emailOtpService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // If user has email 2FA enabled and hasn't verified this session
        if ($this->emailOtpService->isRequiredForUser($user)
            && ! $request->session()->get('email_otp_verified', false)) {
            return redirect()->route('email-otp.challenge');
        }

        return $next($request);
    }
}
