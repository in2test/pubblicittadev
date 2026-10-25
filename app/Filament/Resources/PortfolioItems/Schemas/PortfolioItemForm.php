<?php

declare(strict_types=1);

namespace App\Filament\Resources\PortfolioItems\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PortfolioItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                RichEditor::make('description')
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('image')
                    ->collection('portfolio_images')
                    ->disk('public')
                    ->conversionsDisk('public')
                    ->label('Cover Image')
                    ->image()
                    ->imageEditor()
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('gallery')
                    ->collection('images')
                    ->disk('public')
                    ->conversionsDisk('public')
                    ->label('Gallery')
                    ->multiple()
                    ->reorderable()
                    ->image()
                    ->imagePreviewHeight('150')
                    ->panelLayout('grid')
                    ->columnSpanFull(),
                TextInput::make('link'),
                Toggle::make('is_featured')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
