<?php

use App\Services\Domains\DomainRenewalService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('domains:process-auto-renewals {--dry-run}', function () {
    $summary = app(DomainRenewalService::class)->processDueAutoRenewals((bool) $this->option('dry-run'));

    $this->table(
        ['scanned', 'due', 'created', 'existing', 'renewed', 'awaiting_payment', 'failed'],
        [[
            $summary['scanned'],
            $summary['due'],
            $summary['created'],
            $summary['existing'],
            $summary['renewed'],
            $summary['awaiting_payment'],
            $summary['failed'],
        ]]
    );
})->purpose('Create and process due domain auto-renewals before their renewal date.');

Schedule::command('domains:process-auto-renewals')
    ->dailyAt('02:00')
    ->withoutOverlapping();
