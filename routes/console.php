<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Plugins\Analytics\Jobs\AggregateAnalyticsJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Aggregate previous day's analytics every night at 01:00
Schedule::job(new AggregateAnalyticsJob())->dailyAt('01:00');
