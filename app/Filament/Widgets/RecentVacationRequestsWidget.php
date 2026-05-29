<?php

namespace App\Filament\Widgets;

use App\Models\VacationRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentVacationRequestsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                VacationRequest::query()
                    ->latest()
                    ->limit(5)
                    ->when(
                        !Auth::user()->hasRole('RH Corp'),
                        fn (Builder $query) => $query->where(function ($q) {
                            $user = Auth::user();

                            if ($user->hasRole('RH')) {
                                $q->whereHas('user', fn($query) => $query->where('sede_id', $user->sede_id));
                            } else {
                                $q->where('user_id', $user->id);
                            }
                        })
                    )
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Colaborador')
                    ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('Días')
                    ->alignCenter(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'cancelled' => 'Cancelada',
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'secondary' => 'cancelled',
                    ]),
            ])
            ->heading('Solicitudes Recientes')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['RH', 'RH Corp', 'Supervisor', 'Gerente', 'Director']);
    }
}
