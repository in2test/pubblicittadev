<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Support\SlugGenerator;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
                    ->relationship('parent', 'name') // Load parent categories
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Textarea::make('description')
                    ->label('Descrizione')
                    ->columnSpanFull(),
                Repeater::make('image')
                    ->label('Immagine')
                    ->maxItems(1)
                    ->relationship('image')
                    ->label('Immagine')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Immagine')
                            ->image()
                            ->disk('public')
                            ->directory('category_images')
                            ->visibility('public')
                            ->required(fn () => ! app()->runningUnitTests()),
                        TextInput::make('image_description')
                            ->label('Descrizione dell\'immagine')
                            ->nullable(),
                    ]),

            ]);
    }
}
