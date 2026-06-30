<?php

use App\Jobs\ClearAgentSessions;
use App\Jobs\ExpirePendingConfirmations;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ClearAgentSessions)->everyTenMinutes();
Schedule::job(new ExpirePendingConfirmations)->hourly();
