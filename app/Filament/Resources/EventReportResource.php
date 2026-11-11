<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventReportResource\Pages;
use App\Models\EventReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EventReportResource extends Resource
{
    protected static ?string $model = EventReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $modelLabel = 'Nahlášená chyba';
    protected static ?string $pluralModelLabel = 'Hlášení chyb a vad';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', 'pending')->count();
        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Podrobnosti hlášení')
                    ->schema([
                        Forms\Components\Select::make('event_id')
                            ->label('Dotčená akce')
                            ->relationship('event', 'title')
                            ->searchable()
                            ->required()
                            ->disabledOn('edit'),

                        Forms\Components\Select::make('reason')
                            ->label('Důvod hlášení')
                            ->options(EventReport::$reasons)
                            ->required(),

                        Forms\Components\Textarea::make('details')
                            ->label('Popis problému od uživatele')
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('contact_email')
                            ->label('Kontakt na nahlašujícího (volitelný)')
                            ->email(),

                        Forms\Components\Select::make('status')
                            ->label('Stav vyřešení')
                            ->options([
                                'pending' => 'Čeká na vyřešení',
                                'resolved' => 'Vyřešeno (opraveno)',
                                'dismissed' => 'Zamítnuto (není chyba)',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Interní poznámka administrátora')
                            ->placeholder('např. Čas upraven z 18:00 na 19:00 podle FB')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event.title')
                    ->label('Akce')
                    ->weight('bold')
                    ->searchable()
                    ->wrap()
                    ->url(fn (EventReport $record) => $record->event ? route('filament.admin.resources.events.edit', ['record' => $record->event_id]) : null),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Důvod')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cancelled' => 'danger',
                        'wrong_time', 'wrong_location' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($record) => $record->reason_label),

                Tables\Columns\TextColumn::make('details')
                    ->label('Popis problému')
                    ->limit(50)
                    ->wrap(),

                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Nahlašovatel')
                    ->placeholder('Anonymní')
                    ->copyable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Stav')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'resolved' => 'success',
                        'dismissed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Čeká na řešení',
                        'resolved' => 'Vyřešeno',
                        'dismissed' => 'Zamítnuto',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Nahlášeno')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Stav')
                    ->options([
                        'pending' => 'Čeká na řešení',
                        'resolved' => 'Vyřešeno',
                        'dismissed' => 'Zamítnuto',
                    ])
                    ->default('pending'),
                Tables\Filters\SelectFilter::make('reason')
                    ->label('Důvod')
                    ->options(EventReport::$reasons),
            ])
            ->actions([
                Tables\Actions\Action::make('resolve')
                    ->label('Vyřešit')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (EventReport $record) => $record->status === 'pending')
                    ->action(function (EventReport $record) {
                        $record->update(['status' => 'resolved']);
                        Notification::make()
                            ->title('Hlášení označeno jako vyřešené.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('edit_event')
                    ->label('Upravit akci')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (EventReport $record) => route('filament.admin.resources.events.edit', ['record' => $record->event_id])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('resolve_bulk')
                        ->label('Označit vybrané jako vyřešené')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $records->each(fn (EventReport $r) => $r->update(['status' => 'resolved']));
                            Notification::make()
                                ->title('Hlášení označena jako vyřešená.')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListEventReports::route('/'),
            'create' => Pages\CreateEventReport::route('/create'),
            'edit' => Pages\EditEventReport::route('/{record}/edit'),
        ];
    }
}
