<?php

namespace App\Jobs;

use App\Models\AgentConversation;
use App\Models\PendingConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExpirePendingConfirmations implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $deleted = PendingConfirmation::where('expires_at', '<', now())->delete();

        Log::info('ExpirePendingConfirmations: removed expired rows', ['count' => $deleted]);

        $pruned = AgentConversation::where('created_at', '<', now()->subDays(7))->delete();

        Log::info('ExpirePendingConfirmations: pruned old conversation history', ['count' => $pruned]);
    }
}
