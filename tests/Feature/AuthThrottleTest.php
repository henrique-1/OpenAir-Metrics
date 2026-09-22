<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('login attempts are throttled after 5 consecutive failures', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'admin@openair.com',
            'password' => 'wrong-pass',
        ])->assertStatus(302);
    }

    $response = $this->post('/login', [
        'email' => 'admin@openair.com',
        'password' => 'wrong-pass',
    ]);

    $response->assertStatus(429);
});
