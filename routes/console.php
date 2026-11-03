<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('env:prod', function () {
    $key = env('LARAVEL_ENV_ENCRYPTION_KEY');

    $params = [
        '--env' => 'production',
        '--force' => true,
    ];

    if (! empty($key)) {
        $params['--key'] = $key;
    }

    $this->call('env:encrypt', $params);
})->purpose('Criptografa o .env.production usando LARAVEL_ENV_ENCRYPTION_KEY do .env local');
