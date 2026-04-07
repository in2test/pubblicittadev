<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Support\SlugGenerator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                        $slug = SlugGenerator::unique(Category::class, $state, $record);
                        $set('slug', $slug);
                    })
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('parent_id')
                    ->label('Categoria di appartenenza')
                    ->relationship(
                        name: 'parent',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query, ?Model $record) => $query
                            ->whereNull('parent_id')
                            ->when($record, fn ($query) => $query->where('id', '!=', $record->id)),
                    )
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Textarea::make('description')
                    ->label('Descrizione')
                    ->columnSpanFull(),
                // Image Media Library
                SpatieMediaLibraryFileUpload::make('images')
                    ->label('Immagine')
                    ->collection('images')
                    ->image()
                    ->imagePreviewHeight('300')
                    ->maxFiles(1)
                    ->customProperties(function (): array {
                        return [
                            'alt' => 'descrizione',
                        ];
                    })
                    ->columnSpanFull(),

            ]);
    }
}
