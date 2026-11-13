<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentPlaceSuggestionResource\Pages;
use App\Models\StudentPlaceCategory;
use App\Models\StudentPlaceSuggestion;
use App\Services\GeocodingService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentPlaceSuggestionResource extends Resource
{
    protected static ?string $model = StudentPlaceSuggestion::class;

    protected static ?string $navigationGroup = 'Studentská místa';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $modelLabel = 'Návrh místa';
    protected static ?string $pluralModelLabel = 'Návrhy od studentů';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informace o navrženém místě')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Název místa')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('student_place_category_id')
                            ->label('Kategorie')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('address')
                            ->label('Adresa / Lokalita v Brně')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('highlight')
                            ->label('Hlavní přednost (highlight)')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Popis od studenta (proč doporučuje)')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Štítky')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Odkazy & Odesílatel')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('website_url')
                                    ->label('Web')
                                    ->url(),

                                Forms\Components\TextInput::make('instagram_handle')
                                    ->label('Instagram'),

                                Forms\Components\TextInput::make('submitter_name')
                                    ->label('Jméno odesílatele'),

                                Forms\Components\TextInput::make('submitter_email')
                                    ->label('E-mail odesílatele')
                                    ->email(),
                            ]),
                    ]),

                Forms\Components\Section::make('Stav moderace')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Stav')
                                    ->options([
                                        'pending' => 'Ke schválení',
                                        'approved' => 'Schváleno',
                                        'rejected' => 'Zamítnuto',
                                    ])
                                    ->required(),

                                Forms\Components\Select::make('approved_student_place_id')
                                    ->label('Vytvořené místo v mapě')
                                    ->relationship('approvedPlace', 'name')
                                    ->disabled(),
                            ]),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Poznámka administrátora')
                            ->rows(2)
                            ->columnSpanFull(),
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
                    ->color('primary')
                    ->default('Bez kategorie')
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Adresa')
                    ->limit(28)
                    ->searchable(),

                Tables\Columns\TextColumn::make('highlight')
                    ->label('Přednost')
                    ->limit(20),

                Tables\Columns\TextColumn::make('submitter_name')
                    ->label('Od studenta')
                    ->state(fn (StudentPlaceSuggestion $record) => $record->submitter_name ?: ($record->submitter_email ?: 'Anonym'))
                    ->limit(20),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stav')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Schváleno',
                        'pending' => 'Ke schválení',
                        'rejected' => 'Zamítnuto',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Navrženo')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stav')
                    ->options([
                        'pending' => 'Ke schválení',
                        'approved' => 'Schváleno',
                        'rejected' => 'Zamítnuto',
                    ]),

                Tables\Filters\SelectFilter::make('student_place_category_id')
                    ->label('Kategorie')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Schválit a publikovat')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (StudentPlaceSuggestion $record) => $record->status === 'pending')
                    ->form(function (StudentPlaceSuggestion $record) {
                        $coords = GeocodingService::geocodeAddress($record->address);
                        return [
                            Forms\Components\TextInput::make('name')
                                ->label('Název místa')
                                ->default($record->name)
                                ->required(),

                            Forms\Components\Select::make('student_place_category_id')
                                ->label('Kategorie')
                                ->options(StudentPlaceCategory::where('is_active', true)->pluck('name', 'id'))
                                ->default($record->student_place_category_id)
                                ->required(),

                            Forms\Components\TextInput::make('address')
                                ->label('Adresa v Brně')
                                ->default($record->address)
                                ->required(),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('lat')
                                        ->label('Zeměpisná šířka (Latitude)')
                                        ->numeric()
                                        ->default($coords['lat'])
                                        ->required(),

                                    Forms\Components\TextInput::make('lng')
                                        ->label('Zeměpisná délka (Longitude)')
                                        ->numeric()
                                        ->default($coords['lng'])
                                        ->required(),
                                ]),

                            Forms\Components\TextInput::make('highlight')
                                ->label('Hlavní přednost')
                                ->default($record->highlight),

                            Forms\Components\Textarea::make('description')
                                ->label('Popis pro studenty')
                                ->default($record->description)
                                ->required()
                                ->rows(3),
                        ];
                    })
                    ->action(function (StudentPlaceSuggestion $record, array $data) {
                        $place = $record->approveAndCreatePlace($data);

                        Notification::make()
                            ->title("Místo {$place->name} bylo schváleno a přidáno do mapy.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Zamítnout')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Zamítnout návrh na studentské místo?')
                    ->modalDescription('Návrh bude přesunut do záložky "Zamítnuté" a v mapě se nezobrazí.')
                    ->visible(fn (StudentPlaceSuggestion $record) => $record->status === 'pending')
                    ->action(function (StudentPlaceSuggestion $record) {
                        $record->update(['status' => 'rejected']);

                        Notification::make()
                            ->title('Návrh byl zamítnut.')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\Action::make('view_on_map')
                    ->label('V mapě')
                    ->icon('heroicon-o-map-pin')
                    ->color('gray')
                    ->visible(fn (StudentPlaceSuggestion $record) => $record->status === 'approved')
                    ->url(fn () => url('/mista'))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_bulk')
                        ->label('Hromadně schválit vybrané')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function (StudentPlaceSuggestion $s) {
                                if ($s->status === 'pending') {
                                    $s->approveAndCreatePlace();
                                }
                            });

                            Notification::make()
                                ->title('Vybrané návrhy byly schváleny a přidány do mapy.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('reject_bulk')
                        ->label('Hromadně zamítnout vybrané')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(function (StudentPlaceSuggestion $s) {
                                $s->update(['status' => 'rejected']);
                            });

                            Notification::make()
                                ->title('Vybrané návrhy byly zamítnuty.')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentPlaceSuggestions::route('/'),
            'edit' => Pages\EditStudentPlaceSuggestion::route('/{record}/edit'),
        ];
    }
}
