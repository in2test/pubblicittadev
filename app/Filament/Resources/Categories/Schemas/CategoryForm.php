<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use App\Support\SlugGenerator;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
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
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                        $slug = SlugGenerator::unique(Category::class, $state, $record);
                        $set('slug', $slug);
                    })
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Select::make('parent_id')
                    ->label('Parent Category')
                    ->relationship('parent', 'name') // Load parent categories
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Repeater::make('image')
                ->maxItems(1)
                    ->relationship('image')
                    ->label('Immagine')
                    ->schema([
                FileUpload::make('image_path')
                    ->image()
                    ->disk('public')
                    ->directory('category_images')
                    ->visibility('public')
                    ->required(),
                TextInput::make('image_description')
                    ->nullable(),
                ])
                    
            ]);
    }
}
