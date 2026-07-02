<?php

use App\Jobs\ClearAgentSessions;
use App\Jobs\ExpirePendingConfirmations;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// DISABLED: superseded by finvixy:sweep-inactive-sessions. This legacy job
// messaged "conversation cleared" off a loose AiUsageLog window (10–20 min ago)
// without actually clearing anything, and would double-message users alongside
// the sweeper. Re-enable only if the sweeper is abandoned.
// Schedule::job(new ClearAgentSessions)->everyTenMinutes();

Schedule::job(new ExpirePendingConfirmations)->hourly();

// Inactivity sweeper — every 5 minutes so a 10-minute timeout is honoured
// within ±5 min. Inert until SESSION_SWEEPER_ENABLED=true (owner confirms).
Schedule::command('finvixy:sweep-inactive-sessions')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->when(fn () => (bool) config('services.agentcore.sweeper_enabled', false));

// Finvixy → Enclivix hourly usage rollup. Inert until the owner sets
// FINVIXY_STATS_URL and keeps STATS_PUSH_ENABLED=true — the guard means it
// won't send anything until that configuration is in place (confirm before enabling).
Schedule::command('finvixy:push-stats')
    ->hourly()
    ->when(fn () => config('services.finvixy_stats.enabled') && config('services.finvixy_stats.url'));
