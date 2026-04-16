<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process high-priority chat jobs on schedule.
Schedule::command(
    'queue:work --queue=ai-chat --stop-when-empty --tries=1 --timeout=90 --sleep=1 --max-jobs=200'
)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Process buffered sync jobs (Redis -> DB) on schedule.
Schedule::command(
    'queue:work --queue=ai-sync --stop-when-empty --tries=1 --timeout=90 --sleep=1 --max-jobs=200'
)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Process default queued jobs on schedule.
Schedule::command(
    'queue:work --queue=default --stop-when-empty --tries=1 --timeout=90 --sleep=1 --max-jobs=200'
)
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Keep Grok model catalog in sync with provider availability.
Schedule::command('ai:validate-grok-models')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->runInBackground();
