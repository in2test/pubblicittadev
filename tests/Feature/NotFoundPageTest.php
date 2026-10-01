<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the catalog search on a not found page', function () {
    $response = $this->get('/missing-catalog-page');

    $response->assertNotFound()
        ->assertSeeText('404 Pagina non trovata')
        ->assertSee('name="q"', false)
        ->assertSee(route('search'), false);
});
