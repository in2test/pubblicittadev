<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterSubscriptions;

use App\Filament\Resources\NewsletterSubscriptions\Pages\ManageNewsletterSubscriptions;
use App\Models\NewsletterSubscription;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class NewsletterSubscriptionResource extends Resource
{
    protected static ?string $model = NewsletterSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAtSymbol;

    protected static string|UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $modelLabel = 'Iscrizione Newsletter';

    protected static ?string $pluralModelLabel = 'Iscrizioni Newsletter';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }

    #[Override]
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ManageNewsletterSubscriptions::route('/'),
        ];
    }
}
