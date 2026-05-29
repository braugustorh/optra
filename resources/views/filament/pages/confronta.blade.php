<x-filament-panels::page>
    @if($showTable && $differences->isNotEmpty())
        <div class="mb-4 flex justify-between items-center">
            <h2 class="text-xl font-bold">Diferencias Encontradas: {{ $differences->count() }}</h2>
            <x-filament::button wire:click="clearResults" color="gray">
                Cargar Nuevos Archivos
            </x-filament::button>
        </div>
        {{ $this->table }}
    @else
    <x-filament::section>
            <div class="my-8 p-8 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-900 rounded-xl shadow-sm border border-blue-100 dark:border-gray-700">
                <div class="text-center space-y-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 dark:bg-blue-900 mb-4">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>

                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                        ¡Bienvenido al Módulo de Confronta!
                    </h3>

                    <p class="text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                        Este módulo te permite comparar dos fuentes de datos IMSS y Nomina para identificar diferencias de manera rápida entre sus datos.
                    </p>

                    <div class="mt-6 space-y-3 text-left max-w-xl mx-auto bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm">
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold p-3">1</span>
                            <p class="text-gray-700 dark:text-gray-300">
                                <strong>Carga los archivos:</strong> Haz clic en "Cargar Archivos" y selecciona los dos documentos Excel que deseas comparar.
                            </p>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold p-3">2</span>
                            <p class="text-gray-700 dark:text-gray-300">
                                <strong>Procesa los datos:</strong> El sistema analizará automáticamente ambos archivos.
                            </p>
                        </div>

                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold p-3"> 3</span>
                            <p class="text-gray-700 dark:text-gray-300">
                                <strong>Revisa las inconsistencias:</strong> Al finalizar, se mostrará una tabla con todas las diferencias detectadas, que podrás filtrar y exportar.
                            </p>
                        </div>
                    </div>

                    <div class="flex justify-center mt-8">
                        <x-filament::button wire:click="openModalForm" color="primary" size="lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                            Cargar Archivos
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif

    <x-filament::modal id="modal-form" width="2xl">
        <x-slot name="heading">
            <h2 class="text-lg font-bold text-gray-800 dark:text-neutral-200">
                {{ __('Carga de Documentos') }}
            </h2>
        </x-slot>

        <x-filament::section>
            <form wire:submit="submit" class="space-y-6">
                {{ $this->form }}
                <div class="flex justify-end gap-2 mt-6">
                    <x-filament::button type="submit" color="primary">
                        Procesar Archivos
                    </x-filament::button>
                    <x-filament::button type="button" wire:click="closeModal" color="gray">
                        Cancelar
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>
    </x-filament::modal>
</x-filament-panels::page>
