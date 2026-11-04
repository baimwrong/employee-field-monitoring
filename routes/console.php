<?php

use App\Models\ActivityLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly maintenance: Automatically prune expired activity logs (older than 90 days)
Schedule::command('model:prune', [
    '--model' => [ActivityLog::class],
])->dailyAt('02:00');
