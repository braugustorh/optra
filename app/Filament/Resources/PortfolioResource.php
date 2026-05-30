<?php

namespace App\Filament\Resources;

use App\Exports\PortfolioExport;
use App\Filament\Resources\PortfolioResource\Pages;
use App\Filament\Resources\PortfolioResource\RelationManagers;
use App\Helpers\VisorRoleHelper;
use App\Models\User;
use App\Models\Portfolio;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\Page;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Columns\ColumnGroup;
use Illuminate\Support\Facades\Storage;

class PortfolioResource extends Resource
{
    protected static ?string $model = Portfolio::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';
    public static function getNavigationGroup(): ?string
    {
        if (auth()->user()?->hasAnyRole(['RH Corp', 'Administrador','Super Administrador'])) {
            return 'Colaboradores';
        }

        return 'Mi Perfil';
    }

    protected static ?string $navigationLabel = 'Portafolio Digital';
    protected static ?string $name = 'Portfolio';
    protected static ?string $label = 'Portafolio Digital';
    protected static ?string $title= 'Portafolio Digital';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {

        return (\auth()->user()->hasAnyRole('RH','RH Corp','Administrador','Supervisor','Colaborador','Visor','Gerente','Operativo'));

    }
    public static function canCreate(): bool
    {
        return (\auth()->user()->hasAnyRole('RH','RH Corp','Administrador'));
    }
    public static function canEdit(Model $record): bool
    {
        return (\auth()->user()->hasAnyRole('RH','RH Corp','Administrador'));
    }

