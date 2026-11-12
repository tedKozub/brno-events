<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentPlaceResource\Pages;
use App\Models\StudentPlace;
use App\Models\StudentPlaceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentPlaceResource extends Resource
{
    protected static ?string $model = StudentPlace::class;

    protected static ?string $navigationGroup = 'Studentská místa';
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $modelLabel = 'Místo v mapě';
    protected static ?string $pluralModelLabel = 'Místa v mapě';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Základní informace')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Název místa')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('student_place_category_id')
                            ->label('Kategorie')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('address')
                            ->label('Adresa / Umístění v Brně')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('např. Kounicova 65a, Brno'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('lat')
                                    ->label('Zeměpisná šířka (Latitude)')
                                    ->required()
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->placeholder('49.2087000')
                                    ->helperText('Formát: 49.XXXXXX'),

                                Forms\Components\TextInput::make('lng')
                                    ->label('Zeměpisná délka (Longitude)')
                                    ->required()
                                    ->numeric()
                                    ->step(0.0000001)
                                    ->placeholder('16.5947000')
                                    ->helperText('Formát: 16.XXXXXX'),
                            ]),
                    ]),

                Forms\Components\Section::make('Detaily & Štítky')
                    ->schema([
                        Forms\Components\TextInput::make('highlight')
                            ->label('Hlavní přednost (highlight)')
                            ->maxLength(255)
                            ->placeholder('např. Nejoblíbenější studovna v Brně'),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Vlastnosti a štítky')
                            ->placeholder('Přidat štítek (stiskni Enter)')
                            ->suggestions([
                                'Eduroam',
                                'Zásuvky',
                                'Tiché zóny',
                                'Kavárna',
                                'Klimatizace',
                                'Výběrová káva',
                                'Laptopy',
                                'Zahrádka',
                                'Výhled',
                                'Piknik',
                                'Centrum',
                                'Levné pivo',
                                'Tráva',
                                'Wi-Fi',
                                'Snídaně',
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Popis místa')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull()
                            ->placeholder('Popiš, proč by sem student měl jít a co zde najde...'),
                    ]),

                Forms\Components\Section::make('Odkazy & Viditelnost')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('website_url')
                                    ->label('Webová stránka')
                                    ->url()
                                    ->placeholder('https://...'),

                                Forms\Components\TextInput::make('instagram_handle')
                                    ->label('Instagram')
                                    ->placeholder('@profil'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sort_order')
                                    ->label('Pořadí zobrazení')
                                    ->numeric()
                                    ->default(0),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktivní v mapě')
                                    ->default(true)
                                    ->helperText('Neaktivní místa jsou skryta z mapy i seznamu.'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Název')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategorie')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Adresa')
                    ->limit(25)
                    ->searchable(),

                Tables\Columns\TextColumn::make('highlight')
                    ->label('Hlavní přednost')
                    ->limit(25),

                Tables\Columns\TextColumn::make('tags')
                    ->label('Štítky')
                    ->badge()
                    ->separator(',')
                    ->limitList(3),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Pořadí')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktivní'),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('student_place_category_id')
                    ->label('Kategorie')
                    ->relationship('category', 'name'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Stav aktivity'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentPlaces::route('/'),
            'create' => Pages\CreateStudentPlace::route('/create'),
            'edit' => Pages\EditStudentPlace::route('/{record}/edit'),
        ];
    }
}
