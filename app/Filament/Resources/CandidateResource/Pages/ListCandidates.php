<?php

namespace App\Filament\Resources\CandidateResource\Pages;

use App\Filament\Resources\CandidateResource;
use App\Models\Candidate;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListCandidates extends ListRecords
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Candidato')
                ->icon('heroicon-o-user-plus'),
        ];
    }

    /**
     * Tabs de estatus del pipeline: al entrar se muestra "En Proceso" por defecto
     * y cada tab trae su propio contador, como el navegador tipo "shopify" de Filament 3.
     */
    public function getTabs(): array
    {
        return [
            'en_proceso' => Tab::make('En Proceso')
                ->icon('heroicon-o-clock')
                ->badge(Candidate::where('status', Candidate::STATUS_EN_PROCESO)->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Candidate::STATUS_EN_PROCESO)),

            'contratado' => Tab::make('Contratados')
                ->icon('heroicon-o-check-badge')
                ->badge(Candidate::where('status', Candidate::STATUS_CONTRATADO)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Candidate::STATUS_CONTRATADO)),

            'banco_talento' => Tab::make('Banco de Talento')
                ->icon('heroicon-o-archive-box-arrow-down')
                ->badge(Candidate::where('status', Candidate::STATUS_BANCO_TALENTO)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Candidate::STATUS_BANCO_TALENTO)),

            'archivado' => Tab::make('Archivados')
                ->icon('heroicon-o-archive-box')
                ->badge(Candidate::where('status', Candidate::STATUS_ARCHIVADO)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', Candidate::STATUS_ARCHIVADO)),

            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-users')
                ->badge(Candidate::count())
                ->badgeColor('gray'),
        ];
    }

    /**
     * Al ingresar al módulo, por defecto se posiciona en "En Proceso".
     */
    public function getDefaultActiveTab(): string|int|null
    {
        return 'en_proceso';
    }
}
