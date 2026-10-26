<x-filament-widgets::widget>
    <x-filament::section>
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Biblioteca de Documentos
                </h3>
                <x-filament::button
                    href="#"
                    size="sm"
                    color="primary"
                >
                    Ver todos
                </x-filament::button>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Documentos</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $this->getViewData()['totalDocuments'] }}</p>
                        </div>
                    </div>
                </div>

                @foreach($this->getViewData()['categories'] as $category => $count)
                    <div class="bg-gray-100 dark:bg-gray-900 rounded-lg p-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $category }}</p>
                        <p class="text-xl font-semibold text-gray-900 dark:text-white">{{ $count }}</p>
                    </div>
                @endforeach
            </div>

            <!-- Lista de documentos recientes -->
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">Documentos Recientes</h4>
                <div class="space-y-2">
                    @foreach($this->getViewData()['recentDocuments'] as $document)
                        @php
                            $downloadAction = $document['method'] === 'downloadPolicy'
                                ? 'downloadPolicy'
                                : "downloadDocument('" . $document['path'] . "')";
                        @endphp
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    @if($document['type'] === 'PDF')
                                        <svg class="h-6 w-6 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" />
                                        </svg>
                                    @else
                                        <svg class="h-6 w-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" />
                                        </svg>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $document['name'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $document['type'] }} • {{ $document['size'] }}</p>
                                </div>
                            </div>
                            <x-filament::button
                                href="#"
                                size="sm"
                                color="gray"
                                outlined
                                wire:click="{{ $downloadAction }}"
                            >
                                Descargar
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Acciones rápidas -->
            @if(!auth()->user()->hasRole('Colaborador'))

            <div class="border-t dark:border-gray-700 pt-4">
                <div class="flex space-x-2">
                    <x-filament::button
                        href="#"
                        size="sm"
                        color="primary"
                        outlined
                        class="flex-1"
                    >
                        <x-slot name="icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                            </svg>
                        </x-slot>
                        Subir Documento
                    </x-filament::button>

                    <x-filament::button
                        href="#"
                        size="sm"
                        color="gray"
                        outlined
                        class="mx-3"
                    >
                        <x-slot name="icon">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            </svg>
                        </x-slot>
                        Gestionar Categorías
                    </x-filament::button>
                </div>
            </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
