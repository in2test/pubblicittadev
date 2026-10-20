<?php

declare(strict_types=1);

namespace App\Filament\Resources\Brands\Schemas;

use App\Models\Brand;
use App\Support\SlugGenerator;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Schema;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Brand Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                                $slug = SlugGenerator::unique(Brand::class, $state, $record);
                                $set('slug', $slug);
                            })
                            ->required(),
                        TextInput::make('slug')
                            ->required(),
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo')
                            ->collection('logos')
                            ->image()
                            ->imagePreviewHeight('300')
                            ->maxFiles(1)
                            ->columnSpanFull(),
                    ])
            ]);
    }
}
