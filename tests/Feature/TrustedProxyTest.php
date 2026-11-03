<?php

test('it generates secure asset URLs behind the local reverse proxy', function () {
    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'pubblicitta24.test',
            'HTTP_X_FORWARDED_HOST' => 'pubblicitta24.test',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])
        ->get('http://pubblicitta24.test/');

    $response
        ->assertSuccessful()
        ->assertSee('src="https://pubblicitta24.test/livewire-', false);
});
