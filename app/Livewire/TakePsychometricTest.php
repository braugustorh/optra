<?php

namespace App\Livewire;

use App\Models\PsychometricEvaluation;
use App\Models\Question;
use App\Models\EvaluationUserAnswer;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('components.evaluation-layout')]
class TakePsychometricTest extends Component
{
    public $token;
    public $evaluation;
    public $currentQuestionIndex = 0;
    public $totalQuestions = 0;
    public $answers = [];

    // --- VARIABLES PARA LA UX DE INSTRUCCIONES ---
    public $showWelcome = true;
    public $instructions = '';
    public $testName = '';

    // --- VARIABLES DE SERIES / TERMAN MERRIL ---
    public $showSeriesInstructions = false;
    public $currentSeriesId = null;
    public $seriesInstructions = '';
    public $seriesName = '';
    public $seriesTimeLimitSeconds = 0;
    public $timeRemainingForSeries = null;

    // --- TIMER ---
    public int $accumulatedSeconds = 0; // Segundos de pruebas anteriores del mismo batch

    // --- GLOSARIO CLEAVER ---
    public bool $isCleaver = false;

    public function mount($token)
    {
        $this->token = $token;
        $this->evaluation = PsychometricEvaluation::getNextPendingByToken($token);

        if (!$this->evaluation) {
            return redirect()->route('evaluation.landing', ['token' => $token]);
        }

        // Si la prueba fue invalidada, redirigir
        if ($this->evaluation->is_invalidated) {
            abort(403, 'Esta prueba ha sido invalidada. ' . $this->evaluation->invalidated_reason);
        }

        $this->totalQuestions = $this->evaluation->evaluationType->questions()->count();

        // Cargamos el nombre y las instrucciones de la prueba
        $this->testName = $this->evaluation->evaluationType->name ?? 'Evaluación Psicométrica';
        $this->loadInstructions();

        // Detectar si es prueba Cleaver
        $this->isCleaver = $this->evaluation->evaluations_type_id == 11;

        // Calcular segundos acumulados de pruebas anteriores ya completadas
        $this->accumulatedSeconds = $this->evaluation->getAccumulatedSecondsByToken();
    }

    /**
     * Carga el texto exacto de las instrucciones según el ID de la prueba
     */
    public function loadInstructions()
    {
        // NOTA: Verifica que estos IDs coincidan con los de tu base de datos (evaluations_types)
        // Usamos los mismos que vimos en tu PsychometricScoringService (10=Moss, 11=Cleaver, 12=Kostick, 9=MossWess)

        switch ($this->evaluation->evaluations_type_id) {
            case 10: // Moss
                $this->instructions = "Para cada uno de los problemas siguientes, se sugieren cuatro respuestas. Seleccione la respuesta que usted considere más acertada. Solo podrá marcar una opción de respuesta.";
                break;
            case 9: // Moss Wess
                $this->instructions = "A continuación encontrará unas frases relacionadas con el trabajo. Aunque están pensadas para muy distintos ambientes laborales, es posible que algunas no se ajusten del todo al lugar donde usted trabaja. Trate de acomodarlas a su propio caso y decida si son verdaderas o falsas en relación con su centro de trabajo.\n\nEn las frases, el jefe es la persona de autoridad (coordinador, encargado, supervisor, Director, etc.) con quien usted se relaciona. La palabra empleado se utiliza en sentido general, aplicado a todos los que forman parte del personal del centro o empresa.\n\nSi cree que la frase, aplicada a su lugar de trabajo, es verdadera o casi siempre verdadera, seleccione la opción de V (verdadero). Si cree que la frase es falsa o casi siempre falsa, seleccione la opción F (falso).";
                break;
            case 11: // Cleaver
                $this->instructions = "Las palabras descriptivas que verá a continuación se encuentran agrupadas en series de cuatro, examine las palabras de cada serie y marque en la columna \"MÁS\" de la palabra que mejor describa su forma de ser o de comportarse. Después marque en la palabra que menos lo describa o se acerque a su forma de ser, bajo la columna de \"MENOS\". Solo podrá seleccionar una opción para MÁS y una para MENOS en cada serie de palabras.";
                break;
            case 12: // Kostick
                $this->instructions = "Hay 90 pares de frases, usted debe elegir de cada par, aquella que más se asemeje a su forma de ser o de pensar. A veces tendrá la impresión de que ninguna frase se asemeja a su manera de ser, o al contrario, que ambas lo hacen, en cualquier caso usted debe optar por una de las dos.\n\nLea a continuación cada una de las afirmaciones y conteste de acuerdo a sus preferencias. No existe límite de tiempo, sin embargo no se detenga mucho tiempo en contestar, sea espontáneo y sincero con sus respuestas.";
                break;
            case 13: // Por si acaso tienes otro Terman
                $this->instructions = "Información importante antes de comenzar:

El Test de Terman-Merrill consta de 10 secciones, cada una enfocada en una competencia diferente.

• Instrucciones: Antes de iniciar cada sección, verás las indicaciones específicas y un ejemplo.

• Tiempo límite: Cada serie tiene un tiempo determinado. Al agotarse, la plataforma guardará tus respuestas automáticamente y avanzarás a la siguiente sección.

• Recomendación: Te sugerimos responder de forma fluida y asegurar un espacio libre de interrupciones (duración aproximada: 45 minutos).";
                break;
            default:
                $this->instructions = "Lea cuidadosamente cada pregunta y seleccione la opción que considere correcta. No hay respuestas buenas ni malas, sea lo más sincero posible.";
                break;
        }
    }

