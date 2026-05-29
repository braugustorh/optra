<?php

namespace App\Filament\Pages;

use App\Models\PsychometricEvaluation;
use App\Models\User;
use App\Models\EvaluationUserAnswer;
use Filament\Pages\Page;

class TakeInternalEvaluation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.take-internal-evaluation';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'mis-evaluaciones/contestar/{record}';
    protected static ?string $title = 'Evaluación Psicométrica';

    // --- PROPIEDADES ---
    public $evaluation;
    public $currentQuestionIndex = 0;
    public $totalQuestions = 0;
    public $answers = [];
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
    public int $accumulatedSeconds = 0;

    // --- GLOSARIO CLEAVER ---
    public bool $isCleaver = false;

    public function mount($record)
    {
        $this->evaluation = PsychometricEvaluation::findOrFail($record);

        // BLINDAJE DE SEGURIDAD: Validar que la prueba pertenezca al usuario logueado
        if ($this->evaluation->evaluable_id !== auth()->id() || $this->evaluation->evaluable_type !== User::class) {
            abort(403, 'No tienes permiso para acceder a esta evaluación.');
        }

        // Si ya está completada, lo regresamos
        if ($this->evaluation->status === 'completed') {
            return redirect(MyPsychometricEvaluations::getUrl());
        }

        $this->totalQuestions = $this->evaluation->evaluationType->questions()->count();
        $this->testName = $this->evaluation->evaluationType->name ?? 'Evaluación Psicométrica';
        $this->loadInstructions();

        // Detectar si es prueba Cleaver
        $this->isCleaver = $this->evaluation->evaluations_type_id == 11;

        // Calcular segundos acumulados de pruebas anteriores ya completadas del mismo batch
        $this->accumulatedSeconds = $this->evaluation->getAccumulatedSecondsByToken();
    }

    public function loadInstructions()
    {
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
            case 13: // Terman-Merril
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

    public function startTest()
    {
        $this->showWelcome = false;

        // Reseteamos la serie actual para garantizar que aparezcan las instrucciones
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
        $this->evaluation->refresh();

        // Disparar evento para iniciar el timer en Alpine
        $this->dispatch('test-started');
    }

    public function getCurrentQuestion()
    {
        // IMPORTANTE: ordenar por competence_id PRIMERO para que todas las preguntas
        // de la Serie I (competence 56) aparezcan antes que las de Serie II (57), etc.
        $question = $this->evaluation->evaluationType
            ->questions()
            ->with('answers', 'competence')
            ->orderBy('competence_id', 'asc')
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->skip($this->currentQuestionIndex)
            ->take(1)
            ->first();

        // Lógica de Series (Secciones): detectar cambio de serie
        if ($question && $question->competence_id) {
            $competence = $question->competence;

            if ((int) $this->evaluation->current_series_id !== (int) $competence->id) {
                // Modo "mostrar instrucciones de serie"
                $this->showSeriesInstructions = true;
                $this->currentSeriesId = $competence->id;
                $this->seriesName = $competence->name;
                $this->seriesInstructions = $competence->instructions ?? 'Por favor, conteste la siguiente sección.';
                $this->seriesTimeLimitSeconds = (int) (($competence->time_limit_minutes ?? 0) * 60);

                $this->dispatch('stop-series-timer');

                return $question;
            } else {
                $this->showSeriesInstructions = false;

                // Validar tiempo restante anti-trampa
                if ($competence->time_limit_minutes > 0 && $this->evaluation->current_series_started_at) {
                    $elapsedSinceStart = now()->diffInSeconds($this->evaluation->current_series_started_at, false);
                    $limit = (int) ($competence->time_limit_minutes * 60);

                    if ($elapsedSinceStart >= $limit) {
                        $this->timeRemainingForSeries = 0;
                        if ($elapsedSinceStart > $limit + 10) {
                            $this->evaluation->update([
                                'is_invalidated'      => true,
                                'invalidated_reason'  => 'Tiempo límite excedido intencionalmente (posible manipulación de temporizador).',
                            ]);
                            abort(403, 'Tiempo excedido. Evaluación invalidada.');
                        }
                    } else {
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
            'current_series_id'         => $this->currentSeriesId,
            'current_series_started_at' => now(),
        ]);

        $this->evaluation->refresh();

        $this->showSeriesInstructions = false;

        if ($this->seriesTimeLimitSeconds > 0) {
            $this->dispatch('series-started', ['limit' => $this->seriesTimeLimitSeconds]);
        }
    }

    public function timeUpForSeries()
    {
        $this->evaluation->refresh();

        // GUARD: Si no hay serie activa en BD, el timeout ya fue procesado
        if (!$this->evaluation->current_series_id || !$this->evaluation->current_series_started_at) {
            return;
        }

        $activeSeriesCompetenceId = (int) $this->evaluation->current_series_id;

        // Auto-completar con NULL las preguntas no contestadas de la serie activa
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
            if ($q->answer_type_id == 5) {
                EvaluationUserAnswer::create([
                    'psychometric_evaluation_id' => $this->evaluation->id,
                    'question_id' => $q->id,
                    'answer_id'   => null,
                    'attribute'   => 'MOST',
                ]);
                EvaluationUserAnswer::create([
                    'psychometric_evaluation_id' => $this->evaluation->id,
                    'question_id' => $q->id,
                    'answer_id'   => null,
                    'attribute'   => 'LEAST',
                ]);
            } else {
                EvaluationUserAnswer::create([
                    'psychometric_evaluation_id' => $this->evaluation->id,
                    'question_id' => $q->id,
                    'answer_id'   => null,
                    'attribute'   => null,
                ]);
            }
            $this->currentQuestionIndex++;
        }

        // Limpiar serie actual
        $this->evaluation->update([
            'current_series_id'         => null,
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

        // Validar si la pregunta ya pasó de tiempo (anti-trampa backend)
        if ($this->timeRemainingForSeries !== null && $this->timeRemainingForSeries <= 0) {
            $this->timeUpForSeries();
            return;
        }

        // 1. VALIDACIÓN

        if ($question->answer_type_id == 5) {
             $this->validate([
                "answers.{$question->id}.most" => 'required',
                "answers.{$question->id}.least" => 'required|different:answers.'.$question->id.'.most',
                // Agregué 'different' para UX: no deberías ser MÁS y MENOS lo mismo.
            ], [
                "answers.{$question->id}.most.required" => 'Debes seleccionar una opción en la columna MÁS (+).',
                "answers.{$question->id}.least.required" => 'Debes seleccionar una opción en la columna MENOS (-).',
                "answers.{$question->id}.least.different" => 'No puedes seleccionar la misma palabra para MÁS y MENOS.'
            ]);
        } else {
             $this->validate([
                "answers.{$question->id}" => 'required',
            ], [
                "answers.{$question->id}.required" => 'Debe seleccionar una respuesta para continuar.'
            ]);
        }

        // 2. GUARDADO
        $this->saveAnswerToDb($question);

        // 3. AVANCE
        if ($this->currentQuestionIndex < $this->totalQuestions - 1) {
            $this->currentQuestionIndex++;
            $this->answers = [];

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

        return redirect(MyPsychometricEvaluations::getUrl());
    }

    public function getViewData(): array
    {
        $glosario = $this->isCleaver ? config('cleaver.glosario') : [];
        return [
            'question'              => $this->showWelcome ? null : $this->getCurrentQuestion(),
            'glosario'              => $glosario,
            'showSeriesInstructions' => $this->showSeriesInstructions,
            'seriesName'            => $this->seriesName,
            'seriesInstructions'    => $this->seriesInstructions,
            'seriesTimeLimitSeconds' => $this->seriesTimeLimitSeconds,
            'timeRemainingForSeries' => $this->timeRemainingForSeries,
        ];
    }
}
