<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $modelLabel = 'Kategorie';
    protected static ?string $pluralModelLabel = 'Kategorie akcí';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Název kategorie')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('icon')
                    ->label('Název ikony (Lucide)')
                    ->placeholder('beer, trophy, help-circle...'),

                Forms\Components\Select::make('color')
                    ->label('Barevný styl')
                    ->options([
                        'primary' => 'Oranžová (Primary)',
                        'accent' => 'Zlatá / Žlutá (Accent)',
                        'info' => 'Modrá (Info)',
                        'success' => 'Zelená (Success)',
                        'warning' => 'Jantarová (Warning)',
                        'secondary' => 'Břidlicová (Secondary)',
                        'neutral' => 'Neutrální šedá',
                    ])
                    ->default('primary'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Pořadí zobrazení')
                    ->numeric()
                    ->default(0),

                Forms\Components\Textarea::make('description')
                    ->label('Popis kategorie')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Název')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('icon')
                    ->label('Ikona'),

                Tables\Columns\TextColumn::make('color')
                    ->label('Barva')
                    ->badge(),

                Tables\Columns\TextColumn::make('events_count')
                    ->label('Počet akcí')
                    ->counts('events')
                    ->badge(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Pořadí')
                    ->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
