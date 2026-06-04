<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstitutionResource\Pages;
use App\Filament\Resources\InstitutionResource\RelationManagers;
use App\Models\Institution;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InstitutionResource extends Resource
{
    protected static ?string $model = Institution::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-building-library';
    protected static string|\UnitEnum|null $navigationGroup = 'Instituições';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Instituições';
    protected static ?string $modelLabel = 'Instituição';
    protected static ?string $pluralModelLabel = 'Instituições';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['type']);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Forms\Components\Select::make('institution_type_id')
                    ->label('Tipo de Instituição')
                    ->relationship('type', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                Forms\Components\TextInput::make('acronym')
                    ->label('Sigla')
                    ->unique(ignoreRecord: true)
                    ->maxLength(191),
                Forms\Components\TextInput::make('phone')
                    ->label('Telefone')
                    ->tel()
                    ->prefix('+244')
                    ->placeholder('9XX XXX XXX')
                    ->mask('999 999 999')
                    ->rule('regex:/^[9][0-9]{2}\s?[0-9]{3}\s?[0-9]{3}$/')
                    ->validationMessages([
                        'regex' => 'O número de telefone deve ter 9 dígitos e começar com 9.',
                    ])
                    ->maxLength(11),
                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->rule('email:rfc,dns')
                    ->validationMessages([
                        'email' => 'Por favor, insira um endereço de e-mail válido.',
                    ])
                    ->suffixIcon('heroicon-o-envelope')
                    ->maxLength(191),
                Forms\Components\Select::make('province')
                    ->label('Província')
                    ->options(fn() => \App\Models\Province::orderBy('name')->pluck('name', 'name'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn(\Filament\Schemas\Components\Utilities\Set $set) => $set('municipality', null)),
                Forms\Components\Select::make('municipality')
                    ->label('Município')
                    ->options(function (\Filament\Schemas\Components\Utilities\Get $get) {
                        $provinceName = $get('province');
                        if (!$provinceName) {
                            return [];
                        }
                        $province = \App\Models\Province::where('name', $provinceName)->first();
                        if (!$province) {
                            return [];
                        }
                        return \App\Models\Municipality::where('province_id', $province->id)
                            ->orderBy('name')
                            ->pluck('name', 'name');
                    })
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('address')
                    ->label('Endereço')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('logo')
                    ->label('Logótipo')
                    ->image()
                    ->disk('public')
                    ->directory('institutions'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type.name')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('acronym')
                    ->label('Sigla')
                    ->searchable(),
                Tables\Columns\TextColumn::make('province')
                    ->label('Província')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Criar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->createAnotherAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-plus-circle')->label('Salvar e criar outro'))
                    ->createAnother(true)
                    ->successNotificationTitle('Registo criado com sucesso!')
                    ->label('Nova Instituição'),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                \Filament\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->modalSubmitAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-check')->label('Salvar'))
                    ->modalCancelAction(fn(\Filament\Actions\Action $action) => $action->icon('heroicon-o-x-mark')->label('Cancelar')->color('danger'))
                    ->successNotificationTitle('Registo atualizado com sucesso!'),
                \Filament\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
                ])->icon('heroicon-s-cog-6-tooth')->tooltip('Ações'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstitutions::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Institution') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }
}
