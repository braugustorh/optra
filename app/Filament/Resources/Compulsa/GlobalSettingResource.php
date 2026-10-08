<?php

declare(strict_types=1);

namespace App\Filament\Resources\Compulsa;

use App\Filament\Resources\Compulsa\GlobalSettingResource\Pages;
use App\Models\Compulsa\GlobalSetting;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;


class GlobalSettingResource extends Resource
{
    protected static ?string $model = GlobalSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Config. Cuotas IMSS';
    protected static ?string $modelLabel = 'Configuración anual';
    protected static ?string $pluralModelLabel = 'Configuraciones anuales';
    protected static ?string $navigationGroup = 'Nómina';
    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool
    {
        return Auth::check() && Auth::user()->hasAnyRole(['Administrador', 'RH Corp']);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('config')
                ->columnSpanFull()
                ->tabs([

                    Tab::make('Valores base')
                        ->icon('heroicon-o-scale')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('ejercicio')
                                    ->label('Ejercicio')
                                    ->numeric()
                                    ->minValue(2000)
                                    ->maxValue(2100)
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Año fiscal al que aplica esta configuración.'),

                                TextInput::make('tope_uma_veces')
                                    ->label('Tope UMAs (veces)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(25)
                                    ->required()
                                    ->helperText('Múltiplo de UMA usado como tope del SDI. Default: 25.'),
                            ]),

                            Grid::make(3)->schema([
                                TextInput::make('valor_uma')
                                    ->label('UMA')
                                    ->numeric()
                                    ->step(0.0001)
                                    ->prefix('$')
                                    ->required()
                                    ->helperText('Unidad de Medida y Actualización vigente.'),

                                TextInput::make('valor_umi')
                                    ->label('UMI')
                                    ->numeric()
                                    ->step(0.0001)
                                    ->prefix('$')
                                    ->helperText('Unidad Mixta Infonavit (si aplica).'),

                                TextInput::make('salario_minimo')
                                    ->label('Salario mínimo')
                                    ->numeric()
                                    ->step(0.0001)
                                    ->prefix('$')
                                    ->required()
                                    ->helperText('Piso del SDI cotizable.'),
                            ]),

                            Section::make('Disposiciones de Ley')
                                ->schema([
                                    Toggle::make('exencion_cuota_obrera_sm')
                                        ->label('Exención de cuota obrera para Salario Mínimo (Art. 36 LSS)')
                                        ->helperText('Si el SDI del trabajador es igual o inferior al salario mínimo, la cuota obrera es $0.00 y el patrón absorbe íntegramente la aportación.')
                                        ->default(true),
                                ]),
                        ]),

                    Tab::make('Cuotas EMA (mensual)')
                        ->icon('heroicon-o-calendar')
                        ->schema([
                            Section::make('Enfermedad y Maternidad')
                                ->description('Cuota Fija se aplica sobre UMA × días. Excedente aplica sobre lo que rebasa 3 UMA.')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('rate_cuota_fija_patronal')
                                        ->label('Cuota Fija (patronal)')->suffix('%')
                                        ->numeric()->step(0.001)->default(20.400)->required(),
                                    TextInput::make('rate_excedente_patronal')
                                        ->label('Excedente (patronal)')->suffix('%')
                                        ->numeric()->step(0.001)->default(1.100)->required(),
                                    TextInput::make('rate_excedente_obrero')
                                        ->label('Excedente (obrero)')->suffix('%')
                                        ->numeric()->step(0.001)->default(0.400)->required(),
                                    TextInput::make('rate_prest_dinero_patronal')
                                        ->label('Prestaciones en dinero (patronal)')->suffix('%')
                                        ->numeric()->step(0.001)->default(0.700)->required(),
                                    TextInput::make('rate_prest_dinero_obrero')
                                        ->label('Prestaciones en dinero (obrero)')->suffix('%')
                                        ->numeric()->step(0.001)->default(0.250)->required(),
                                    TextInput::make('rate_gmp_patronal')
                                        ->label('Gastos Médicos Pensionados (patronal)')->suffix('%')
                                        ->numeric()->step(0.001)->default(1.050)->required(),
                                    TextInput::make('rate_gmp_obrero')
                                        ->label('Gastos Médicos Pensionados (obrero)')->suffix('%')
                                        ->numeric()->step(0.001)->default(0.375)->required(),
                                ]),

                            Section::make('Invalidez, Vida y otros ramos EMA')
                                ->columns(3)
                                ->schema([
                                    TextInput::make('rate_iv_patronal')
                                        ->label('Invalidez y Vida (patronal)')->suffix('%')
                                        ->numeric()->step(0.001)->default(1.750)->required(),
                                    TextInput::make('rate_iv_obrero')
                                        ->label('Invalidez y Vida (obrero)')->suffix('%')
                                        ->numeric()->step(0.001)->default(0.625)->required(),
                                    TextInput::make('rate_guarderias_patronal')
                                        ->label('Guarderías y P. Sociales (patronal)')->suffix('%')
                                        ->numeric()->step(0.001)->default(1.000)->required(),
                                ]),