    /**
     * Oculta la bienvenida y registra la hora de inicio real
     */
    public function startTest()
    {
        $this->showWelcome = false;

        // Siempre reseteamos la serie actual para garantizar que aparezcan las instrucciones
        // de la primera sección (aunque el evaluado haya iniciado antes y quedado a medias)
        $updateData = [
            'current_series_id'         => null,
            'current_series_started_at' => null,
        ];

        if ($this->evaluation->status === 'assigned') {
            $updateData['status']     = 'started';
            $updateData['started_at'] = now();
        }

        $this->evaluation->update($updateData);
        $this->evaluation->refresh(); // Refrescar para que la comparación en getCurrentQuestion() sea precisa

        // Disparar evento para iniciar el timer en Alpine
        $this->dispatch('test-started');
    }

    public function getCurrentQuestion()
    {
        // En primer lugar obtenemos la pregunta que tocaría.
        // IMPORTANTE: ordenar por competence_id PRIMERO para que todas las preguntas
        // de la Serie I (competence 56) aparezcan antes que las de Serie II (57), etc.
        // Sin esto, como cada serie reinicia su campo 'order' desde 1, la query intercalaría series.
        $question = $this->evaluation->evaluationType
            ->questions()
            ->with('answers', 'competence')
            ->orderBy('competence_id', 'asc')
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->skip($this->currentQuestionIndex)
            ->take(1)
            ->first();

        // Lógica de Series (Secciones): Si la pregunta tiene competencia y es diferente a la serie actual en progreso
        if ($question && $question->competence_id) {
            $competence = $question->competence;

            // Comparación con cast a int para evitar problemas de tipo string vs int
            // (PDO en algunos entornos devuelve los IDs como cadenas de texto)
            if ((int) $this->evaluation->current_series_id !== (int) $competence->id) {
                // Modo "mostrar instrucciones de serie"
                $this->showSeriesInstructions = true;
                $this->currentSeriesId = $competence->id;
                $this->seriesName = $competence->name;
                $this->seriesInstructions = $competence->instructions ?? 'Por favor, conteste la siguiente sección.';
                $this->seriesTimeLimitSeconds = (int) (($competence->time_limit_minutes ?? 0) * 60);

                // Detener el timer de Alpine cuando aparecen instrucciones.
                // Cubre el caso donde el usuario terminó todas las preguntas de la serie
                // de forma manual ANTES de que el timer expirara y el timer seguiría corriendo.
                $this->dispatch('stop-series-timer');

                return $question; // Retornamos la pregunta pero en la vista mostraremos las instrucciones en base a la flag
            } else {
                $this->showSeriesInstructions = false;

                // Si estamos en la serie correcta, validamos tiempo restante (anti-trampa)
                if ($competence->time_limit_minutes > 0 && $this->evaluation->current_series_started_at) {
                    $elapsedSinceStart = now()->diffInSeconds($this->evaluation->current_series_started_at, false);
                    $limit = (int) ($competence->time_limit_minutes * 60);

                    if ($elapsedSinceStart >= $limit) {
                        // El tiempo en el backend ya pasó formalmente, fuerza el timeUp
                        $this->timeRemainingForSeries = 0;
                        // Opcionalmente invalidar por trampa si el desfase es exagerado
                        if ($elapsedSinceStart > $limit + 10) { // Si tardó 10 seg más de lo permitido en el submit (Trampa de JS)
                             $this->evaluation->update([
                                 'is_invalidated' => true,
                                 'invalidated_reason' => 'Tiempo límite excedido intencionalmente (posible manipulación de temporizador).'
                             ]);
                             abort(403, 'Tiempo excedido. Evaluación invalidada.');
                        }
                    } else {
                        // Casteo a int para evitar que diffInSeconds devuelva float con microsegundos
                        $this->timeRemainingForSeries = (int) round($limit - $elapsedSinceStart);
                    }
                } else {
                    $this->timeRemainingForSeries = null;
                }
            }
        } else {
            $this->showSeriesInstructions = false;
        }

        return $question;
    }

