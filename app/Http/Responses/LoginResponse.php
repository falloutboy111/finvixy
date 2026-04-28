<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 200);
        }

        $user = $request->user();

        if ($user && ! $user->onboarding_completed_at) {
            return redirect()->route('onboarding');
        }

        return redirect()->intended(config('fortify.home'));
    }
}
