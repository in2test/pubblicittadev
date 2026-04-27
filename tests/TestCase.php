<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Override;
use Tests\Support\FortifyTestHelpers;

abstract class TestCase extends BaseTestCase
{
    use FortifyTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    // Bootstrapping the Laravel application for tests
    #[Override]
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
