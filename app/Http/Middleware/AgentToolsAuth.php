<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentToolsAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.agent_tools.secret');

        if (empty($secret)) {
            return response()->json(['error' => 'Agent tools endpoint is not configured'], 503);
        }

        $provided = $request->bearerToken();

        if (! $provided || ! hash_equals($secret, $provided)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
