<?php

use Illuminate\Support\Facades\Route;

// WhatsApp Business API Webhook (stateless, no CSRF)
Route::prefix('webhooks/whatsapp')->group(function () {
    Route::get('/', [\App\Http\Controllers\WhatsAppWebhookController::class, 'verify']);
    Route::post('/', [\App\Http\Controllers\WhatsAppWebhookController::class, 'handle']);
});

// Agent tools dispatch — secret-auth, no session/CSRF
Route::middleware('agent.tools.auth')
    ->post('/agent/tools/dispatch', [\App\Http\Controllers\AgentToolsController::class, 'dispatch']);
