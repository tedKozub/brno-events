<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VenueResource\Pages;
use App\Models\Venue;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VenueResource extends Resource
{
    protected static ?string $model = Venue::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $modelLabel = 'Místo v Brně';
    protected static ?string $pluralModelLabel = 'Místa & Kluby v Brně';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Název místa')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('address')
                    ->label('Ulice a číslo')
                    ->placeholder('např. Štefánikova 24'),

                Forms\Components\TextInput::make('city')
                    ->label('Město')
                    ->default('Brno')
                    ->required(),

                Forms\Components\TextInput::make('website_url')
                    ->label('Webové stránky')
                    ->url(),

                Forms\Components\TextInput::make('latitude')
                    ->label('GPS Šířka (Latitude)')
                    ->numeric()
                    ->placeholder('49.2096'),

                Forms\Components\TextInput::make('longitude')
                    ->label('GPS Délka (Longitude)')
                    ->numeric()
                    ->placeholder('16.6033'),

                Forms\Components\Textarea::make('description')
                    ->label('Popis místa')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Název')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Adresa')
                    ->searchable(),

                Tables\Columns\TextColumn::make('events_count')
                    ->label('Počet akcí')
                    ->counts('events')
                    ->badge(),

                Tables\Columns\TextColumn::make('website_url')
                    ->label('Web')
                    ->url(fn ($record) => $record->website_url, true)
                    ->limit(25),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenues::route('/'),
            'create' => Pages\CreateVenue::route('/create'),
            'edit' => Pages\EditVenue::route('/{record}/edit'),
        ];
    }
}
