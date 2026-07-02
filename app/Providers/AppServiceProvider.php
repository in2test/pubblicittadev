<?php

declare(strict_types=1);

namespace App\Providers;

use App\Filament\Resources\CategoryQuantityDiscounts\CategoryQuantityDiscountResource;
use App\Models\NewsletterSubscription;
use App\Models\PricingTier;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Observers\MediaObserver;
use App\Observers\NewsletterSubscriptionObserver;
use App\Observers\PricingTierObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductSkuObserver;
use App\Observers\UserObserver;
use App\Services\NwgApiClient;
use App\Services\QuantityDiscountService;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Override;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->singleton(NwgApiClient::class);
        $this->app->singleton(QuantityDiscountService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ini_set('memory_limit', '4G');

        $this->configureDefaults();

        // Prevent lazy loading to catch potential N+1 query issues.
        Model::preventLazyLoading();

        // Register observer for Spatie Media deletion to clean up orphaned Image records
        Media::observe(MediaObserver::class);

        // Register observers for caching prices
        Product::observe(ProductObserver::class);
        PricingTier::observe(PricingTierObserver::class);
        ProductSku::observe(ProductSkuObserver::class);

        // Register observers for admin notifications
        User::observe(UserObserver::class);
        NewsletterSubscription::observe(NewsletterSubscriptionObserver::class);

        // Register Filament resources (global menu)
        if (class_exists(Filament::class)) {
            Filament::registerResources([
                CategoryQuantityDiscountResource::class,
            ]);
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols(),
        );
    }
}
