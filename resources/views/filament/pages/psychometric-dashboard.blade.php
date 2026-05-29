<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @foreach($this->getStats() as $stat)
                <x-filament::section class="text-center">
                    <div class="text-3xl font-bold {{
                        $stat['color'] === 'primary' ? 'text-primary-600' :
                        ($stat['color'] === 'success' ? 'text-success-600' :
                        ($stat['color'] === 'warning' ? 'text-warning-600' : 'text-info-600'))
                    }}">
                        {{ $stat['value'] }}
                    </div>
                    <div class="text-sm text-gray-600 font-medium mt-1">
                        {{ $stat['label'] }}
                    </div>
                    @if(isset($stat['description']))
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $stat['description'] }}
                        </div>
                    @endif
                </x-filament::section>
            @endforeach
        </div>

        <!-- Main Content Grid -->
        <div class="grid gap-2 grid-cols-1 sm:grid-cols-3 xl:grid-cols-3">
            <!-- Evaluations Table -->
            <div class="sm:col-span-2">

            {{-- <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex justify-between items-center">
                            <span>Evaluaciones en Curso</span>

                            <!-- Filtros -->
                            <div class="flex gap-2">
                                <button wire:click="applyTypeFilter('{{ User::class }}')"
                                        class="px-3 py-1 text-xs rounded-full border {{ $evaluableTypeFilter === User::class ? 'bg-blue-100 text-blue-800 border-blue-300' : 'bg-gray-100 text-gray-600 border-gray-300' }}">
                                    👥 Colaboradores
                                </button>

                                <button wire:click="applyTypeFilter('{{ Candidate::class }}')"
                                        class="px-3 py-1 text-xs rounded-full border {{ $evaluableTypeFilter === Candidate::class ? 'bg-green-100 text-green-800 border-green-300' : 'bg-gray-100 text-gray-600 border-gray-300' }}">
                                    🎯 Candidatos
                                </button>

                                @if($statusFilter || $typeFilter || $evaluableTypeFilter)
                                    <button wire:click="clearFilters"
                                            class="px-3 py-1 text-xs rounded-full bg-red-100 text-red-800 border border-red-300">
                                        ✕ Limpiar
                                    </button>
                                @endif
                            </div>
                        </div>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progreso</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->getFilteredEvaluations() as $evaluation)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full"
                                                     src="https://ui-avatars.com/api/?name={{ urlencode($evaluation->getEvaluatedName()) }}&color=6366f1&background=e0e7ff"
                                                     alt="">
                                            </div>
                                            <div class="ml-4">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-medium text-gray-900">{{ $evaluation->getEvaluatedName() }}</span>

                                                    <!-- Badge para identificar tipo -->
                                                    @if($evaluation->evaluable_type == User::class)
                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                                👥 Colaborador
                                                            </span>
                                                    @else

                                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                                🎯 Candidato
                                                            </span>
                                                    @endif
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    @if($evaluation->evaluable_type === User::class)
                                                        {{ $evaluation->evaluable->position->name ?? 'Sin posición' }}
                                                    @else
                                                        Puesto: {{ $evaluation->evaluable->position_applied ?? 'N/A' }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                                {{ $evaluation->evaluationType->name }}
                                            </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 h-2 rounded-full"
                                                 style="width: {{ $evaluation->progress ?? 0 }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $evaluation->progress ?? 0 }}%</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                                {{ $evaluation->status === 'completed' ? 'bg-green-100 text-green-800' :
                                                   ($evaluation->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                {{ ucfirst($evaluation->status) }}
                                            </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button class="text-indigo-600 hover:text-indigo-900">Ver</button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
                --}}
                <div class="sm:col-span-2">
                    {{ $this->table }}
                </div>

            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <x-filament::section>
                    <x-slot name="heading">
                        Acciones Rápidas
                    </x-slot>

                    <div class="space-y-3">
                        <x-filament::button
                            color="gray"
                            size="sm"
                            class="w-full justify-start"
                            wire:click="mountAction('assign_evaluation')">
                            📋 Asignar Evaluación
                        </x-filament::button>

                        <x-filament::button
                            color="gray"
                            size="sm"
                            class="w-full justify-start"
                            wire:click="mountAction('evaluate_candidate')">
                            🎯 Evaluar Candidato
                        </x-filament::button>

                        <x-filament::button
                            color="gray"
                            size="sm"
                            class="w-full justify-start"
                            wire:click="mountAction('configuration')">
                            ⚙️ Configuración
                        </x-filament::button>

                        <x-filament::button
                            color="warning"
                            size="sm"
                            class="w-full justify-start"
                            wire:click="mountAction('generate_report')">
                            📈 Generar Reporte General
                        </x-filament::button>
                    </div>
                </x-filament::section>

                <!-- Evaluation Types -->
                <x-filament::section>
                    <x-slot name="heading">
                        Tipos de Evaluación
                    </x-slot>

                    <div class="space-y-3">
                        @foreach($this->getEvaluationTypes() as $type)
                            <div class="flex justify-between items-center">
                                <span class="font-medium text-sm">{{ $type['name'] }}</span>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ $type['count'] }} activas
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
