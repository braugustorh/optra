@props(['psychometricResults', 'candidateData', 'competencias', 'meta', 'reportKey'])

<div class="min-h-screen bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <header class="no-print sticky top-0 z-50 backdrop-blur-xl bg-white/5 border-b border-white/10">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-teal-500 rounded-2xl flex items-center justify-center transform rotate-3">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-white">Resultados Psicométricos</h1>
                        <p class="text-blue-300 text-sm">Sin Análisis de IA • {{ $candidateData['puesto'] ?? 'General' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-8 space-y-8">
        <div class="relative bg-white/10 backdrop-blur-xl rounded-3xl p-8 border border-white/20">
            <div class="flex items-center space-x-4 mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-400 to-teal-400 rounded-2xl flex items-center justify-center text-2xl font-bold text-white">
                    {{ strtoupper(substr($candidateData['name'] ?? 'C', 0, 2)) }}
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-white">{{ $candidateData['name'] ?? 'Desconocido' }}</h2>
                    <p class="text-blue-200 text-lg">{{ $candidateData['puesto'] ?? 'General' }}</p>
                </div>
            </div>
             <div class="flex space-x-4 mt-4">
                <span class="px-4 py-2 bg-blue-500/20 text-blue-200 rounded-full text-sm border border-blue-500/30">
                    Generado el: {{ \Carbon\Carbon::parse($meta['generado_en'] ?? now())->format('d/m/Y') }}
                </span>
                <span class="px-4 py-2 bg-green-500/20 text-green-200 rounded-full text-sm border border-green-500/30">
                    Tiempo de pruebas: {{ $meta['tiempo_total'] ?? 'N/A' }}
                </span>
            </div>
        </div>

        <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-2xl p-6 flex gap-4 no-print">
            <div class="mt-1"><svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
            <div>
                <h4 class="text-yellow-400 font-semibold text-lg">Información sobre el reporte</h4>
                <p class="text-yellow-200/80">Este reporte incluye únicamente los resultados nativos de las pruebas psicométricas y las competencias calculadas algorítmicamente del modelo SEDYCO, debido a que no se pudo generar el análisis avanzado basado en Inteligencia Artificial.</p>
            </div>
        </div>

        @if(!empty($competencias))
        <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 border border-white/20">
            <h3 class="text-2xl font-bold text-white mb-6 border-b border-white/10 pb-4">Competencias SEDYCO v1.1</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($competencias as $comp)
                    <div class="bg-slate-800/80 rounded-xl p-4 border border-slate-700/50 flex flex-col justify-between h-full">
                        <div class="flex items-start justify-between mb-3">
                            <span class="text-3xl">{{ $comp['icono'] }}</span>
                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 shadow-inner
                                 @if($comp['nivel']==='strong') bg-teal-500/20 text-teal-400 border border-teal-500/30
                                 @elseif($comp['nivel']==='moderate') bg-amber-500/20 text-amber-400 border border-amber-500/30
                                 @else bg-rose-500/20 text-rose-400 border border-rose-500/30 @endif">
                                <span class="text-sm font-bold">{{ $comp['puntaje'] }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-200 leading-tight">{{ $comp['nombre'] }}</span>
                            <span class="text-xs font-semibold
                                @if($comp['nivel']==='strong') text-teal-400
                                @elseif($comp['nivel']==='moderate') text-amber-400
                                @else text-rose-400 @endif">{{ $comp['etiqueta'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($psychometricResults as $resultado)
            <div class="bg-white/5 backdrop-blur-sm rounded-3xl p-6 border border-white/10 group">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-xl font-bold text-white">{{ $resultado['prueba'] }}</h4>
                    <span class="text-xs px-3 py-1 bg-blue-500/20 text-blue-300 border border-blue-500/30 rounded-full">
                        {{ $resultado['tiempo'] }}
                    </span>
                </div>

                @if(isset($resultado['resultados']['summary']))
                    <p class="text-blue-100 font-semibold mb-4">{{ $resultado['resultados']['summary'] }}</p>
                @endif

                <div class="text-gray-300 text-sm space-y-2">
                    <p>Los resultados detallados de esta prueba se encuentran registrados en el sistema.</p>
                    <p><i>Visualización de datos crudos optimizada para exportación.</i></p>
                </div>
            </div>
            @endforeach
        </div>
    </main>
</div>
