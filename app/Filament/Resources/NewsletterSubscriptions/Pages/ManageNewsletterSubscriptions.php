<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterSubscriptions\Pages;

use App\Filament\Resources\NewsletterSubscriptions\NewsletterSubscriptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Override;

class ManageNewsletterSubscriptions extends ManageRecords
{
    protected static string $resource = NewsletterSubscriptionResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
