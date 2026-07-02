<?php

namespace App\Console\Commands;

use App\Models\AgentConversation;
use App\Models\AgentSession;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Closes conversation sessions idle for SESSION_INACTIVITY_MINUTES, notifies
 * the user on WhatsApp, and clears the conversation context (Laravel
 * agent_conversations rows — the only place context persists; the Python
 * runtime gets a fresh session id per call and holds no reusable state).
 *
 * Idempotent: each row is claimed with an atomic UPDATE ... WHERE closed_at IS
 * NULL, so overlapping runs can never double-send. Sessions with no completed
 * agent exchange are closed silently (no message). Users, orgs and expenses
 * are never touched.
 *
 * Scheduled every 5 minutes, gated behind SESSION_SWEEPER_ENABLED.
 */
class SweepInactiveSessions extends Command
{
    protected $signature = 'finvixy:sweep-inactive-sessions {--dry-run : Report without closing}';

    protected $description = 'Close conversation sessions idle beyond SESSION_INACTIVITY_MINUTES, notify the user, and clear their conversation context';

    public function handle(WhatsAppService $whatsApp): int
    {
        $minutes = max(1, (int) config('services.agentcore.inactivity_minutes', 10));
        $cutoff  = now()->subMinutes($minutes);

        $stale = AgentSession::whereNull('closed_at')
            ->where('last_activity_at', '<', $cutoff)
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No inactive sessions.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->line("Would close {$stale->count()} session(s) idle since before {$cutoff->toDateTimeString()}.");

            return self::SUCCESS;
        }

        $closed = 0;

        foreach ($stale as $session) {
            // Atomic claim — only the run that flips the row proceeds, so an
            // overlapping sweeper invocation can never double-send.
            $won = AgentSession::whereKey($session->id)
                ->whereNull('closed_at')
                ->update(['closed_at' => now(), 'closed_reason' => 'inactivity']);

            if ($won !== 1) {
                continue;
            }

            // Notify only sessions that had a real exchange; best-effort — a
            // failed send is logged (no PII) and never blocks context clearing.
            if ($session->exchange_count > 0) {
                try {
                    $user = User::find($session->user_id);

                    if ($user?->whatsapp_number) {
                        $whatsApp->sendText(
                            ltrim($user->whatsapp_number, '+'),
                            "Your chat session closed after {$minutes} minutes of inactivity. "
                            .'Your expenses and receipts are all saved — just send a message to start a new one 👋'
                        );
                    }
                } catch (\Throwable $e) {
                    Log::warning('SweepInactiveSessions: notify failed', [
                        'session_id' => $session->id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            // Clear conversation context. This is the only persistent store of
            // agent context; users, orgs and expenses are untouched.
            AgentConversation::where('user_id', $session->user_id)->delete();

            $closed++;
        }

        Log::info('SweepInactiveSessions: completed', ['closed' => $closed]);
        $this->info("Closed {$closed} inactive session(s).");

        return self::SUCCESS;
    }
}
