<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('marketplace:status', function () {
    $this->info('ChachaPrime application routes and console are loaded.');
})->purpose('Check the ChachaPrime marketplace application');
