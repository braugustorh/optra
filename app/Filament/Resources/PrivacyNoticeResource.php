<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrivacyNoticeResource\Pages;
use App\Filament\Resources\PrivacyNoticeResource\RelationManagers;
use App\Models\PrivacyNotice;
use App\Services\PrivacyNoticeService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PrivacyNoticeResource extends Resource
{
    protected static ?string $model = PrivacyNotice::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Optra Estructura';
    protected static ?string $modelLabel = 'Aviso de Privacidad';
    protected static ?string $pluralModelLabel = 'Avisos de Privacidad';
    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasAnyRole('Administrador', 'Super Administrador', 'RH Corp') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->hasAnyRole('Administrador', 'Super Administrador', 'RH Corp') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()?->hasAnyRole('Administrador', 'Super Administrador', 'RH Corp') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->hasAnyRole('Administrador', 'Super Administrador') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Configuración General del Aviso')
                    ->description('Establece cómo se mostrará el aviso al ingresar a la plataforma y cómo se resolverán los datos del Responsable.')
                    ->icon('heroicon-m-cog-6-tooth')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Nombre del Aviso (Aparece en la Cabecera/Header del Modal)')
                            ->required()
                            ->maxLength(255)
                            ->default('Aviso de Privacidad y Protección de Datos Personales')
                            ->placeholder('Ej. Aviso de Privacidad Institucional')
                            ->columnSpan(['sm' => 2]),

                        Forms\Components\TextInput::make('version')
                            ->label('Versión')
                            ->maxLength(50)
                            ->default('v1.0')
                            ->placeholder('Ej. v1.0 - 2026')
                            ->columnSpan(['sm' => 1]),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aviso Vigente (Activo)')
                            ->helperText('Al marcar como activo, los usuarios deberán aceptar esta versión en su próximo inicio de sesión.')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(['sm' => 1]),

                        Forms\Components\Select::make('scope_type')
                            ->label('Tipo de Configuración de Datos del Responsable')
                            ->options(PrivacyNotice::getScopeOptions())
                            ->default(PrivacyNotice::SCOPE_RAZON_SOCIAL)
                            ->required()
                            ->reactive()
                            ->helperText('Define de dónde toma el aviso el Nombre de la Empresa y la Dirección fiscal.')
                            ->columnSpan(['sm' => 2]),

                        Forms\Components\TextInput::make('provider_name')
                            ->label('Nombre de la Empresa Proveedora')
                            ->default('NetInnfode')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('NetInnfode')
                            ->helperText('Sustituye la etiqueta [NOMBRE DE LA EMPRESA PROVEEDORA] en los textos.')
                            ->columnSpan(['sm' => 2]),

                        Forms\Components\TextInput::make('general_company_name')
                            ->label('Nombre de Empresa (General / Respaldo)')
                            ->maxLength(255)
                            ->placeholder('Ej. RED INTEGRAL OPTIBUS S.A. DE C.V.')
                            ->helperText('Utilizado cuando el tipo es General, o si un colaborador no tiene Razón Social/Sede asignada.')
                            ->columnSpan(['sm' => 2]),

                        Forms\Components\Textarea::make('general_address')
                            ->label('Dirección Completa (General / Respaldo)')
                            ->rows(2)
                            ->placeholder('Calle, No. Exterior, Colonia, C.P., Ciudad, Estado')
                            ->helperText('Utilizada cuando el tipo es General o si la Razón Social no tiene domicilio capturado.')
                            ->columnSpan(['sm' => 2]),
                    ])->columns(2),

                Forms\Components\Section::make('Variables Dinámicas Disponibles')
                    ->description('Puedes insertar cualquiera de las siguientes etiquetas en los editores de texto para que se reemplacen dinámicamente según el usuario que ingrese:')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('variables_guide')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                                    <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                        <code class="font-bold text-teal-600 dark:text-teal-400">[NOMBRE DE LA EMPRESA PROVEEDORA]</code>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400">Inserta la empresa proveedora (por defecto NetInnfode).</p>
                                    </div>
                                    <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                        <code class="font-bold text-teal-600 dark:text-teal-400">[NOMBRE DE LA EMPRESA CONTRATANTE]</code>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400">Inserta la Razón Social o Sede según el tipo configurado.</p>
                                    </div>
                                    <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                        <code class="font-bold text-teal-600 dark:text-teal-400">[DIRECCIÓN COMPLETA]</code>
                                        <p class="mt-1 text-gray-600 dark:text-gray-400">Inserta el domicilio fiscal de la Razón Social o dirección de la Sede.</p>
                                    </div>
                                </div>
                            ')),
                    ]),

                Forms\Components\Tabs::make('Contenido y Estilo del Aviso')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('1. Aviso Importante')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Forms\Components\RichEditor::make('initial_notice_content')
                                    ->label('Texto del Aviso Importante (Paso Inicial)')
                                    ->default(fn () => PrivacyNoticeService::getDefaultInitialContent())
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'link',
                                        'bulletList',
                                        'orderedList',
                                        'undo',
                                        'redo',
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('2. Aviso Simplificado')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Forms\Components\RichEditor::make('simplified_content')
                                    ->label('Contenido del Aviso de Privacidad Simplificado')
                                    ->default(fn () => PrivacyNoticeService::getDefaultSimplifiedContent())
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'link',
                                        'heading',
                                        'h2',
                                        'h3',
                                        'bulletList',
                                        'orderedList',
                                        'blockquote',
                                        'undo',
                                        'redo',
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('3. Aviso Extendido')
                            ->icon('heroicon-m-book-open')
                            ->schema([
                                Forms\Components\RichEditor::make('extended_content')
                                    ->label('Contenido del Aviso de Privacidad Extendido / Integral')
                                    ->default(fn () => PrivacyNoticeService::getDefaultExtendedContent())
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'underline',
                                        'strike',
                                        'link',
                                        'heading',
                                        'h2',
                                        'h3',
                                        'bulletList',
                                        'orderedList',
                                        'blockquote',
                                        'undo',
                                        'redo',
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('4. Casilla de Aceptación')
                            ->icon('heroicon-m-check-badge')
                            ->schema([
                                Forms\Components\Textarea::make('checkbox_label')
                                    ->label('Texto para la Casilla de Aceptación')
                                    ->default(fn () => PrivacyNoticeService::getDefaultCheckboxLabel())
                                    ->required()
                                    ->rows(3)
                                    ->helperText('Texto legal que acompaña la casilla que el colaborador debe marcar antes de aceptar.'),
                            ]),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('is_active', 'desc')
            ->columns([
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Vigente')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Nombre del Aviso (Cabecera)')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('version')
                    ->label('Versión')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('scope_type')
                    ->label('Tipo de Configuración')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        PrivacyNotice::SCOPE_RAZON_SOCIAL => 'Por Razón Social',
                        PrivacyNotice::SCOPE_SEDE => 'Por Sede',
                        PrivacyNotice::SCOPE_GENERAL => 'General',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        PrivacyNotice::SCOPE_RAZON_SOCIAL => 'primary',
                        PrivacyNotice::SCOPE_SEDE => 'warning',
                        PrivacyNotice::SCOPE_GENERAL => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('acceptances_count')
                    ->counts('acceptances')
                    ->label('Aceptaciones')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider_name')
                    ->label('Proveedor')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Solo Vigente')
                    ->trueLabel('Avisos Vigentes')
                    ->falseLabel('Históricos'),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Vista Previa')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->action(function (PrivacyNotice $record, $livewire) {
                        $livewire->dispatch('open-privacy-notice-preview', noticeId: $record->id);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AcceptancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrivacyNotices::route('/'),
            'create' => Pages\CreatePrivacyNotice::route('/create'),
            'edit' => Pages\EditPrivacyNotice::route('/{record}/edit'),
        ];
    }
}
