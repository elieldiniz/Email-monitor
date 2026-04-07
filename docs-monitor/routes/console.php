<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduler: verifica novos e-mails a cada 15 minutos
Schedule::command('email:poll')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();
