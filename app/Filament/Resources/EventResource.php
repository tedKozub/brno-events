<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $modelLabel = 'Akce';
    protected static ?string $pluralModelLabel = 'Studentské Akce';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending_review')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Multi-tenancy pro spolky: zástupce spolku vidí jen své akce
        if ($user && $user->isClubAdmin() && $user->organization_id) {
            $query->where('organization_id', $user->organization_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $user = auth()->user();
        $isClubAdmin = $user && $user->isClubAdmin();

        return $form
            ->schema([
                Forms\Components\Placeholder::make('data_quality_alert')
                    ->label('')
                    ->visible(fn (?Event $record) => $record && !empty($record->missing_fields))
                    ->content(function (?Event $record) {
                        if (!$record || empty($record->missing_fields)) {
                            return null;
                        }
                        $items = implode(' • ', array_values($record->missing_fields));
                        return new \Illuminate\Support\HtmlString("
                            <div style='padding: 1rem; border-radius: 0.75rem; border: 1px solid rgba(245, 158, 11, 0.4); background: rgba(245, 158, 11, 0.1); margin-bottom: 0.5rem;'>
                                <div style='font-weight: 600; color: #f59e0b; font-size: 0.95rem; margin-bottom: 0.25rem;'>
                                    ⚠️ Vyžaduje pozornost před schválením – chybějící data:
                                </div>
                                <div style='font-size: 0.875rem; color: #fbbf24; line-height: 1.4;'>
                                    {$items}
                                </div>
                                <div style='font-size: 0.75rem; color: #d97706; margin-top: 0.25rem;'>
                                    Doplňte chybějící pole (např. plakát nebo místo), aby se akce zobrazovala správně na mapě a v kalendáři.
                                </div>
                            </div>
                        ");
                    })
                    ->columnSpanFull(),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Základní informace')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Název akce')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            Forms\Components\Select::make('category_id')
                                ->label('Kategorie')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\Select::make('organization_id')
                                ->label('Pořádající spolek')
                                ->relationship('organization', 'name')
                                ->searchable()
                                ->preload()
                                ->default($isClubAdmin ? $user->organization_id : null)
                                ->disabled($isClubAdmin)
                                ->dehydrated(true)
                                ->required(!$isClubAdmin),

                            Forms\Components\Select::make('venue_id')
                                ->label('Místo konání (z databáze)')
                                ->relationship('venue', 'name')
                                ->searchable()
                                ->preload()
                                ->helperText('Vyberte existující klub, kolej nebo sál v Brně.'),

                            Forms\Components\TextInput::make('location_name')
                                ->label('Vlastní místo konání')
                                ->placeholder('např. Fakulta informatiky MUNI, učebna D2')
                                ->helperText('Vyplňte pouze pokud místo není v seznamu výše.'),
                        ])->columns(2),

                    Forms\Components\Section::make('Datum a čas')
                        ->schema([
                            Forms\Components\DateTimePicker::make('starts_at')
                                ->label('Začátek akce')
                                ->required()
                                ->native(false),

                            Forms\Components\DateTimePicker::make('ends_at')
                                ->label('Konec akce')
                                ->native(false),

                            Forms\Components\Toggle::make('is_all_day')
                                ->label('Celodenní akce')
                                ->default(false),
                        ])->columns(3),

                    Forms\Components\Section::make('Podrobný popis & Plakát')
                        ->schema([
                            Forms\Components\TextInput::make('cover_image')
                                ->label('Odkaz na plakát / fotku (URL)')
                                ->placeholder('https://...')
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('short_description')
                                ->label('Krátký upoutávkový popis')
                                ->rows(2)
                                ->columnSpanFull()
                                ->helperText('Zobrazí se na kartě akce v přehledu.'),

                            Forms\Components\Textarea::make('description')
                                ->label('Kompletní popis a program akce')
                                ->rows(5)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Stav a publikace')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->label('Stav akce')
                                ->options([
                                    'published' => 'Publikováno (veřejné)',
                                    'pending_review' => 'Ke schválení (moderace)',
                                    'draft' => 'Koncept',
                                    'cancelled' => 'Zrušeno',
                                ])
                                ->default('published')
                                ->required(),

                            Forms\Components\Toggle::make('is_featured')
                                ->label('Zvýrazněná akce (Hero sekce)')
                                ->default(false),
                        ]),

                    Forms\Components\Section::make('Vstupné & Vstupenky')
                        ->schema([
                            Forms\Components\Select::make('price_type')
                                ->label('Typ vstupného')
                                ->options([
                                    'free' => 'Zdarma',
                                    'paid' => 'Placené',
                                    'voluntary' => 'Dobrovolné',
                                ])
                                ->default('free')
                                ->required(),

                            Forms\Components\TextInput::make('price_amount')
                                ->label('Částka (Kč)')
                                ->numeric()
                                ->prefix('Kč')
                                ->visible(fn (Forms\Get $get) => $get('price_type') === 'paid'),

                            Forms\Components\TextInput::make('ticket_url')
                                ->label('Odkaz na lístky (GoOut / Boomhits)')
                                ->url()
                                ->placeholder('https://goout.net/...'),
                        ]),

                    Forms\Components\Section::make('Odkazy na sítě')
                        ->schema([
                            Forms\Components\TextInput::make('facebook_event_url')
                                ->label('Facebook událost URL')
                                ->url(),

                            Forms\Components\TextInput::make('instagram_post_url')
                                ->label('Instagram příspěvek URL')
                                ->url(),

                            Forms\Components\Select::make('source_type')
                                ->label('Původ dat')
                                ->options([
                                    'manual' => 'Vloženo ručně',
                                    'scraper_web' => 'Scraper: Web / RSS / iCal',
                                    'scraper_facebook' => 'Scraper: Facebook',
                                    'scraper_instagram' => 'Scraper: Instagram',
                                ])
                                ->default('manual')
                                ->disabled(),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')
                    ->label('Plakát')
                    ->state(fn ($record) => $record->cover_image_url)
                    ->circular()
                    ->defaultImageUrl('https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=150&q=80'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Název')
                    ->searchable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('organization.name')
                    ->label('Spolek')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('organization.university')
                    ->label('Univerzita')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'MUNI' => 'info',
                        'VUT' => 'danger',
                        'MENDELU' => 'success',
                        'UNOB' => 'warning',
                        'VETUNI' => 'purple',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategorie')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Začátek')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('location_display')
                    ->label('Místo')
                    ->limit(25),

                Tables\Columns\TextColumn::make('formatted_price')
                    ->label('Vstupné')
                    ->badge()
                    ->color(fn ($record) => $record->price_type === 'free' ? 'success' : 'warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stav')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'pending_review' => 'warning',
                        'rejected' => 'danger',
                        'draft' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'published' => 'Publikováno',
                        'pending_review' => 'Ke schválení',
                        'rejected' => 'Zamítnuto',
                        'draft' => 'Koncept',
                        'cancelled' => 'Zrušeno',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('missing_fields')
                    ->label('Kvalita dat')
                    ->badge()
                    ->state(function (Event $record) {
                        $missing = $record->missing_fields;
                        if (empty($missing)) {
                            return ['Vše v pořádku'];
                        }
                        return array_values($missing);
                    })
                    ->color(fn ($state) => $state === 'Vše v pořádku' ? 'success' : 'warning')
                    ->wrap(),
            ])
            ->defaultSort('starts_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stav')
                    ->options([
                        'pending_review' => 'Ke schválení (moderace)',
                        'published' => 'Publikováno',
                        'rejected' => 'Zamítnuto',
                        'draft' => 'Koncept',
                        'cancelled' => 'Zrušeno',
                    ]),
                Tables\Filters\Filter::make('missing_data')
                    ->label('Vyžaduje doplnění dat')
                    ->query(fn (Builder $query) => $query->withMissingData()),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorie')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('university')
                    ->label('Univerzita')
                    ->options([
                        'MUNI' => 'Masarykova univerzita (MUNI)',
                        'VUT' => 'Vysoké učení technické (VUT)',
                        'MENDELU' => 'Mendelova univerzita (MENDELU)',
                        'UNOB' => 'Univerzita obrany (UNOB)',
                        'VETUNI' => 'Veterinární univerzita (VETUNI)',
                        'OTHER' => 'Ostatní / Meziuniverzitní',
                    ])
                    ->query(fn (Builder $query, array $data) => !empty($data['value']) ? $query->whereHas('organization', fn ($q) => $q->where('university', $data['value'])) : $query),
                Tables\Filters\SelectFilter::make('organization')
                    ->label('Spolek')
                    ->relationship('organization', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(fn (Event $record) => $record->status === 'rejected' ? 'Obnovit' : 'Schválit')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Event $record) => in_array($record->status, ['pending_review', 'rejected']))
                    ->action(function (Event $record) {
                        $record->update(['status' => 'published']);
                        Notification::make()
                            ->title('Akce byla schválena a publikována na webu.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Zamítnout')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Zamítnout a vyřadit akci?')
                    ->modalDescription('Akce se přesune do záložky "Vyřazené / Zamítnuté", nebude se zobrazovat na webu a scraper ji už znovu nestáhne.')
                    ->visible(fn (Event $record) => $record->status !== 'rejected')
                    ->action(function (Event $record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()
                            ->title('Akce byla vyřazena (znovu se již nescrapne).')
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\Action::make('view_web')
                    ->label('Zobrazit')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->visible(fn (Event $record) => $record->status === 'published')
                    ->url(fn (Event $record) => url('/akce/' . $record->slug))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Trvale smazat')
                    ->modalHeading('Trvale smazat akci z databáze?')
                    ->modalDescription('Akce bude nevratně smazána.')
                    ->visible(fn (Event $record) => $record->status === 'rejected'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('approve_bulk')
                        ->label('Schválit / Obnovit vybrané')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(fn (Event $e) => $e->update(['status' => 'published']));
                            Notification::make()
                                ->title('Vybrané akce byly schváleny a publikovány.')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('reject_bulk')
                        ->label('Zamítnout vybrané')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hromadně zamítnout vybrané akce?')
                        ->modalDescription('Vybrané akce budou přesunuty do záložky "Vyřazené / Zamítnuté" a scraper je již znovu nestáhne.')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(fn (Event $e) => $e->update(['status' => 'rejected']));
                            Notification::make()
                                ->title('Vybrané akce byly vyřazeny.')
                                ->warning()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Trvale smazat vybrané'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
