<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Psicométrico SEDYCO — {{ $meta['candidato'] ?? 'Candidato' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-break { page-break-before: always; }
        }
    </style>
</head>
<body class="bg-gray-50">

{{-- Barra de acciones flotante (no se imprime) --}}
<div class="no-print fixed bottom-8 right-8 z-50 flex flex-col gap-3">
    <a href="{{ route('psychometric.report.pdf', $reportKey) }}"
       target="_blank"
       class="flex items-center justify-center gap-2 px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-900 focus:ring-offset-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Descargar PDF
    </a>
    <button onclick="window.print()"
            class="flex items-center justify-center gap-2 px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-xl shadow-md border border-gray-200 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-200 focus:ring-offset-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        Imprimir
    </button>
    <a href="{{ url()->previous() }}"
       class="flex items-center justify-center gap-2 px-6 py-3 bg-white hover:bg-gray-50 text-gray-500 hover:text-gray-900 font-medium rounded-xl shadow-sm border border-gray-200 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-gray-200 focus:ring-offset-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver
    </a>
</div>

@if($aiAvailable)
    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- REPORTE COMPLETO CON IA                                        --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <x-psychometric-report
        :reportData="$reportData"
        :candidateData="$candidateData"
        :psychometricResults="$psychometricResults"
        :competencias="$competencias"
        :meta="$meta"
        :reportKey="$reportKey"
    />
@else
    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- REPORTE SIN IA (solo resultados psicométricos)                  --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <x-psychometric-results-only
        :psychometricResults="$psychometricResults"
        :candidateData="$candidateData"
        :competencias="$competencias"
        :meta="$meta"
        :reportKey="$reportKey"
    />
@endif

</body>
</html>

