<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentPlaceCategoryResource\Pages;
use App\Models\StudentPlaceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class StudentPlaceCategoryResource extends Resource
{
    protected static ?string $model = StudentPlaceCategory::class;

    protected static ?string $navigationGroup = 'Studentská místa';
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $modelLabel = 'Kategorie míst';
    protected static ?string $pluralModelLabel = 'Kategorie míst';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Název kategorie')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Forms\Set $set, $get) {
                        if (empty($get('slug'))) {
                            $set('slug', Str::slug($state));
                        }
                        if (empty($get('short_name'))) {
                            $set('short_name', $state);
                        }
                    }),

                Forms\Components\TextInput::make('short_name')
                    ->label('Zkrácený název (pro odznaky)')
                    ->required()
                    ->maxLength(50)
                    ->helperText('Zkrácená podoba pro štítky a mobilní rozhraní (např. "Kavárna", "Studovna").'),

                Forms\Components\TextInput::make('slug')
                    ->label('URL identifikátor (Slug)')
                    ->required()
                    ->unique(StudentPlaceCategory::class, 'slug', ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\ColorPicker::make('color')
                    ->label('Barva špendlíku v mapě')
                    ->default('#6366f1')
                    ->required(),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Pořadí zobrazení')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktivní')
                    ->default(true)
                    ->helperText('Neaktivní kategorie se nezobrazí na veřejné mapě.'),
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

                Tables\Columns\TextColumn::make('short_name')
                    ->label('Zkráceně'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->badge(),

                Tables\Columns\ColorColumn::make('color')
                    ->label('Barva'),

                Tables\Columns\TextColumn::make('places_count')
                    ->label('Počet míst')
                    ->counts('places')
                    ->badge(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Pořadí')
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktivní'),
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
            'index' => Pages\ListStudentPlaceCategories::route('/'),
            'create' => Pages\CreateStudentPlaceCategory::route('/create'),
            'edit' => Pages\EditStudentPlaceCategory::route('/{record}/edit'),
        ];
    }
}