    public function startSeries()
    {
        $this->evaluation->update([
            'current_series_id' => $this->currentSeriesId,
            'current_series_started_at' => now(),
        ]);

        // Refrescar el modelo para que la comparación en el siguiente render
        // use valores enteros frescos desde la BD y no haya desincronía de tipos
        $this->evaluation->refresh();

        $this->showSeriesInstructions = false;

        // Disparar evento para reiniciar reloj de la serie localmente en JS
        if ($this->seriesTimeLimitSeconds > 0) {
            $this->dispatch('series-started', ['limit' => $this->seriesTimeLimitSeconds]);
        }
    }

    public function timeUpForSeries()
    {
        // Recargar desde BD para obtener estado fresco (evitar caché de Eloquent)
        $this->evaluation->refresh();

        // GUARD: Si no hay serie activa en BD, el timeout ya fue procesado anteriormente
        // o la serie terminó naturalmente (el usuario respondió todo antes del límite).
        // En cualquier caso, no hay preguntas pendientes que guardar como null.
        if (!$this->evaluation->current_series_id || !$this->evaluation->current_series_started_at) {
            return;
        }

        // FIX CRÍTICO: Usar current_series_id de la BD, NO el competence_id de getCurrentQuestion().
        // Si el usuario avanzó más allá de la serie activa, getCurrentQuestion() retornaría
        // la primera pregunta de la SIGUIENTE serie, y takeUntil() usaría ese competence_id
        // guardando null en preguntas que NO corresponden, saltándose series enteras.
        $activeSeriesCompetenceId = (int) $this->evaluation->current_series_id;

        // Auto-completar con NULL las preguntas NO contestadas de la serie activa
        $remainingQuestions = $this->evaluation->evaluationType
            ->questions()
            ->orderBy('competence_id', 'asc')
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->skip($this->currentQuestionIndex)
            ->take(PHP_INT_MAX)
            ->get()
            ->takeUntil(function ($item) use ($activeSeriesCompetenceId) {
                return (int) $item->competence_id !== $activeSeriesCompetenceId;
            });

        foreach ($remainingQuestions as $q) {
            // Guardamos respuestas nulas
            if ($q->answer_type_id == 5) {
                EvaluationUserAnswer::create([
                    'psychometric_evaluation_id' => $this->evaluation->id,
                    'question_id' => $q->id,
                    'answer_id' => null,
                    'attribute' => 'MOST'
                ]);
                EvaluationUserAnswer::create([
                    'psychometric_evaluation_id' => $this->evaluation->id,
                    'question_id' => $q->id,
                    'answer_id' => null,
                    'attribute' => 'LEAST'
                ]);
            } else {
                EvaluationUserAnswer::create([
                    'psychometric_evaluation_id' => $this->evaluation->id,
                    'question_id' => $q->id,
                    'answer_id' => null,
                    'attribute' => null
                ]);
            }
            $this->currentQuestionIndex++;
        }

        // Limpiar serie actual
        $this->evaluation->update([
            'current_series_id' => null,
            'current_series_started_at' => null,
        ]);

        $this->answers = [];

        if ($this->currentQuestionIndex >= $this->totalQuestions) {
            $this->finishEvaluation();
        }
    }

