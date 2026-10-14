<?php

declare(strict_types=1);

namespace App\Filament\Resources\Collections\Schemas;

use App\Models\Collection;
use App\Support\SlugGenerator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dettagli Collezione')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Nome Collezione')
                                ->placeholder('es. Apertura Attività o Golfing Kit')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                                    $slug = SlugGenerator::unique(Collection::class, $state, $record);
                                    $set('slug', $slug);
                                }),

                            TextInput::make('slug')
                                ->label('Slug')
                                ->required()
                                ->maxLength(255)
                                ->unique(Collection::class, 'slug', ignoreRecord: true),
                        ]),

                        Textarea::make('description')
                            ->label('Descrizione Bundle')
                            ->helperText('Descrivi cosa include questo bundle o kit e a chi si rivolge.')
                            ->rows(4)
                            ->columnSpanFull(),

                        Grid::make(3)->schema([
                            Toggle::make('is_featured')
                                ->label('In Evidenza (Hero / Banner Homepage)')
                                ->helperText('Mostra questa collezione nella homepage (hero carousel e sezione bundle).')
                                ->default(false),

                            Toggle::make('is_active')
                                ->label('Attivo nel Catalogo')
                                ->default(true),

                            TextInput::make('sort_order')
                                ->label('Ordinamento')
                                ->numeric()
                                ->default(0),
                        ]),
                    ]),

                Section::make('Prodotti Inclusi nel Bundle')
                    ->description('Seleziona i prodotti che compongono questa collezione / bundle.')
                    ->schema([
                        Select::make('products')
                            ->label('Prodotti')
                            ->relationship('products', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                    ]),

                Section::make('Immagini & Banner')
                    ->description('Carica banner e immagini per la promozione della collezione.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('banner')
                            ->label('Banner Principale / Hero')
                            ->collection('banner')
                            ->image()
                            ->imagePreviewHeight('240')
                            ->maxFiles(1)
                            ->helperText('Formato orizzontale consigliato (1920x1080 o 1200x600) per hero e banner.')
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('images')
                            ->label('Immagini Secondarie / Gallery')
                            ->collection('images')
                            ->multiple()
                            ->image()
                            ->imagePreviewHeight('160')
                            ->maxFiles(6)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
