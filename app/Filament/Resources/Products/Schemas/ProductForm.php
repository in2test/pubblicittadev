<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Actions\Action;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Category;
use App\Support\SlugGenerator;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
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
                FileUpload::make('attachments')
                    ->multiple()
                    ->panelLayout('grid')
                    ->reorderable()

                ]);
    }
}