    public static function form(Form $form): Form
    {
        $forms = Tabs::make('Portafolio')
            ->tabs([
                Tab::make('Colaborador')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Colaborador')
                            ->searchable()
                            ->preload()
                            ->options(function (Get $get):Collection {
                                if ($get('user_id')!==null){
                                    $user= User::query()
                                        ->where('id', $get('user_id'))
                                        ->where('status',1)
                                        ->where('id','!=',1)
                                        ->get()
                                        ->mapWithKeys(fn (User $user): array => [$user->id => $user->name.' '.$user->first_name .' '.$user->last_name]);
                                }else{
                                    if (auth()->user()->hasAnyRole('Administrador','RH Corp','Visor')){
                                        $user=User::query()
                                            ->doesntHave('portfolio')
                                            ->where('status',1)
                                            ->where('id','!=',1)
                                            ->with('portfolio') // Incluye la relación 'portfolio' en la consulta
                                            ->get()
                                            ->mapWithKeys(fn (User $user): array => [
                                                $user->id => $user->name.' '.$user->first_name.' '.$user->last_name
                                            ]);
                                    }elseif (auth()->user()->hasRole('RH')){
                                        $user= User::query()
                                            ->doesntHave('portfolio')
                                            ->where('status',1)
                                            ->where('id','!=',1)
                                            ->where('sede_id',\auth()->user()->sede_id)
                                            ->with('portfolio') // Incluye la relación 'portfolio' en la consulta
                                            ->get()
                                            ->mapWithKeys(fn (User $user): array => [
                                                $user->id => $user->name.' '.$user->first_name.' '.$user->last_name
                                            ]);
                                    }elseif(auth()->user()->hasRole('Supervisor')) {
                                        $supervisorId = auth()->user()->id;
                                        $user= User::query()
                                            ->doesntHave('portfolio')
                                            ->where('status',1)
                                            ->where('sede_id',\auth()->user()->sede_id)
                                            ->where('department_id',\auth()->user()->department_id)
                                            ->with('portfolio') // Incluye la relación 'portfolio' en la consulta
                                            ->whereHas('position', function ($query) use ($supervisorId) {
                                                $query->where('supervisor_id', $supervisorId);
                                            })
                                            ->get()
                                            ->mapWithKeys(fn (User $user): array => [
                                                $user->id => $user->name.' '.$user->first_name.' '.$user->last_name
                                            ]);

                                    }else{
                                        $user= User::query()
                                            ->where('id',\auth()->user()->id)
                                            ->get()
                                            ->mapWithKeys(fn (User $user): array => [$user->id => $user->name.' '.$user->first_name .' '.$user->last_name]);
                                    }

                                }
                                    return $user;
                            })
                        ->disabled(fn (string $operation): bool => $operation === 'edit')
                        ->required(),
                    ]),

                Tab::make('Identificación Oficial')
                    ->icon('heroicon-m-identification')
                    ->schema([
                        Forms\Components\FileUpload::make('acta_url')
                            ->label('Acta de nacimiento')
                            ->downloadable(true)
                            ->openable(true)
                            ->previewable(true)
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->disk('sedyco_disk')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->visibility('public')
                            ->default(null),
                        Forms\Components\FileUpload::make('ine_url')
                            ->label('Identificación Oficial Vigente')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->openable()
                            ->previewable('true')
                            ->helperText('Los documentos válidos son: Credencial para votar (INE), Pasaporte o Cédula Profesional.')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->maxSize('2048')
                            ->default(null),
                        Forms\Components\FileUpload::make('curp_url')
                            ->label('CURP')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->openable()
                            ->previewable('true')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                    ])->columns(3),

                Tab::make('Domicilio y Fiscal')
                    ->icon('heroicon-m-home')
                    ->schema([
                        Forms\Components\FileUpload::make('comprobante_domicilio_url')
                            ->label('Comprobante de domicilio')
                            ->helperText('Recibo de luz, agua, teléfono o predial. No mayor a 3 meses.')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->openable()
                            ->previewable('true')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                        Forms\Components\FileUpload::make('rfc_url')
                            ->label('Constancia Situación Fiscal')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->previewable('true')
                            ->openable()
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize('2048')
                            ->helperText('Constancia de Situación Fiscal actualizada')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                    ])->columns(2),

                Tab::make('Información Laboral')
                    ->icon('heroicon-m-briefcase')
                    ->schema([
                        Forms\Components\FileUpload::make('sol_empleo_url')
                            ->label('Solicitud de Empleo')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->previewable('true')
                            ->openable()
                            ->acceptedFileTypes(['image/*','application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                        Forms\Components\FileUpload::make('recomendacion_url')
                            ->label('Carta de recomendación 1')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->openable()
                            ->previewable('true')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                        Forms\Components\FileUpload::make('comprobante_estudios_url')
                            ->label('Comprobante de estudios')
                            ->downloadable('true')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->openable()
                            ->previewable('true')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                    ])->columns(3),

                Tab::make('Seguro Social y Médico')
                    ->icon('heroicon-m-heart')
                    ->schema([
                        Forms\Components\FileUpload::make('cert_medico_url')
                            ->label('Certificado médico')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->openable()
                            ->previewable('true')
                            ->acceptedFileTypes(['image/*','application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                        Forms\Components\FileUpload::make('nss_url')
                            ->label('Número de Seguro Social')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->previewable('true')
                            ->openable()
                            ->acceptedFileTypes(['image/*','application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                        Forms\Components\FileUpload::make('alta_imss_url')
                            ->downloadable('true')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->previewable('true')
                            ->openable()
                            ->hidden(fn (): bool => !auth()->user()->hasAnyRole(['Administrador', 'RH Corp', 'RH','Supervior']))
                            ->label('Alta en el IMSS')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                        Forms\Components\FileUpload::make('modificacion_imss_url')
                            ->downloadable('true')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->previewable('true')
                            ->openable()
                            ->hidden(fn (): bool => !auth()->user()->hasAnyRole(['Administrador', 'RH','RH Corp']))
                            ->label('Modificación en el IMSS')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                        Forms\Components\FileUpload::make('baja_imss_url')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->previewable('true')
                            ->openable()
                            ->hidden(fn (): bool => !auth()->user()->hasAnyRole(['Administrador', 'RH','RH Corp']))
                            ->label('Baja en el IMSS')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                    ])->columns(3),

                Tab::make('Legal y Financiero')
                    ->icon('heroicon-m-scale')
                    ->schema([
                        Forms\Components\FileUpload::make('carta_no_antecedentes_url')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->previewable('true')
                            ->openable()
                            ->label('Carta de no antecedentes penales')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                        Forms\Components\FileUpload::make('retencion_url')
                            ->label('Retención de Crédito Infonavit')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->previewable('true')
                            ->openable()
                            ->acceptedFileTypes(['image/*','application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                        Forms\Components\FileUpload::make('renuncia_url')
                            ->downloadable('true')
                            ->previewable('true')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->openable()
                            ->hidden(fn (): bool => !auth()->user()->hasAnyRole(['Administrador', 'RH','RH Corp']))
                            ->label('Renuncia')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                        Forms\Components\FileUpload::make('finiquito_url')
                            ->disk('sedyco_disk')
                            ->visibility('public')
                            ->downloadable('true')
                            ->previewable('true')
                            ->openable()
                            ->hidden(fn (): bool => !auth()->user()->hasAnyRole(['Administrador', 'RH','RH Corp']))
                            ->label('Finiquito')
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->maxSize('2048')
                            ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                            ->default(null),
                    ])->columns(2),

            ])->columnSpanFull();

        return $form->schema([
            $forms,
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fullName')
                    ->label('Colaborador')
                    ->getStateUsing(fn ($record) =>
                        trim("{$record->user->name} {$record->user->first_name} {$record->user->last_name}")
                    )
                    ->description(fn ($record) => $record->user?->sede?->name ?? 'Sin sede')
                    ->searchable(['user.name', 'user.first_name', 'user.last_name'])
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('progress')
                    ->label('Completitud')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $fields = ['acta_url', 'ine_url', 'curp_url', 'comprobante_domicilio_url', 'rfc_url', 'sol_empleo_url', 'recomendacion_url', 'comprobante_estudios_url', 'cert_medico_url', 'nss_url'];
                        $filled = collect($fields)->filter(fn($f) => !empty($record->{$f}))->count();
                        $percentage = round(($filled / count($fields)) * 100);
                        return $percentage . '%';
                    })
                    ->color(fn (string $state): string => match (true) {
                        (int) $state === 100 => 'success',
                        (int) $state >= 50 => 'warning',
                        default => 'danger',
                    }),

                ColumnGroup::make('Identidad', [
                    Tables\Columns\IconColumn::make('acta_url')
                        ->label('Acta')
                        ->boolean()
                        ->default(false)
                        ->getStateUsing(fn ($record): bool => !empty($record->acta_url)),
                    Tables\Columns\IconColumn::make('curp_url')
                        ->label('CURP')
                        ->boolean()
                        ->default(false)
                        ->getStateUsing(fn ($record): bool => !empty($record->curp_url)),
                    Tables\Columns\IconColumn::make('ine_url')
                        ->label('INE')
                        ->boolean()
                        ->default(false)
                        ->getStateUsing(fn ($record): bool => !empty($record->ine_url)),
                ]),

                ColumnGroup::make('Fiscal y Legal', [
                    Tables\Columns\IconColumn::make('rfc_url')
                        ->label('RFC')
                        ->boolean()
                        ->default(false)
                        ->getStateUsing(fn ($record): bool => !empty($record->rfc_url)),
                    Tables\Columns\IconColumn::make('comprobante_domicilio_url')
                        ->label('Domicilio')
                        ->boolean()
                        ->default(false)
                        ->getStateUsing(fn ($record): bool => !empty($record->comprobante_domicilio_url)),
                    Tables\Columns\IconColumn::make('comprobante_estudios_url')
                        ->label('Estudios')
                        ->boolean()
                        ->default(false)
                        ->getStateUsing(fn ($record): bool => !empty($record->comprobante_estudios_url)),
                    Tables\Columns\IconColumn::make('carta_no_antecedentes_url')
                        ->label('Penales')
                        ->boolean()
                        ->default(false)
                        ->getStateUsing(fn ($record): bool => !empty($record->carta_no_antecedentes_url)),
                ]),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Últ. actualización')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('preview_acta')
                        ->label('Ver Acta')
                        ->icon('heroicon-o-document-text')
                        ->url(fn ($record) => Storage::disk('sedyco_disk')->url($record->acta_url))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => !empty($record->acta_url)),
                    Tables\Actions\Action::make('preview_ine')
                        ->label('Ver INE')
                        ->icon('heroicon-o-identification')
                        ->url(fn ($record) => Storage::disk('sedyco_disk')->url($record->ine_url))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => !empty($record->ine_url)),
                    Tables\Actions\Action::make('preview_curp')
                        ->label('Ver CURP')
                        ->icon('heroicon-o-document-text')
                        ->url(fn ($record) => Storage::disk('sedyco_disk')->url($record->curp_url))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => !empty($record->curp_url)),
                    Tables\Actions\Action::make('preview_rfc')
                        ->label('Ver RFC / Constancia')
                        ->icon('heroicon-o-document-text')
                        ->url(fn ($record) => Storage::disk('sedyco_disk')->url($record->rfc_url))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => !empty($record->rfc_url)),
                    Tables\Actions\Action::make('preview_alta_imss')
                        ->label('Ver Alta IMSS')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->url(fn ($record) => Storage::disk('sedyco_disk')->url($record->alta_imss_url))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => !empty($record->alta_imss_url)),
                ])->label('Ver documentos')
                  ->icon('heroicon-m-eye')
                  ->color('info')
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('Delete')
                    ->label('Eliminar')
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->action(fn (Collection $records) => $records->each->delete())
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar registros')
                    ->modalDescription('¿Estás seguro de que deseas eliminar los registros seleccionados?')
                    ->modalSubmitActionLabel('Eliminar'),
                Tables\Actions\BulkAction::make('exportar')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records) {
                        return Excel::download(
                            new PortfolioExport($records->pluck('id')->toArray()),
                            'portafolio_' . now()->format('Y-m-d_His') . '.xlsx'
                        );
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Exportar Portafolio')
                    ->modalDescription('¿Deseas exportar los registros seleccionados a un archivo Excel?')
                    ->modalSubmitActionLabel('Exportar'),
            ])
            ->headerActions([
               /*
                Tables\Actions\Action::make('downloadPortfolioReport')
                    ->label('Descargar Reporte')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->action(fn () => PortfolioResource::downloadPortfolioReport())
                    ->visible(fn () => auth()->user()->hasAnyRole(['Administrador', 'RH Corp', 'RH'])),
               */
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Si el usuario tiene el rol "Jefe RH", filtrar por su sede_id
                if (auth()->user()->hasRole('RH')) {
                    $users=User::where('sede_id',\auth()->user()->sede_id)->pluck('id');
                    $query->whereIn('user_id', $users);
                }
//                elseif(auth()->user()->hasRole('Jefe de Área')){
//                    $supervisorId = \auth()->user()->position_id;
//                    $users = User::where('status', true)
//                        ->whereNotNull('department_id')
//                        ->whereNotNull('position_id')
//                        ->whereNotNull('sede_id')
//                        ->whereHas('position', function ($query) use ($supervisorId) {
//                            $query->where('supervisor_id', $supervisorId);
//                        })
//                        ->pluck('users.id');
//
//                    $query->whereIn('user_id', $users);
//
//                }
                elseif(auth()->user()->hasAnyRole('Colaborador','Supervisor','Operativo','Gerente')){
                    $query->where('user_id',\auth()->user()->id);
                }
            });
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
            'index' => Pages\ListPortfolios::route('/'),
            'create' => Pages\CreatePortfolio::route('/create'),
            'edit' => Pages\EditPortfolio::route('/{record}/edit'),
        ];
    }
    public static function downloadPortfolioReport()
    {
        return Excel::download(new PortfolioExport(), 'Portafolio_Documentos.xlsx');
    }
}
