<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use App\Models\Product;
use App\Support\SlugGenerator;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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
                    ->label('Descrizione')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->label('Prezzo')
                    ->required()
                    ->numeric()
                    ->prefix('€'),
                    Select::make('is_featured')
                    ->label('Prodotto in Evidenza')
                    ->options([
                        true => 'Sì',
                        false => 'No',
                    ])
                    ->default(false)
                    ->required(),
                Select::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name') // Load categories
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nome Categoria')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?Model $record) {
                                $slug = SlugGenerator::unique(Category::class, $state, $record);
                                $set('slug', $slug);
                            })
                            ->required(),
                        TextInput::make('slug')
                            ->required(),
                        Textarea::make('description')
                            ->label('Descrizione'),
                        Select::make('parent_id')
                            ->label('Categoria di appartenenza')
                            ->relationship('parent', 'name') // Load parent categories
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ]) // Form for adding new category
                    ->createOptionAction(function (Action $action) {
                        $action->modalHeading('Create Category');
                    }),
                Repeater::make('images')
                    ->label('Immagini')
                    ->relationship()
                    ->grid()
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Immagine')
                            ->image()
                            ->disk('public')
                            ->directory('product_images')
                            ->visibility('public')
                            ->required(fn () => ! app()->runningUnitTests()),
                        TextInput::make('image_description')
                            ->label('Descrizione dell\'immagine')
                            ->nullable(),
                    ])
                    ->orderColumn('order_by')
                    ->columnSpanFull(),
            ]);
    }
}
