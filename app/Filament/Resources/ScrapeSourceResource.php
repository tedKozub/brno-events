<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScrapeSourceResource\Pages;
use App\Models\ScrapeSource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;

class ScrapeSourceResource extends Resource
{
    protected static ?string $model = ScrapeSource::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $modelLabel = 'Zdroj scrapingu';
    protected static ?string $pluralModelLabel = 'Zdroje scrapingu';
    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Nastavení zdroje')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Název zdroje')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label('Typ parseru')
                            ->options([
                                'web_mendelu' => 'Web: MENDELU Kalendář',
                                'web_fit_vut' => 'Web: FIT VUT Kalendář',
                                'web_unob' => 'Web: UNOB Kalendář',
                                'web_muni' => 'Web: MUNI Kalendář',
                                'web_ical' => 'iCal / ICS Feed (.ics soubor nebo URL)',
                                'web_rss' => 'RSS / Atom Feed',
                                'web_generic' => 'Generická webová stránka (Schema.org Event)',
                            ])
                            ->required(),

                        Forms\Components\Select::make('university')
                            ->label('Univerzita')
                            ->options([
                                'MUNI' => 'Masarykova univerzita (MUNI)',
                                'VUT' => 'Vysoké učení technické (VUT)',
                                'MENDELU' => 'Mendelova univerzita (MENDELU)',
                                'UNOB' => 'Univerzita obrany (UNOB)',
                                'VETUNI' => 'Veterinární univerzita (VETUNI)',
                                'OTHER' => 'Ostatní / Meziuniverzitní',
                            ])
                            ->default('OTHER')
                            ->required(),

                        Forms\Components\Select::make('organization_id')
                            ->label('Přiřadit ke spolku (nepovinné)')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('url')
                            ->label('Cílová URL adresa nebo soubor')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktivní zdroj pro automatické stahování')
                            ->default(true),
                    ])->columns(2),
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

                Tables\Columns\TextColumn::make('university')
                    ->label('Univerzita')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'MUNI' => 'info',
                        'VUT' => 'danger',
                        'MENDELU' => 'success',
                        'UNOB' => 'warning',
                        'VETUNI' => 'purple',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL')
                    ->limit(35)
                    ->url(fn ($record) => str_starts_with($record->url, 'http') ? $record->url : null, true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktivní')
                    ->boolean(),

                Tables\Columns\TextColumn::make('last_status')
                    ->label('Poslední běh')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'ok' => 'success',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'ok' => 'V pořádku',
                        'error' => 'Chyba',
                        default => 'Zatím neběželo',
                    }),

                Tables\Columns\TextColumn::make('last_scraped_at')
                    ->label('Naposledy staženo')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('scrape_now')
                    ->label('Spustit scraping')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->action(function (ScrapeSource $record) {
                        Artisan::call('events:scrape', ['source_id' => $record->id]);
                        $output = Artisan::output();

                        Notification::make()
                            ->title("Scraping pro '{$record->name}' dokončen")
                            ->body(Str::limit($output, 200))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScrapeSources::route('/'),
            'create' => Pages\CreateScrapeSource::route('/create'),
            'edit' => Pages\EditScrapeSource::route('/{record}/edit'),
        ];
    }
}
