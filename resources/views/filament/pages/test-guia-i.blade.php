<x-filament-panels::page>
    @if($existingResponses)
        <x-filament::section icon="heroicon-s-exclamation-circle" icon-color="warning">
            <x-slot name="heading">
                Cuestionario de Identificación de Acontecimientos Traumáticos Severos
            </x-slot>
            <div class="mb-4">
                <p class="text-xl font-bold text-gray-900 dark:text-white">
                    Ya has completado la Guía de Referencia I - Cuestionario para identificar a los trabajadores que fueron sujetos a acontecimientos traumáticos severos.
                </p>
                <br>
                <p>Gracias por tu participación!</p>
               <!-- <p>
                    Tu bienestar es importante para nosotros. Si tienes alguna pregunta o necesitas apoyo adicional, no dudes en contactar al equipo de Recursos Humanos o llamar a la línea de atención en crisis al
                    <a href="tel:8002900024" class="text-primary-600 hover:underline">
                        <strong>800-290-0024</strong>
                    </a>
                    o al
                    <a href="tel:8009112000" class="text-primary-600 hover:underline">
                        <strong>800-911-2000</strong>
                    </a>.
                </p> -->
            </div>
        </x-filament::section>
    @elseif($page==='welcome')
    <x-filament::section>
        <div class="mb-4">
            <p class="text-xl font-bold text-gray-900 dark:text-white">
                Bienvenido(a) al Cuestionario de Identificación de Acontecimientos Traumáticos Severos
            </p>
            <br>
            <p><strong>Estimado(a) colaborador(a),</strong></p>
            <br>
            <p>
                En Optra, nos preocupamos por tu bienestar y por garantizar un entorno laboral seguro y saludable, en cumplimiento con la
                Norma Oficial Mexicana NOM-035-STPS-2018. Este cuestionario tiene como objetivo identificar si has estado expuesto(a) a
                algún acontecimiento traumático severo relacionado con tu trabajo, como accidentes, situaciones de violencia o eventos que hayan
                impactado tu bienestar.
            </p>
            <br>
            <p>
            <strong>¿Qué debes saber antes de responder?</strong>
            </p>

                <ul class="list-disc list-inside mt-2">
                    <li>Este cuestionario es confidencial y tus respuestas serán tratadas con la máxima privacidad, conforme a las disposiciones de la norma.</li>
                    <li>Tu participación es fundamental para ayudarnos a identificar posibles riesgos psicosociales y garantizar un entorno laboral favorable.</li>
                    <li>Las preguntas son breves y están diseñadas para evaluar si necesitas apoyo adicional, como una evaluación clínica con un profesional de la salud.</li>
                    <li>Por favor, responde con sinceridad. No hay respuestas correctas o incorrectas.</li>

                </ul>

            <br>
            <p>
                <strong>Instrucciones:</strong>
            </p>

                <ul class="list-disc list-inside mt-2">
                    <li>El cuestionario consta de varias secciones que preguntan sobre experiencias específicas y su impacto.</li>
                    <li>Responde cada pregunta marcando la opción que mejor refleje tu situación.</li>
                    <li>Si en cualquier momento necesitas apoyo, puedes contactar al equipo de Recursos Humanos de tu sede.</li>
                    <li>Gracias por tu tiempo y compromiso con este proceso. Tu bienestar es nuestra prioridad.</li>
                </ul>

        </div>
        <div class="flex justify-end">
            <x-filament::button
                color="primary"
                class="mt-4"
                wire:click="navigateSection('next')"
            >
                Comenzar Cuestionario
            </x-filament::button>
        </div>

    </x-filament::section>
    @elseif($page==='Section I')
        <x-filament::section>
            <x-slot name="heading">
                Sección I: Acontecimientos Traumáticos Severos
            </x-slot>
            <x-slot name="description">
                Lee la pregunta y selecciona Sí o No según corresponda a tu situación.
            </x-slot>

            <div class="space-y-6">
                <div>
                    <h3 class="font-semibold text-base mb-2">
                        ¿Ha presenciado o sufrido alguna vez, <span class="text-primary-600">durante o con motivo del trabajo</span> un acontecimiento como los siguientes:
                    </h3>
                    <ul class="list-disc list-inside mb-4 ml-4">
                        <li>Accidente que tenga como consecuencia la muerte, la pérdida de un miembro o una lesión grave?</li>
                        <li>Asalto?</li>
                        <li>Actos violentos que derivaron en lesiones graves?</li>
                        <li>Secuestro?</li>
                        <li>Amenazas?</li>
                        <li>Cualquier otro que ponga en riesgo su vida o salud, y/o la de otras personas?</li>
                    </ul>

                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s1p1" value="si" id="s1p1_si"/>
                            <span>Sí</span>
                        </label>
                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s1p1" value="no" id="s1p1_no"/>
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                </div>
                @error('s1p1')
                    <span class="text-danger-600 text-sm">
                        {{$message}}
                    </span>
                @enderror
            </div>

            <div class="flex justify-between mt-6">
                <x-filament::button
                    color="gray"
                    wire:click="navigateSection('previous')"
                >
                    Anterior
                </x-filament::button>
                <x-filament::button color="primary" wire:click="navigateSection('next')">
                    Siguiente Sección
                </x-filament::button>
            </div>
        </x-filament::section>

    @elseif($page==='Section II')
        <x-filament::section>
            <x-slot name="heading">
                Sección II: Recuerdos persistentes sobre el acontecimiento (durante el último mes)
            </x-slot>
            <x-slot name="description">
                Lee cada pregunta y selecciona Sí o No según corresponda a tu situación.
            </x-slot>

            <div class="space-y-6">
                <div>
                    <p class="mb-3 font-bold">
                        ¿Ha tenido recuerdos recurrentes sobre el acontecimiento que le provocan malestares?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio
                                wire:model.live="s2p1"
                                value="si"
                                id="s2p1_si"
                                />
                            <span>
                                Sí
                                </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio
                                wire:model.live="s2p1"
                                value="no"
                                id="s2p1_no"
                            />
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s2p1')
                    <span class="text-danger-600 text-sm">
                                {{ $message }}
                            </span>
                    @enderror

                </div>

                <div>
                    <p class="mb-3 font-semibold">¿Ha tenido sueños de carácter recurrente sobre el acontecimiento, que le producen malestar?</p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s2p2" value="si" id="s2p2_si"/>
                            <span>
                                Sí
                                </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s2p2" id="s2p2_no" value="no"/>
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s2p2')
                    <span class="text-danger-600 text-sm">
                                {{ $message }}
                            </span>
                    @enderror
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <x-filament::button color="gray" wire:click="navigateSection('previous')">
                    Anterior
                </x-filament::button>
                <x-filament::button color="primary" wire:click="navigateSection('next')">
                    Siguiente Sección
                </x-filament::button>
            </div>
        </x-filament::section>
    @elseif($page==='Section III')
        <x-filament::section>
            <x-slot name="heading">
                Sección III: Esfuerzo por evitar circunstancias parecidas o asociadas al acontecimiento (durante el último
                mes)
            </x-slot>
            <x-slot name="description">
                Lee cada pregunta y selecciona Sí o No según corresponda a tu situación.
            </x-slot>

            <div class="space-y-6">
                <div>
                    <p class="mb-3 font-bold">
                        ¿Se ha esforzado por evitar todo tipo de sentimientos, conversaciones o situaciones que
                        le puedan recordar el acontecimiento?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s3p1" value="si" id="s3p1_si" />
                            <span>
                                Sí
                            </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s3p1" value="no" id="s3p1_no" />
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s3p1')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror

                </div>

                <div>
                    <p class="mb-3 font-semibold">
                        ¿Se ha esforzado por evitar todo tipo de actividades, lugares o personas que motivan
                        recuerdos del acontecimiento?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s3p2" value="si" id="s3p2_si" />
                            <span>
                                Sí
                            </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s3p2" value="no" id="s3p2_no" />
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s3p2')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
                <div>
                    <p class="mb-3 font-semibold">
                        ¿Ha tenido dificultad para recordar alguna parte importante del evento?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s3p3" value="si" id="s3p3_si" />
                            <span>
                                Sí
                                </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s3p3" value="no" id="s3p3_no" />
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s3p3')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
                <div>
                    <p class="mb-3 font-semibold">
                        ¿Ha disminuido su interés en sus actividades cotidianas?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s3p4" value="si"/>
                            <span>
                                Sí
                                </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s3p4" value="no" id="s3p4_no" />
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s3p4')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
                <div>
                    <p class="mb-3 font-semibold">
                        ¿Se ha sentido usted alejado o distante de los demás?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s3p5" value="si" id="s3p5_si" />
                            <span>
                                Sí
                                </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s3p5" value="no" id="s3p5_no" />
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s3p5')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
                <div>
                    <p class="mb-3 font-semibold">
                        ¿Ha notado que tiene dificultad para expresar sus sentimientos?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s3p6" value="si" id="s3p6_si" />
                            <span>
                                Sí
                                </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s3p6" value="no" id="s3p6_no" />
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s3p6')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
                <div>
                    <p class="mb-3 font-semibold">
                        ¿Ha tenido la impresión de que su vida se va a acortar, que va a morir antes que otras
                        personas o que tiene un futuro limitado?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s3p7" value="si" id="s3p7_si" />
                            <span>
                                Sí
                                </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s3p7" value="no" id="s3p7_no" />
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s3p7')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>

            <div class="flex justify-between mt-6">
                <x-filament::button
                    color="gray"
                    wire:click="navigateSection('previous')"
                >
                    Anterior
                </x-filament::button>
                <x-filament::button
                    color="primary"
                    wire:click="navigateSection('next')"
                >
                    Siguiente Sección
                </x-filament::button>
            </div>
        </x-filament::section>
    @elseif($page==='Section IV')
        <x-filament::section>
            <x-slot name="heading">
                Sección IV: Afectación (durante el último mes):
            </x-slot>
            <x-slot name="description">
                Lee cada pregunta y selecciona Sí o No según corresponda a tu situación.
            </x-slot>
            <div class="space-y-6">
                <div>
                    <p class="mb-3 font-bold">
                        ¿Ha tenido usted dificultades para dormir?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s4p1" value="si" id="s4p1_si" />
                            <span>
                                Sí
                            </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s4p1" value="no" id="s4p1_no" />
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s4p1')
                        <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror

                </div>

                <div>
                    <p class="mb-3 font-semibold">
                        ¿Ha estado particularmente irritable o le han dado arranques de coraje?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s4p2" value="si" id="s4p2_si" />
                            <span>
                                Sí
                                </span>
                        </label>
                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s4p2" value="no" id="s4p2_no" />
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s4p2')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
                <div>
                    <p class="mb-3 font-semibold">
                        ¿Ha tenido dificultad para concentrarse?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s4p3" value="si" id="s4p3_si"/>
                            <span>
                                Sí
                                </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s4p3" value="no" id="s4p3_no"/>
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s4p3')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
                <div>
                    <p class="mb-3 font-semibold">
                        ¿Ha estado nervioso o constantemente en alerta?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s4p4" value="si" id="s4p4_si" />
                            <span>
                                Sí
                                </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s4p4" value="no" id="s4p4_no"/>
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s4p4')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
                <div>
                    <p class="mb-3 font-semibold">
                        ¿Se ha sobresaltado fácilmente por cualquier cosa?
                    </p>
                    <div class="flex items-center space-x-4">
                        <label>
                            <x-filament::input.radio wire:model.live="s4p5" value="si" id="s4p5_si"/>
                            <span>
                                Sí
                            </span>
                        </label>

                        <span>&nbsp;&nbsp;&nbsp;</span>
                        <label>
                            <x-filament::input.radio wire:model.live="s4p5" value="no" id="s4p5_no"/>
                            <span>
                                No
                            </span>
                        </label>
                    </div>
                    @error('s4p5')
                    <span class="text-danger-600 text-sm">
                            {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>
            <div class="flex justify-between mt-6">
                <x-filament::button
                    color="gray"
                    wire:click="navigateSection('previous')"
                >
                    Anterior
                </x-filament::button>
                <x-filament::button
                    color="primary"
                    wire:click="navigateSection('next')"
                >
                    Finalizar Cuestionario
                </x-filament::button>
            </div>

        </x-filament::section>
    @elseif($page==='Finish')
        <x-filament::section>
            <div class="mb-4">
                <p class="text-xl font-bold text-gray-900 dark:text-white">
                    Gracias por completar el cuestionario.
                </p>
                <br>
                <p>
                    Agradecemos profundamente tu tiempo y sinceridad al completar la Guía de Referencia I - Cuestionario para identificar a los trabajadores que fueron sujetos a acontecimientos traumáticos severos. Para Optra tu colaboración es esencial para promover un entorno laboral seguro y saludable.
                </p>

                <br>
                <!--
                <p>
                    {--!!$finalMessage!!--}
                </p>
                <br> -->
                <p>
                    {{--
                    @if($reqAtt)
                    Si tienes alguna pregunta o necesitas apoyo inmediato, no dudes en contactar al equipo de Recursos Humanos o llamar a la linea de atención en crisis al
                        <a href="tel:8002900024" class="text-primary-600 hover:underline">
                            <strong>800-290-0024</strong>
                        </a>
                        o al
                        <a href="tel:8009112000" class="text-primary-600 hover:underline">
                            <strong>800-911-2000</strong>
                        </a>. Estamos aquí para ayudarte.
                    @endif
                    --}}
                </p>
                <br>
                <p>
                    Gracias por tu compromiso, tu bienestar es nuestra prioridad.
                </p>
            </div>
            <div class="flex justify-end">
                <x-filament::button
                    color="primary"
                    class="mt-4"
                    icon="fas-check"
                    wire:loading.attr="disabled"
                    wire:target="finish"
                    wire:click="finish"
                    disbled="{{$flagFinish ? 'true' : 'false'}}"
                >
                    <div class="flex items-center space-x-4">
                        <x-filament::icon
                            icon="fas-spinner"
                            wire:loading.class="animate-spin"
                            class="h-4 w-4 hidden display-inline"
                            wire:loading.remove.class="hidden"
                            wire:target="finish"/>
                        <span> Salir </span>
                    </div>
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif

</x-filament-panels::page>
