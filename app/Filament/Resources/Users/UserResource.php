<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Override;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Impostazioni';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function getNavigationGroup(): string
    {
        return 'Impostazioni';
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return 'Utenti';
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return 'Utente';
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return 'Utenti';
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignorable: fn ($record) => $record),
                Select::make('role')
                    ->options([
                        User::ROLE_CLIENT => 'Cliente',
                        User::ROLE_ADMIN => 'Admin',
                    ])
                    ->required(),
                Select::make('is_active')
                    ->options([
                        true => 'Attivo',
                        false => 'Disattivato',
                    ])
                    ->required(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge()
                    ->colors([
                        'success' => User::ROLE_ADMIN,
                        'warning' => User::ROLE_CLIENT,
                    ]),
                TextColumn::make('is_active')
                    ->badge()
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->formatStateUsing(fn (bool $state) => $state ? 'Attivo' : 'Disattivato'),
            ])
            ->filters([
                Filter::make('admins')
                    ->query(fn ($query) => $query->where('role', User::ROLE_ADMIN))
                    ->label('Solo Admin'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
