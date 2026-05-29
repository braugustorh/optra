<x-filament-panels::page>
<div x-data="{
        showGlosarioModal: false,
        elapsedSeconds: @js($accumulatedSeconds),
        timerInterval: null,
        seriesTimeRemaining: 0,
       seriesTimeRemaining: @js($timeRemainingForSeries ?? 0),
        seriesTimerInterval: null,

        init() {
            // Reanudar cronómetro global si la prueba ya estaba en curso (recarga de página)
            @if(!$showWelcome)
            this.startTimer();
            @endif
            // Reanudar cronómetro de serie si hay tiempo restante calculado por servidor (recarga mid-serie)
            @if(!$showWelcome && !$showSeriesInstructions && ($timeRemainingForSeries ?? 0) > 0)
            this.resumeSeriesTimer(this.seriesTimeRemaining);
            @endif
        },

        formatTime(s) {
            if (s <= 0) return '00:00';
            s = Math.floor(s);
            const h = Math.floor(s / 3600);
            const m = Math.floor((s % 3600) / 60);
            const sec = Math.floor(s % 60);
            if (h > 0) {
                return String(h).padStart(2,'0') + ':' + String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
            }
            return String(m).padStart(2,'0') + ':' + String(sec).padStart(2,'0');
        },

        startTimer() {
            if (this.timerInterval) return;
            this.timerInterval = setInterval(() => { this.elapsedSeconds++; }, 1000);
        },

        stopTimer() {
            if (this.timerInterval) { clearInterval(this.timerInterval); this.timerInterval = null; }
        },

        startSeriesTimer(limit) {
            if (this.seriesTimerInterval) clearInterval(this.seriesTimerInterval);
            this.seriesTimeRemaining = limit;
            this._runSeriesCountdown();
        },

        resumeSeriesTimer(remaining) {
            if (this.seriesTimerInterval) clearInterval(this.seriesTimerInterval);
            this.seriesTimeRemaining = remaining;
            this._runSeriesCountdown();
        },

        _runSeriesCountdown() {
            this.seriesTimerInterval = setInterval(() => {
                if (this.seriesTimeRemaining > 0) {
                    this.seriesTimeRemaining--;
                } else {
                    clearInterval(this.seriesTimerInterval);
                    this.seriesTimerInterval = null;
                    $wire.call('timeUpForSeries');
                }
            }, 1000);
        },

        stopSeriesTimer() {
            if (this.seriesTimerInterval) {
                clearInterval(this.seriesTimerInterval);
                this.seriesTimerInterval = null;
            }
            this.seriesTimeRemaining = 0;
        },
    }"
    @test-started.window="startTimer()"
    @series-started.window="startSeriesTimer($event.detail[0].limit)"
    x-on:stop-series-timer.window="stopSeriesTimer()"
