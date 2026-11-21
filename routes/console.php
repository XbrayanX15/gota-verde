<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Arquitectura física: cron ejecuta `php artisan schedule:run` cada minuto.
// Cierre diario: rachas, metas e invitaciones vencidas (CU-19).
Schedule::command('gotaverde:cierre-diario')->dailyAt('00:05')->withoutOverlapping();