                            Section::make('Riesgos de trabajo')
                                ->description('La prima de riesgo se configura por Razón Social en cada compulsa. Aquí sólo es informativa.')
                                ->schema([
                                    // Placeholder informativo, no persiste — la prima_riesgo vive en compulsa_risk_premiums.
                                ]),
                        ]),

                    Tab::make('Cuotas EBA (bimestral)')
                        ->icon('heroicon-o-calendar-days')
                        ->schema([
                            Section::make('Retiro y Cesantía Obrera')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('rate_retiro_patronal')
                                        ->label('Retiro (patronal)')->suffix('%')
                                        ->numeric()->step(0.001)->default(2.000)->required(),
                                    TextInput::make('rate_cesantia_obrero')
                                        ->label('Cesantía y Vejez (obrero)')->suffix('%')
                                        ->numeric()->step(0.001)->default(1.125)->required()
                                        ->helperText('Tasa fija de ley (Art. 168 LSS Fracc. II inc. b).'),
                                ]),

                            Section::make('Cesantía y Vejez Patronal (Tabla Progresiva por UMA)')
                                ->description('Conforme al Art. Segundo Transitorio de la reforma a la Ley del Seguro Social, la cuota patronal es progresiva por nivel salarial.')
                                ->headerActions([
                                    Action::make('cargar_tabla_oficial')
                                        ->label('Cargar tabla oficial DOF')
                                        ->icon('heroicon-o-arrow-path')
                                        ->color('primary')
                                        ->requiresConfirmation()
                                        ->modalHeading('Cargar tabla oficial del DOF')
                                        ->modalDescription('Esto reemplazará los rangos actuales con los porcentajes de ley oficiales publicados para este ejercicio.')
                                        ->action(function ($set, $get) {
                                            $ejercicio = (int) ($get('ejercicio') ?: 2026);
                                            $set('tabla_cesantia_patronal', GlobalSetting::defaultCesantiaTable($ejercicio));
                                        }),
                                ])
                                ->schema([
                                    Repeater::make('tabla_cesantia_patronal')
                                        ->label('Rangos de Salario y Cuotas Patronales')
                                        ->columns(4)
                                        ->reorderable(false)
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => ($state['rango'] ?? null) . (isset($state['porcentaje_patronal']) ? ' (' . $state['porcentaje_patronal'] . '%)' : ''))
                                        ->schema([
                                            TextInput::make('rango')
                                                ->label('Concepto / Rango')
                                                ->required(),
                                            TextInput::make('limite_inferior_umas')
                                                ->label('Límite Inf. (UMAs)')
                                                ->numeric()
                                                ->step(0.01)
                                                ->required(),
                                            TextInput::make('limite_superior_umas')
                                                ->label('Límite Sup. (UMAs)')
                                                ->numeric()
                                                ->step(0.01)
                                                ->helperText('Vacío = En adelante'),
                                            TextInput::make('porcentaje_patronal')
                                                ->label('% Patronal')
                                                ->numeric()
                                                ->step(0.001)
                                                ->suffix('%')
                                                ->required(),
                                        ])
                                        ->default(fn ($get) => GlobalSetting::defaultCesantiaTable((int) ($get('ejercicio') ?: 2026))),

                                    TextInput::make('rate_cesantia_patronal')
                                        ->label('Tasa de fallback histórica')
                                        ->suffix('%')
                                        ->numeric()
                                        ->step(0.001)
                                        ->default(3.150)
                                        ->helperText('Se utiliza como respaldo si la tabla no está disponible.'),
                                ]),

                            Section::make('INFONAVIT')
                                ->schema([
                                    TextInput::make('rate_infonavit_patronal')
                                        ->label('Aportación patronal Infonavit')->suffix('%')
                                        ->numeric()->step(0.001)->default(5.000)->required(),
                                ]),
                        ]),


                    Tab::make('Notas')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Textarea::make('notas')
                                ->label('Notas / observaciones')
                                ->rows(6)
                                ->helperText('Referencias legales, fecha de vigencia, cambios respecto al año anterior, etc.'),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ejercicio')
                    ->label('Ejercicio')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('valor_uma')
                    ->label('UMA')
                    ->money('MXN', divideBy: 1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('valor_umi')
                    ->label('UMI')
                    ->money('MXN', divideBy: 1)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('salario_minimo')
                    ->label('Salario mínimo')
                    ->money('MXN', divideBy: 1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('tope_uma_veces')
                    ->label('Tope UMAs')
                    ->numeric(2)
                    ->suffix(' UMAs'),
                Tables\Columns\IconColumn::make('exencion_cuota_obrera_sm')
                    ->label('Art. 36 LSS')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('updated_at')

                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('ejercicio', 'desc')
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
            'index' => Pages\ListGlobalSettings::route('/'),
            'create' => Pages\CreateGlobalSetting::route('/create'),
            'edit' => Pages\EditGlobalSetting::route('/{record}/edit'),
        ];
    }
}
