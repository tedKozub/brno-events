<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $modelLabel = 'Spolek';
    protected static ?string $pluralModelLabel = 'Studentské Spolky';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->isClubAdmin() && $user->organization_id) {
            $query->where('id', $user->organization_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profil spolku')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Název spolku')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('university')
                            ->label('Univerzita')
                            ->options([
                                'MUNI' => 'Masarykova univerzita (MUNI)',
                                'VUT' => 'Vysoké učení technické (VUT)',
                                'MENDELU' => 'Mendelova univerzita (MENDELU)',
                                'UNOB' => 'Univerzita obrany (UNOB)',
                                'VETUNI' => 'Veterinární univerzita Brno (VETUNI)',
                                'OTHER' => 'Ostatní / Meziuniverzitní',
                            ])
                            ->default('MUNI')
                            ->required(),

                        Forms\Components\TextInput::make('faculty')
                            ->label('Fakulta')
                            ->placeholder('např. FI, FSS, FIT, FEKT, nebo Všechny fakulty'),

                        Forms\Components\TextInput::make('logo_url')
                            ->label('Logo spolku (URL)')
                            ->placeholder('https://...'),

                        Forms\Components\Textarea::make('description')
                            ->label('O spolku / Popis činnosti')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Kontakty a sociální sítě')
                    ->schema([
                        Forms\Components\TextInput::make('website_url')
                            ->label('Oficiální web')
                            ->url()
                            ->placeholder('https://...'),

                        Forms\Components\TextInput::make('instagram_handle')
                            ->label('Instagram profil (@handle)')
                            ->prefix('@')
                            ->placeholder('munie_cz'),

                        Forms\Components\TextInput::make('facebook_url')
                            ->label('Facebook stránka (URL)')
                            ->url(),

                        Forms\Components\TextInput::make('contact_email')
                            ->label('Kontaktní e-mail')
                            ->email(),

                        Forms\Components\Toggle::make('is_verified')
                            ->label('Ověřený studentský spolek')
                            ->visible(fn () => auth()->user()?->isSuperAdmin())
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl('https://images.unsplash.com/photo-1523240795612-9a054b0db644?auto=format&fit=crop&w=150&q=80'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Název spolku')
                    ->searchable()
                    ->weight('bold'),

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

                Tables\Columns\TextColumn::make('faculty')
                    ->label('Fakulta')
                    ->searchable(),

                Tables\Columns\TextColumn::make('instagram_handle')
                    ->label('Instagram')
                    ->formatStateUsing(fn ($state) => $state ? '@' . ltrim($state, '@') : '-')
                    ->url(fn ($record) => $record->instagram_handle ? 'https://instagram.com/' . ltrim($record->instagram_handle, '@') : null, true),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Ověřen')
                    ->boolean(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
