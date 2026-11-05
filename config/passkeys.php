<?php

use App\Actions\FindPasskeyToAuthenticateAction;
use App\Models\User;
use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Spatie\LaravelPasskeys\Models\Passkey;

return [
    /*
    |--------------------------------------------------------------------------
    | Spatie Laravel Passkeys Configuration
    |--------------------------------------------------------------------------
    */
    'redirect_to_after_login' => '/dashboard',

    'actions' => [
        'generate_passkey_register_options' => GeneratePasskeyRegisterOptionsAction::class,
        'store_passkey' => StorePasskeyAction::class,
        'generate_passkey_authentication_options' => GeneratePasskeyAuthenticationOptionsAction::class,
        'find_passkey' => FindPasskeyToAuthenticateAction::class,
        'configure_ceremony_step_manager_factory' => ConfigureCeremonyStepManagerFactoryAction::class,
    ],

    'relying_party' => [
        'name' => env('APP_NAME', 'OpenAir Metrics'),
        'id' => parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost',
        'icon' => null,
    ],

    'models' => [
        'passkey' => Passkey::class,
        'authenticatable' => User::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared / Fortify Passkeys Compatibility
    |--------------------------------------------------------------------------
    */
    'relying_party_id' => parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost',
    'allowed_origins' => [
        env('APP_URL', 'http://localhost'),
    ],
    'user_handle_secret' => env('PASSKEYS_USER_HANDLE_SECRET', env('APP_KEY')),
    'timeout' => 60000,
    'guard' => 'web',
    'middleware' => ['web'],
    'management_middleware' => ['auth', 'can:manage-passkeys'],
];
