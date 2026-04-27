<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use App\Filament\Resources\CategoryQuantityDiscounts\CategoryQuantityDiscountResource;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register Filament resources so they appear in the admin menu
        if (class_exists(Filament::class)) {
            Filament::registerResources([
                CategoryQuantityDiscountResource::class,
            ]);
        }
    }

    public function register(): void
    {
        // no bindings required
    }
}
