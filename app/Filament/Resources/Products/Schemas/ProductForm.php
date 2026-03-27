<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\Product;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome Prodotto')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                        $slug = SlugGenerator::unique(Product::class, $state, $record);
                        $set('slug', $slug);
                    })
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('€'),
                Select::make('category_id')
                    ->relationship('category', 'name') // Load categories
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                                $slug = SlugGenerator::unique(Category::class, $state, $record);
                                $set('slug', $slug);
                            })
                            ->required(),
                        TextInput::make('slug')
                            ->required(),
                        Textarea::make('description'),
                        Select::make('parent_id')
                            ->label('Parent Category')
                            ->relationship('parent', 'name') // Load parent categories
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]) // Form for adding new category
                    ->createOptionAction(function (Action $action) {
                        $action->modalHeading('Create Category');
                    }),
                FileUpload::make('image')
                    ->image()    
                    ->multiple()
                    ->panelLayout('grid')
                    ->visibility('private')
                    ->disk('local')  // storage/app
                    ->directory('product_images')  // storage/app/uploaded-csv
                    ->visibility('public')
                    ->required()
                    ->moveFiles() //move from temp folder, instead of copying. helps to save disk space.
                    ->reorderable(),

            ]);
    }
}