>

    @if($showWelcome)
        {{-- PANTALLA DE BIENVENIDA --}}
        <div class="max-w-4xl mx-auto">
            <x-filament::section>
                <div class="text-center py-6">
                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl mb-4">
                        {{ $testName }}
                    </h2>
                    <div class="prose dark:prose-invert max-w-none text-left mb-8 bg-gray-50 dark:bg-gray-800 p-6 rounded-lg">
                        <p class="font-semibold mb-2">Instrucciones:</p>
                        <div class="whitespace-pre-line text-gray-600 dark:text-gray-300">
                            {!! nl2br(e($instructions)) !!}
                        </div>
                    </div>
                    <x-filament::button wire:click="startTest" size="xl">
                        Comenzar Evaluación
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>

    @elseif($showSeriesInstructions)
        {{-- ================================================================ --}}
        {{-- PANTALLA INTERMEDIA: INSTRUCCIONES DE SERIE (Terman-Merril)      --}}
        {{-- ================================================================ --}}
        <div class="max-w-4xl mx-auto">
            <x-filament::section>
                <div class="text-center py-6">
                    {{-- Encabezado con nombre de la serie --}}
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/30 mb-4">
                        <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>

                    <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl mb-2">
                        {{ $seriesName }}
                    </h2>

                    {{-- Tiempo límite de la serie --}}
                    @if($seriesTimeLimitSeconds > 0)
                    <div class="inline-flex items-center gap-2 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 px-4 py-2 rounded-full text-sm font-semibold mb-6">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tiempo límite: {{ $seriesTimeLimitSeconds / 60 }} minutos
                    </div>
                    @endif

                    {{-- Instrucciones de la serie desde el modelo Competence --}}
                    <div class="prose dark:prose-invert max-w-none text-left mb-8 bg-gray-50 dark:bg-gray-800 p-6 rounded-lg">
                        <p class="font-semibold mb-2">Instrucciones de esta sección:</p>
                        <div class="whitespace-pre-line text-gray-600 dark:text-gray-300">
                            {!! nl2br(e($seriesInstructions)) !!}
                        </div>
                    </div>

                    <x-filament::button wire:click="startSeries" size="xl">
                        Comenzar Sección
                    </x-filament::button>
                </div>
            </x-filament::section>
        </div>

    @elseif($question)
        {{-- PANTALLA DE PREGUNTA --}}
        <div class="max-w-4xl mx-auto space-y-6">

            {{-- Barra de Progreso --}}
            <div class="space-y-2">
                <div class="flex justify-between items-center text-sm font-medium text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-3">
                        <span>Pregunta {{ $currentQuestionIndex + 1 }} de {{ $totalQuestions }}</span>
                        {{-- TIMER total transcurrido --}}
                        <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 px-3 py-1 rounded-full font-mono text-xs font-semibold">
                            <svg class="w-3.5 h-3.5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="formatTime(elapsedSeconds)">00:00</span>
                        </span>

                        {{-- Timer de serie (solo cuando la serie tiene límite de tiempo) --}}
                        @if($timeRemainingForSeries !== null)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-mono text-xs font-bold"
                              :class="seriesTimeRemaining <= 30 ? 'bg-red-100 text-red-700 animate-pulse dark:bg-red-900/30 dark:text-red-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Serie:&nbsp;<span >Serie:&nbsp;<span>{{ str_pad((int)($seriesTimeLimitSeconds/60), 2, '0', STR_PAD_LEFT) }}:00</span></span>
                        </span>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- BOTÓN GLOSARIO (solo Cleaver) --}}
                        @if($isCleaver)
                        <button
                            @click="showGlosarioModal = true"
                            type="button"
                            style="color: #d97706; background-color: #fffbeb;"
                            class="inline-flex items-center hover:opacity-80 px-3 py-1 rounded-full text-xs transition-opacity">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            Glosario
                        </button>
                        @endif
                        <button
                            x-data
                            x-on:click="$dispatch('open-modal', { id: 'instructions-modal' })"
                            type="button"
                            class="inline-flex items-center text-primary-600 hover:text-primary-800 bg-primary-50 hover:bg-primary-100 px-3 py-1 rounded-full text-xs transition-colors">
                            Ver instrucciones
                        </button>
                        <span class="font-bold text-gray-700 dark:text-gray-300">
                            {{ round(($currentQuestionIndex / $totalQuestions) * 100) }}%
                        </span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-primary-500 h-2.5 rounded-full transition-all duration-500 ease-out"
                         x-data
                         x-bind:style="'width: ' + ($wire.currentQuestionIndex / {{ $totalQuestions }}) * 100 + '%'">
                    </div>
                </div>
            </div>

            {{-- TARJETA DE PREGUNTA --}}
            <x-filament::section>
                {{-- Encabezado de la pregunta --}}
                <div wire:key="question-header-{{ $currentQuestionIndex }}" class="mb-6">
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold text-primary-600 bg-primary-50 dark:bg-primary-900/30 uppercase tracking-wide mb-4">
                        Pregunta {{ $currentQuestionIndex + 1 }}
                    </span>
                    <h3 class="text-xl leading-7 font-semibold text-gray-900 dark:text-white">
                        {{ $question->question }}
                    </h3>
                </div>

                {{-- Mensajes de error --}}
                @if ($errors->any())
                    <div class="mb-6 bg-danger-50 dark:bg-danger-900/20 border-l-4 border-danger-500 p-4 rounded-r-md">
                        <p class="text-sm text-danger-700 dark:text-danger-400 font-medium">
                            Debe seleccionar una respuesta para continuar.
                        </p>
                    </div>
                @endif

                {{-- OPCIONES DE RESPUESTA según tipo --}}
                <div wire:key="question-answers-{{ $currentQuestionIndex }}">
                    @switch($question->answer_type_id)

                        {{-- TIPO 2: Radio con tarjetas --}}
                        @case(2)
                            {{-- Test de Moss y Kostik --}}
                            <div class="space-y-3 mt-6" wire:key="q-group-{{ $question->id }}">
                                @foreach($question->answers as $ans)
                                    <label wire:key="ans-{{ $currentQuestionIndex }}-{{ $ans->id }}"
                                           class="flex items-start p-4 border rounded-lg cursor-pointer transition-all
                                                  border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800
                                                  has-[:checked]:border-primary-500 has-[:checked]:bg-primary-50 dark:has-[:checked]:bg-primary-900/20 has-[:checked]:ring-1 has-[:checked]:ring-primary-500">

                                        <div class="me-3 flex items-center h-5 mt-0.5">
                                            <input type="radio"
                                                   wire:model="answers.{{ $question->id }}"
                                                   value="{{ $ans->id }}"
                                                   class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                        </div>

                                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ $ans->text }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @break

                        {{-- TIPO 4: Botones en línea --}}
                        @case(4)
                            {{-- Test de Moss Wess--}}
                            <div class="flex flex-wrap gap-3 justify-center mt-6"
                                 wire:key="q-group-{{ $question->id }}"
                                 x-data="{ selected: @entangle('answers.' . $question->id).live }">

                                @foreach($question->answers as $ans)
                                    <label wire:key="ans-{{ $currentQuestionIndex }}-{{ $ans->id }}" class="cursor-pointer">
                                        <input type="radio"
                                               name="q_{{ $question->id }}"
                                               x-model="selected"
                                               wire:model="answers.{{ $question->id }}"
                                               value="{{ $ans->id }}"
                                               class="sr-only">

                                        <span class="px-6 py-3 rounded-lg border text-sm font-medium transition-all block"
                                              :class="selected == '{{ $ans->id }}'
                                                  ? 'bg-primary-600 text-white border-primary-600 shadow-md'
                                                  : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700'">
                                            {{ $ans->text }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            @error("answers.{$question->id}")
                                <p class="mt-2 text-center text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            @break

                        {{-- TIPO 5: Cleaver MÁS / MENOS --}}
                        @case(5)
                            {{-- Test de Cleaver --}}
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Palabra</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-success-600 uppercase tracking-wider">Más (+)</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-danger-600 uppercase tracking-wider">Menos (-)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($question->answers as $ans)
                                            <tr wire:key="cleaver-{{ $currentQuestionIndex }}-{{ $ans->id }}">
                                                <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $ans->text }}
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <input type="radio"
                                                           wire:model="answers.{{ $question->id }}.most"
                                                           value="{{ $ans->id }}"
                                                           class="h-4 w-4 text-success-600 border-gray-300 focus:ring-success-500">
                                                </td>
                                                <td class="px-6 py-4 text-center">
                                                    <input type="radio"
                                                           wire:model="answers.{{ $question->id }}.least"
                                                           value="{{ $ans->id }}"
                                                           class="h-4 w-4 text-danger-600 border-gray-300 focus:ring-danger-500">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                <div class="mt-2 text-center space-y-1">
                                    @error("answers.{$question->id}.most")
                                        <p class="text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                    @error("answers.{$question->id}.least")
                                        <p class="text-sm text-danger-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                            @break

                    @endswitch
                </div>

                {{-- Botón Siguiente --}}
                <div class="mt-6 flex justify-end">
                    <x-filament::button
                        wire:click="nextQuestion"
                        wire:loading.attr="disabled"
                        size="lg">
                        <span wire:loading.remove>
                            {{ $currentQuestionIndex < $totalQuestions - 1 ? 'Siguiente Pregunta' : 'Finalizar Evaluación' }}
                        </span>
                        <span wire:loading>Procesando...</span>
                    </x-filament::button>
                </div>
            </x-filament::section>

        </div>

        {{-- Modal de Instrucciones --}}
        <x-filament::modal id="instructions-modal" width="lg">
            <x-slot name="heading">Instrucciones</x-slot>
            <div class="whitespace-pre-line text-gray-600 dark:text-gray-300 text-sm leading-relaxed">
                {!! nl2br(e($instructions)) !!}
            </div>
        </x-filament::modal>

    @endif

    {{-- ======================================================== --}}
    {{-- MODAL GLOSARIO CLEAVER (solo visible en prueba Cleaver)  --}}
    {{-- ======================================================== --}}
    @if($isCleaver)
    <div x-show="showGlosarioModal" style="display: none;" class="fixed z-50 inset-0 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showGlosarioModal"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showGlosarioModal = false" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showGlosarioModal"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">

                {{-- Cabecera --}}
                <div style="background-color: #f59e0b;" class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" style="color: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <h3 class="text-lg font-bold" style="color: #ffffff;">Glosario — Pregunta {{ $currentQuestionIndex + 1 }}</h3>
                    </div>
                    <button @click="showGlosarioModal = false" style="color: #ffffff;" class="hover:opacity-75 transition-opacity">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Cuerpo con scroll --}}
                <div class="px-6 py-4 bg-white dark:bg-gray-900">
                    @php $grupoActual = $glosario[$currentQuestionIndex + 1] ?? []; @endphp
                    @if($grupoActual)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($grupoActual as $item)
                        <div class="flex items-start gap-3 rounded-lg px-4 py-3" style="background-color: #fffbeb; border: 1px solid #fde68a;">
                            <span class="font-bold text-gray-800 dark:text-gray-200 min-w-[8rem] text-sm">{{ $item['frase'] }}</span>
                            <span class="text-gray-500 dark:text-gray-400 text-sm leading-snug">{{ $item['definicion'] }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-400 text-sm text-center py-4">No hay definiciones para esta pregunta.</p>
                    @endif
                </div>

                <div class="px-6 py-3 flex justify-end border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                    <button type="button" @click="showGlosarioModal = false"
                            style="background-color: #f59e0b; color: #ffffff;"
                            class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-sm font-medium hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2">
                        Cerrar glosario
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>{{-- fin x-data Alpine --}}
</x-filament-panels::page>
