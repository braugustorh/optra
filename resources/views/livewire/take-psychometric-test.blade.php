<div x-data="{
        showHelpModal: false,
        showGlosarioModal: false,
        elapsedSeconds: @js($accumulatedSeconds),
        timerInterval: null,
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
            s = Math.floor(s); // Asegurar entero (diffInSeconds puede retornar float)
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

        // Inicia el cronómetro de serie con un límite NUEVO (primera vez que comienza la sección)
        startSeriesTimer(limit) {
            if (this.seriesTimerInterval) clearInterval(this.seriesTimerInterval);
            this.seriesTimeRemaining = limit;
            this._runSeriesCountdown();
        },

        // Reanuda el cronómetro de serie desde el tiempo RESTANTE ya calculado por el servidor
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
                    @this.call('timeUpForSeries');
                }
            }, 1000);
        },

        // Detiene el timer de serie explícitamente (llamado desde PHP via dispatch)
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
        {{-- ======================================================== --}}
        {{-- PANTALLA 1: TARJETA DE BIENVENIDA E INSTRUCCIONES        --}}
        {{-- ======================================================== --}}
        <div class="max-w-2xl mx-auto px-4 sm:px-6">

            <div class="bg-white shadow-2xl sm:rounded-2xl overflow-hidden">
                {{-- Encabezado de color --}}
                <div class="bg-indigo-600 px-6 py-10 sm:p-12 text-center">
                    <h2 class="text-3xl font-extrabold text-white tracking-tight">
                        {{ $testName }}
                    </h2>
                    <p class="mt-3 text-indigo-100 text-lg font-medium">Instrucciones de la evaluación</p>
                </div>

                {{-- Cuerpo de instrucciones --}}
                <div class="px-4 py-8 sm:p-12 bg-gray-50 text-center sm:text-left">
                    <div class="prose prose-indigo prose-lg text-gray-700 mx-auto leading-relaxed text-justify">
                        {!! nl2br(e($instructions)) !!}
                    </div>

                    {{-- Botón de acción principal --}}
                    <div class="mt-10 flex justify-center">
                        <button wire:click="startTest"
                                class="inline-flex items-center px-8 py-4 border border-transparent text-lg font-bold rounded-full text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Comenzar Evaluación
                            <svg class="ml-3 -mr-1 h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    @elseif($showSeriesInstructions)
        {{-- ======================================================== --}}
        {{-- PANTALLA INTERMEDIA: INSTRUCCIONES DE SERIE/SECCIÓN       --}}
        {{-- ======================================================== --}}
        <div class="max-w-2xl mx-auto px-4 sm:px-6">
            <div class="bg-white shadow-2xl sm:rounded-2xl overflow-hidden">
                <div class="bg-indigo-500 px-6 py-10 sm:p-12 text-center">
                    <h2 class="text-3xl font-extrabold text-white tracking-tight">
                        {{ $seriesName }}
                    </h2>
                    @if($seriesTimeLimitSeconds > 0)
                    <p class="mt-3 text-indigo-100 text-lg font-medium flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Tiempo límite: {{ $seriesTimeLimitSeconds / 60 }} minutos
                    </p>
                    @endif
                </div>

                <div class="px-4 py-8 sm:p-12 bg-gray-50 text-center sm:text-left">
                    <div class="prose prose-indigo text-gray-700 mx-auto leading-relaxed text-justify">
                        {!! nl2br(e($seriesInstructions)) !!}
                    </div>

                    <div class="mt-10 flex justify-center">
                        <button wire:click="startSeries"
                                class="inline-flex items-center px-8 py-4 border border-transparent text-lg font-bold rounded-full text-white bg-indigo-600 hover:bg-indigo-700 shadow-lg transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Comenzar Sección
                        </button>
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- ======================================================== --}}
        {{-- PANTALLA 2: EL EXAMEN ACTUAL                             --}}
        {{-- ======================================================== --}}
        <div class="max-w-4xl mx-auto px-4 sm:px-6">

            {{-- HEADER: Barra de progreso + Botones --}}
            <div class="mb-6">
                {{-- Fila superior: info izquierda + controles derecha --}}
                <div class="flex flex-wrap justify-between items-center gap-y-2 text-sm font-medium text-gray-500 mb-2">

                    {{-- Izquierda: pregunta actual + timers --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-gray-700 font-semibold whitespace-nowrap">
                            Pregunta {{ $currentQuestionIndex + 1 }} de {{ $totalQuestions }}
                        </span>

                        {{-- Timer total transcurrido --}}
                        <span class="inline-flex items-center gap-1 bg-gray-100 text-gray-700 px-3 py-1 rounded-full font-mono text-xs font-semibold whitespace-nowrap">
                            <svg class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="formatTime(elapsedSeconds)">00:00</span>
                        </span>

                        {{-- Timer de serie (solo cuando la serie tiene límite de tiempo) --}}
                        @if($timeRemainingForSeries !== null)
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full font-mono text-xs font-bold whitespace-nowrap"
                              :class="seriesTimeRemaining <= 30 ? 'bg-red-100 text-red-700 animate-pulse' : 'bg-amber-100 text-amber-700'">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Serie:&nbsp;<span x-text="formatTime(seriesTimeRemaining)">00:00</span>
                        </span>
                        @endif
                    </div>

                    {{-- Derecha: botones + porcentaje --}}
                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- BOTÓN GLOSARIO (solo Cleaver) --}}
                        @if($isCleaver)
                        <button @click="showGlosarioModal = true" type="button"
                                style="color: #d97706; background-color: #fffbeb;"
                                class="inline-flex items-center hover:opacity-80 px-3 py-1 rounded-full transition-opacity text-xs font-semibold">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            Glosario
                        </button>
                        @endif

                        {{-- BOTÓN INSTRUCCIONES --}}
                        <button @click="showHelpModal = true" type="button"
                                class="inline-flex items-center text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-full transition-colors text-xs font-semibold">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Instrucciones
                        </button>

                        <span class="font-bold text-gray-700 text-xs whitespace-nowrap">
                            {{ round((($currentQuestionIndex) / $totalQuestions) * 100) }}%
                        </span>
                    </div>
                </div>

                {{-- Barra de Progreso --}}
                <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500 ease-out"
                         style="width: {{ (($currentQuestionIndex) / $totalQuestions) * 100 }}%"></div>
                </div>
            </div>

            {{-- LA PREGUNTA (Tu código intacto) --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden border border-gray-100">
                <div class="px-4 py-6 sm:p-8">
                    <div wire:key="question-idx-{{ $currentQuestionIndex }}">
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold text-indigo-600 bg-indigo-50 uppercase tracking-wide mb-4">
                            Pregunta {{ $currentQuestionIndex + 1 }}
                        </span>

                        <h3 class="text-xl leading-7 font-semibold text-gray-900 mb-8">
                            {{ $question->question }}
                        </h3>

                        @if ($errors->any())
                            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700 font-medium">Debe seleccionar una respuesta para continuar.</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                    <div class="mt-4">

                        @switch($question->answer_type_id)

                            @case(2)
                                <div class="space-y-4">
                                    @foreach($question->answers as $ans)
                                        <label wire:key="q-idx-{{ $currentQuestionIndex }}-ans-{{ $ans->id }}"
                                               class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition {{ isset($answers[$question->id]) && $answers[$question->id] == $ans->id ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500' : 'border-gray-200' }}">
                                            <div class="me-3 flex items-center h-5">
                                                <input type="radio"
                                                       wire:model="answers.{{ $question->id }}"
                                                       name="question_{{ $question->id }}"
                                                       value="{{ $ans->id }}"
                                                       class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                            </div>
                                            <div class="text-sm">
                                                <span class="font-medium text-gray-700">{{ $ans->text }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                @error("answers.{$question->id}") <span class="text-red-500 text-xs font-bold">{{ $message }}</span> @enderror
                                @break

                            @case(4)

                                <div class="flex space-x-4 justify-center mt-6">
                                    @foreach($question->answers as $ans)
                                        <label wire:key="q-idx-{{ $currentQuestionIndex }}-ans-{{ $ans->id }}" class="cursor-pointer">
                                            <input type="radio"
                                                   wire:model="answers.{{ $question->id }}"
                                                   name="question_{{ $question->id }}"
                                                   id="ans_radio_{{ $ans->id }}"
                                                   value="{{ $ans->id }}"
                                                   class="sr-only peer">
                                            <span class="px-6 py-3 rounded-md border text-sm font-medium transition-colors block
                                                peer-checked:bg-indigo-600 peer-checked:text-white peer-checked:border-indigo-600 peer-checked:shadow-md
                                                bg-white text-gray-700 border-gray-300 hover:bg-gray-50">
                                                {{ $ans->text }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error("answers.{$question->id}") <div class="text-center mt-2 text-red-500 text-xs font-bold">{{ $message }}</div> @enderror
                                @break

                            @case(5)

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Palabra</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Más (+)</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Menos (-)</th>
                                        </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($question->answers as $ans)
                                            <tr wire:key="cleaver-idx-{{ $currentQuestionIndex }}-ans-{{ $ans->id }}">
                                                <td class="px-3.5 pr-10 py-3.5 whitespace-nowrap text-sm font-medium text-gray-900">
                                                    {{ $ans->text }}
                                                </td>

                                                <td class="pl-6 pr-3.5 py-3.5 whitespace-nowrap text-center">
                                                    <input type="radio"
                                                           wire:model="answers.{{ $question->id }}.most"
                                                           value="{{ $ans->id }}"
                                                           name="most_group_{{ $question->id }}"
                                                           class="ms-6 focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300">
                                                </td>

                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <input type="radio"
                                                           wire:model="answers.{{ $question->id }}.least"
                                                           value="{{ $ans->id }}"
                                                           name="least_group_{{ $question->id }}"
                                                           class="focus:ring-red-500 h-4 w-4 text-red-600 border-gray-300">
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                    <div class="mt-2 text-center">
                                        @error("answers.{$question->id}.most") <span class="block text-red-500 text-xs font-bold">Selecciona la opción MÁS.</span> @enderror
                                        @error("answers.{$question->id}.least") <span class="block text-red-500 text-xs font-bold">Selecciona la opción MENOS.</span> @enderror
                                    </div>
                                </div>
                                @break
                        @endswitch
                    </div>
                </div>
            </div>

                {{-- FOOTER / BOTÓN SIGUIENTE --}}
                <div class="bg-gray-50 px-4 py-4 sm:px-8 sm:flex sm:flex-row-reverse border-t border-gray-100">
                    <button wire:click="nextQuestion"
                            wire:loading.attr="disabled"
                            class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-bold rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 sm:w-auto sm:text-sm transition-colors">
                        <span wire:loading.remove>
                            {{ $currentQuestionIndex < $totalQuestions - 1 ? 'Siguiente Pregunta' : 'Finalizar Evaluación' }}
                        </span>
                        <span wire:loading>Procesando...</span>
                        <svg wire:loading.remove class="ml-2 -mr-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ======================================================== --}}
    {{-- MODAL FLOTANTE DE AYUDA (Se muestra al darle al botón ?) --}}
    {{-- ======================================================== --}}
    <div x-show="showHelpModal" style="display: none;" class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">

            {{-- Fondo oscuro transparente --}}
            <div x-show="showHelpModal"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showHelpModal = false" aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            {{-- Tarjeta del Modal --}}
            <div x-show="showHelpModal"
                 x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">
                                Instrucciones
                            </h3>
                            <div class="mt-4 text-sm text-gray-600 prose prose-indigo leading-relaxed">
                                {!! nl2br(e($instructions)) !!}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="showHelpModal = false"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Entendido, continuar
                    </button>
                </div>
            </div>
        </div>
    </div>
        <!-- DEBUG: Agrega esto temporalmente en la vista -->
    {{-- ======================================================== --}}
    {{-- MODAL GLOSARIO CLEAVER                                   --}}
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
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">

                {{-- Cabecera del modal --}}
                <div style="background-color: #f59e0b;" class="px-6 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-6 h-6" style="color:#ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <h3 class="text-lg font-bold" style="color:#ffffff;">Glosario — Pregunta {{ $currentQuestionIndex + 1 }}</h3>
                    </div>
                    <button @click="showGlosarioModal = false" style="color:#ffffff;" class="hover:opacity-75 transition-opacity">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Cuerpo del modal --}}
                <div class="px-6 py-4 bg-white">
                    @php $grupoActual = $glosario[$currentQuestionIndex + 1] ?? []; @endphp
                    @if($grupoActual)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($grupoActual as $item)
                        <div class="flex items-start gap-3 rounded-lg px-4 py-3" style="background-color:#fffbeb; border:1px solid #fde68a;">
                            <span class="font-bold text-gray-800 min-w-[8rem] text-sm">{{ $item['frase'] }}</span>
                            <span class="text-gray-500 text-sm leading-snug">{{ $item['definicion'] }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-400 text-sm text-center py-4">No hay definiciones para esta pregunta.</p>
                    @endif
                </div>

                <div class="px-6 py-3 flex justify-end border-t border-gray-100 bg-gray-50">
                    <button type="button" @click="showGlosarioModal = false"
                            style="background-color:#f59e0b; color:#ffffff;"
                            class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-sm font-medium hover:opacity-90 focus:outline-none">
                        Cerrar glosario
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- BLOQUE DEBUG ELIMINADO --}}
</div>



