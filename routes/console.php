<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\GerarRelatoriosDiariosJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job((new GerarRelatoriosDiariosJob())->onConnection('sync'))
    ->dailyAt(sprintf('%02d:%02d', (int) env('REPORT_CLOSE_HOUR', 19), (int) env('REPORT_CLOSE_MINUTE', 0)));
