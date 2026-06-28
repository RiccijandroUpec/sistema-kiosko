<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:verify-payments')->everyMinute()->withoutOverlapping();

Schedule::command('app:check-system-health')->everyFiveMinutes()->withoutOverlapping();

Schedule::command('clean:old-data')->daily();
