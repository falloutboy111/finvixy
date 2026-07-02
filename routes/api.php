<?php

use Illuminate\Support\Facades\Route;

// WhatsApp Business API Webhook (stateless, no CSRF)
Route::prefix('webhooks/whatsapp')->group(function () {
    Route::get('/', [\App\Http\Controllers\WhatsAppWebhookController::class, 'verify']);
    Route::post('/', [\App\Http\Controllers\WhatsAppWebhookController::class, 'handle']);
});

// Agent tools dispatch — secret-auth, no session/CSRF. Throttled so a leaked
// bearer cannot drive unbounded enumeration of the tool surface.
Route::middleware(['agent.tools.auth', 'throttle:300,1'])
    ->post('/agent/tools/dispatch', [\App\Http\Controllers\AgentToolsController::class, 'dispatch']);
