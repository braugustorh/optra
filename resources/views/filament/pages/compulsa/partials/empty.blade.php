<div class="my-4 relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
    {{-- Fondo decorativo --}}
    <div class="absolute inset-0 bg-gradient-to-br from-primary-50 via-white to-indigo-50 dark:from-primary-900/10 dark:via-gray-900 dark:to-indigo-900/10"></div>
    <div class="absolute -top-24 -right-24 w-64 h-64 rounded-full bg-primary-200/40 dark:bg-primary-500/10 blur-3xl"></div>
    <div class="absolute -bottom-24 -left-24 w-64 h-64 rounded-full bg-indigo-200/40 dark:bg-indigo-500/10 blur-3xl"></div>

    <div class="relative px-6 py-12 sm:px-12">
        <div class="text-center space-y-5 max-w-3xl mx-auto">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 shadow-lg shadow-primary-500/30 mb-2 rotate-3 hover:rotate-0 transition-transform duration-300">
                <x-heroicon-o-scale class="w-10 h-10 text-white" />
            </div>

            <h3 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                Bienvenido al módulo de Compulsa
            </h3>

            <p class="text-base sm:text-lg text-gray-600 dark:text-gray-300 max-w-2xl mx-auto leading-relaxed">
                Compara automáticamente las cuotas obrero-patronales entre lo declarado por el patrón
                (SUA + Nómina interna) y lo cobrado por el IMSS (EMA mensual o EBA bimestral).
            </p>

            <div class="pt-2 flex flex-wrap items-center justify-center gap-3">
                <x-filament::button
                    size="lg"
                    color="primary"
                    icon="heroicon-o-plus-circle"
                    wire:click="mountAction('newCompulsa')"
                >
                    Iniciar nueva compulsa
                </x-filament::button>
                <x-filament::button
                    size="lg"
                    color="gray"
                    icon="heroicon-o-clock"
                    wire:click="mountAction('history')"
                >
                    Ver historial
                </x-filament::button>
            </div>
        </div>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-4 max-w-4xl mx-auto">
            <div class="group relative p-5 rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center gap-3 mb-3">
                    <span class="flex-shrink-0 w-10 h-10 rounded-lg bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                        <x-heroicon-o-arrow-up-tray class="w-5 h-5" />
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Paso 1</span>
                </div>
                <p class="font-semibold text-gray-900 dark:text-white mb-1">Nueva compulsa</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Selecciona la sede, el período (mensual o bimestral) y sube EMA/EBA, SUA y tu Nómina interna.
                </p>
            </div>

            <div class="group relative p-5 rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center gap-3 mb-3">
                    <span class="flex-shrink-0 w-10 h-10 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <x-heroicon-o-arrows-right-left class="w-5 h-5" />
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-400">Paso 2</span>
                </div>
                <p class="font-semibold text-gray-900 dark:text-white mb-1">Confronta</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    El sistema cruza los tres archivos por NSS y calcula diferencias aplicando prima de riesgo, tope de 25 UMAs y salario mínimo.
                </p>
            </div>

            <div class="group relative p-5 rounded-xl bg-white/80 dark:bg-gray-800/80 backdrop-blur border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                <div class="flex items-center gap-3 mb-3">
                    <span class="flex-shrink-0 w-10 h-10 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Paso 3</span>
                </div>
                <p class="font-semibold text-gray-900 dark:text-white mb-1">Revisión</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Revisa los resultados lado a lado, marca alertas de tope y exporta el reporte ejecutivo.
                </p>
            </div>
        </div>
    </div>
</div>
