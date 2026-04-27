<?php

declare(strict_types=1);

namespace Tests\Support;

use Laravel\Fortify\Features;

trait FortifyTestHelpers
{
    protected function skipUnlessFortifyFeature($feature): void
    {
        if (! class_exists(Features::class)) {
            $this->markTestSkipped('Fortify testing helpers not available.');
        }
        // Real feature checks can be added here if required later.
    }
}
