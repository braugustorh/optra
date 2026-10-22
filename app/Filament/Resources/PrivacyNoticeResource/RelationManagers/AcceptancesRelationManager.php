<?php

namespace App\Filament\Resources\PrivacyNoticeResource\RelationManagers;

use App\Models\PrivacyNoticeAcceptance;
use App\Models\RazonSocial;
use App\Models\Sede;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class AcceptancesRelationManager extends RelationManager
{
    protected static string $relationship = 'acceptances';

    protected static ?string $title = 'Usuarios que han Aceptado este Aviso';

    protected static ?string $modelLabel = 'Aceptación';
    protected static ?string $pluralModelLabel = 'Aceptaciones';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('resolved_company_name')
                ->label('Empresa Responsable Aceptada')
                ->readOnly(),
            Forms\Components\TextInput::make('resolved_address')
                ->label('Domicilio Aceptado')
                ->readOnly(),
            Forms\Components\TextInput::make('ip_address')
                ->label('Dirección IP')
                ->readOnly(),
            Forms\Components\DateTimePicker::make('accepted_at')
                ->label('Fecha y Hora')
                ->readOnly(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('accepted_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.employee_code')
                    ->label('Cód. Empleado')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Colaborador')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?: ($record->user?->name ?: '—'))
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('razonSocial.name')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('sede.name')
                    ->label('Sede')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('resolved_company_name')
                    ->label('Empresa Mostrada en Aviso')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('accepted_at')
                    ->label('Fecha de Aceptación')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('razon_social_id')
                    ->label('Filtrar por Razón Social')
                    ->options(fn () => RazonSocial::pluck('name', 'id')->toArray())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('sede_id')
                    ->label('Filtrar por Sede')
                    ->options(fn () => Sede::pluck('name', 'id')->toArray())
                    ->searchable(),
            ])
            ->headerActions([
                // No se crean manualmente para preservar validez legal
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detalles'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Exportar Aceptaciones a Excel'),
                ]),
            ]);
    }
}
