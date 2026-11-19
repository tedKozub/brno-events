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
                Forms\Components\Section::make('Základní údaje o místě')
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

                        Forms\Components\Select::make('place_type')
                            ->label('Typ místa')
                            ->options([
                                'faculty' => 'Univerzita / Fakulta',
                                'club' => 'Hudební klub / Bar',
                                'dorm' => 'Koleje / Menza',
                                'library' => 'Knihovna / Studovna',
                                'cafe' => 'Kavárna / Kulturní prostor',
                                'other' => 'Ostatní',
                            ])
                            ->default('other'),

                        Forms\Components\Toggle::make('is_verified')
                            ->label('Ověřeno administrátorem')
                            ->default(true),

                        Forms\Components\TextInput::make('website_url')
                            ->label('Webové stránky')
                            ->url(),
                    ])->columns(2),

                Forms\Components\Section::make('Geolokace (GPS & OSM)')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('GPS Šířka (Latitude)')
                            ->numeric()
                            ->placeholder('49.2096'),

                        Forms\Components\TextInput::make('longitude')
                            ->label('GPS Délka (Longitude)')
                            ->numeric()
                            ->placeholder('16.6033'),

                        Forms\Components\TextInput::make('osm_place_id')
                            ->label('OpenStreetMap Place ID')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Automaticky přiřazeno geocoderem'),
                    ])->columns(3),

                Forms\Components\Section::make('Textové aliasy pro automatické párování')
                    ->description('Scraper tato synonyma v textu automaticky spáruje s tímto místem')
                    ->schema([
                        Forms\Components\Repeater::make('aliases')
                            ->relationship('aliases')
                            ->schema([
                                Forms\Components\TextInput::make('alias')
                                    ->label('Alias / Klíčové slovo')
                                    ->required()
                                    ->placeholder('např. fit vut, božetěchova'),
                            ])
                            ->columns(1)
                            ->itemLabel(fn (array $state): ?string => $state['alias'] ?? null)
                            ->defaultItems(0),
                    ]),

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
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Ověřeno')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Název')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Adresa')
                    ->searchable(),

                Tables\Columns\TextColumn::make('place_type')
                    ->label('Typ')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'faculty' => 'info',
                        'club' => 'warning',
                        'dorm' => 'purple',
                        'library' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('events_count')
                    ->label('Počet akcí')
                    ->counts('events')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('aliases_count')
                    ->label('Aliasy')
                    ->counts('aliases')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('website_url')
                    ->label('Web')
                    ->url(fn ($record) => $record->website_url, true)
                    ->limit(20),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Stav ověření')
                    ->trueLabel('Pouze ověřená místa')
                    ->falseLabel('Čeká na kontrolu (nově objevená)'),

                Tables\Filters\SelectFilter::make('place_type')
                    ->label('Typ místa')
                    ->options([
                        'faculty' => 'Fakulty',
                        'club' => 'Kluby',
                        'dorm' => 'Koleje',
                        'library' => 'Knihovny',
                        'cafe' => 'Kavárny',
                        'other' => 'Ostatní',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->label('Ověřit')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => !$record->is_verified)
                    ->action(fn ($record) => $record->update(['is_verified' => true])),
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