    public function nextQuestion()
    {
        $question = $this->getCurrentQuestion();

        // Validar si la pregunta ya pasó de tiempo y JS no lo atrapó (Anti trampa)
        if ($this->timeRemainingForSeries !== null && $this->timeRemainingForSeries <= 0) {
            $this->timeUpForSeries();
            return;
        }

        // ==========================================
        // 1. VALIDACIÓN: Evitar que avancen sin contestar
        // ==========================================
        if ($question->answer_type_id == 5) {
            // Validación especial para Cleaver (Debe tener MOST y LEAST)
            $this->validate([
                "answers.{$question->id}.most" => 'required',
                "answers.{$question->id}.least" => 'required',
            ], [
                "answers.{$question->id}.most.required" => 'Debes seleccionar una opción en la columna MÁS (+).',
                "answers.{$question->id}.least.required" => 'Debes seleccionar una opción en la columna MENOS (-).'
            ]);
        } else {
            // Validación normal (Moss, Kostick, etc.)
            $this->validate([
                "answers.{$question->id}" => 'required',
            ], [
                "answers.{$question->id}.required" => 'Debe seleccionar una respuesta para continuar.'
            ]);
        }

        // ==========================================
        // 2. Si pasa la validación, procedemos a guardar
        // ==========================================
        $this->saveAnswerToDb($question);

        // ==========================================
        // 3. Avanzamos o terminamos
        // ==========================================
        if ($this->currentQuestionIndex < $this->totalQuestions - 1) {
            $this->currentQuestionIndex++;
            $this->answers = []; // Limpiamos para la siguiente
            // render() llamará a getCurrentQuestion() que detectará automáticamente
            // el cambio de competencia y pondrá showSeriesInstructions = true si corresponde
        } else {
            $this->finishEvaluation();
        }
    }

    public function saveAnswerToDb($question)
    {
        if ($question->answer_type_id == 5) {
            $data = $this->answers[$question->id];
            EvaluationUserAnswer::create([
                'psychometric_evaluation_id' => $this->evaluation->id,
                'question_id' => $question->id,
                'answer_id' => $data['most'],
                'attribute' => 'MOST'
            ]);
            EvaluationUserAnswer::create([
                'psychometric_evaluation_id' => $this->evaluation->id,
                'question_id' => $question->id,
                'answer_id' => $data['least'],
                'attribute' => 'LEAST'
            ]);
        } else {
            EvaluationUserAnswer::create([
                'psychometric_evaluation_id' => $this->evaluation->id,
                'question_id' => $question->id,
                'answer_id' => $this->answers[$question->id],
                'attribute' => null
            ]);
        }
    }

    public function finishEvaluation()
    {
        $elapsedSeconds = 0;
        if ($this->evaluation->started_at) {
            $elapsedSeconds = max(0, (int) now()->diffInSeconds($this->evaluation->started_at, false));
        }

        $this->evaluation->update([
            'status'          => 'completed',
            'completed_at'    => now(),
            'progress'        => 100,
            'elapsed_seconds' => $elapsedSeconds,
        ]);

        return redirect()->route('evaluation.landing', ['token' => $this->token]);
    }

    public function render()
    {
        // Si estamos en la bienvenida, no necesitamos cargar la pregunta aún
        $question = $this->showWelcome ? null : $this->getCurrentQuestion();

        $glosario = $this->isCleaver ? config('cleaver.glosario') : [];

        return view('livewire.take-psychometric-test', [
            'question' => $question,
            'glosario' => $glosario,
        ]);
    }
}
