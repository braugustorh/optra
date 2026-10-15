<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Question;

class MissingQuestionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $questions = [
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 35,
                'order'               => 5,
                'question'            => '<p class="g3">&nbsp;Considero que las actividades que realizo son peligrosas&nbsp;</p>',
                'created_at'          => '2025-07-23 02:24:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 36,
                'order'               => 6,
                'question'            => '<p class="g3">&nbsp;Por la cantidad de trabajo que tengo debo quedarme tiempo adicional a mi turno&nbsp;</p>',
                'created_at'          => '2025-07-23 02:24:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 36,
                'order'               => 7,
                'question'            => '<p class="g3">&nbsp;Por la cantidad de trabajo que tengo debo trabajar sin parar&nbsp;</p>',
                'created_at'          => '2025-07-23 02:25:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 37,
                'order'               => 10,
                'question'            => '<p class="g3">&nbsp;Mi trabajo requiere que memorice mucha información&nbsp;</p>',
                'created_at'          => '2025-07-23 02:26:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 37,
                'order'               => 12,
                'question'            => '<p class="g3">&nbsp;Mi trabajo exige que atienda varios asuntos al mismo tiempo&nbsp;</p>',
                'created_at'          => '2025-07-23 02:27:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 38,
                'order'               => 13,
                'question'            => '<p class="g3">&nbsp;En mi trabajo soy responsable de cosas de mucho valor&nbsp;</p>',
                'created_at'          => '2025-07-23 02:28:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 38,
                'order'               => 16,
                'question'            => '<p class="g3">&nbsp;Considero que en mi trabajo me piden hacer cosas innecesarias&nbsp;</p>',
                'created_at'          => '2025-07-23 02:30:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 39,
                'order'               => 17,
                'question'            => '<p class="g3">&nbsp;Trabajo horas extras más de tres veces a la semana&nbsp;</p>',
                'created_at'          => '2025-07-23 02:30:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 39,
                'order'               => 18,
                'question'            => '<p class="g3">&nbsp;Mi trabajo me exige laborar en días de descanso, festivos o fines de semana&nbsp;</p>',
                'created_at'          => '2025-07-23 02:31:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 39,
                'order'               => 19,
                'question'            => '<p class="g3">&nbsp;Considero que el tiempo en el trabajo es mucho y perjudica mis actividades familiares o personales&nbsp;</p>',
                'created_at'          => '2025-07-23 02:31:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 39,
                'order'               => 22,
                'question'            => '<p class="g3">&nbsp;Pienso que mis responsabilidades familiares afectan mi trabajo&nbsp;</p>',
                'created_at'          => '2025-07-28 03:48:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 40,
                'order'               => 23,
                'question'            => '<p class="g3">&nbsp;Mi trabajo permite que desarrolle nuevas habilidades&nbsp;</p>',
                'created_at'          => '2025-07-23 02:34:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 40,
                'order'               => 24,
                'question'            => '<p class="g3">&nbsp;En mi trabajo puedo aspirar a un mejor puesto&nbsp;</p>',
                'created_at'          => '2025-07-23 02:34:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 40,
                'order'               => 25,
                'question'            => '<p class="g3">&nbsp;Durante mi jornada de trabajo puedo tomar pausas cuando las necesito&nbsp;</p>',
                'created_at'          => '2025-07-23 02:35:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 40,
                'order'               => 27,
                'question'            => '<p class="g3">&nbsp;Puedo decidir la velocidad a la que realizo mis actividades en mi trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 02:35:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 40,
                'order'               => 28,
                'question'            => '<p class="g3">&nbsp;Puedo cambiar el orden de las actividades que realizo en mi trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 02:36:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 42,
                'order'               => 31,
                'question'            => '<p class="g3">&nbsp;Me informan con claridad cuáles son mis funciones&nbsp;</p>',
                'created_at'          => '2025-07-23 02:37:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 42,
                'order'               => 32,
                'question'            => '<p class="g3">&nbsp;Me explican claramente los resultados que debo obtener en mi trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 02:37:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 42,
                'order'               => 34,
                'question'            => '<p class="g3">&nbsp;Me informan con quién puedo resolver problemas o asuntos de trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 02:38:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 42,
                'order'               => 35,
                'question'            => '<p class="g3">&nbsp;Me permiten asistir a capacitaciones relacionadas con mi trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 02:38:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 42,
                'order'               => 36,
                'question'            => '<p class="g3">&nbsp;Recibo capacitación útil para hacer mi trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 02:38:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 43,
                'order'               => 41,
                'question'            => '<p class="g3">&nbsp;Mi jefe ayuda a solucionar los problemas que se presentan en el trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 02:53:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 44,
                'order'               => 45,
                'question'            => '<p class="g3">&nbsp;Cuando tenemos que realizar trabajo de equipo los compañeros colaboran&nbsp;</p>',
                'created_at'          => '2025-07-23 02:56:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 44,
                'order'               => 46,
                'question'            => '<p class="g3">&nbsp;Mis compañeros de trabajo me ayudan cuando tengo dificultades&nbsp;</p>',
                'created_at'          => '2025-07-23 02:56:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 46,
                'order'               => 57,
                'question'            => '<p class="g3">&nbsp;En mi trabajo puedo expresarme libremente sin interrupciones&nbsp;</p>',
                'created_at'          => '2025-07-23 03:03:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 46,
                'order'               => 58,
                'question'            => '<p class="g3">&nbsp;Recibo críticas constantes a mi persona y/o trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 03:03:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 46,
                'order'               => 59,
                'question'            => '<p class="g3">&nbsp;Recibo burlas, calumnias, difamaciones, humillaciones o ridiculizaciones&nbsp;</p>',
                'created_at'          => '2025-07-23 03:03:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 46,
                'order'               => 63,
                'question'            => '<p class="g3">&nbsp;Me bloquean o impiden las oportunidades que tengo para obtener ascenso o mejora en mi trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 03:05:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 47,
                'order'               => 67,
                'question'            => '<p class="g3">&nbsp;Mi trabajo me exige atender personas muy necesitadas de ayuda o enfermas&nbsp;</p>',
                'created_at'          => '2025-07-23 03:14:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 47,
                'order'               => 68,
                'question'            => '<p class="g3">&nbsp;Para hacer mi trabajo debo demostrar sentimientos distintos a los míos&nbsp;</p>',
                'created_at'          => '2025-07-23 03:14:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 48,
                'order'               => 70,
                'question'            => '<p class="g3">&nbsp;Soy jefe de otros trabajadores:&nbsp;</p>',
                'created_at'          => '2025-07-23 03:16:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 48,
                'order'               => 71,
                'question'            => '<p class="g3">&nbsp;Comunican tarde los asuntos de trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 03:17:00',
            ],
            [
                'evaluations_type_id' => 8,
                'competence_id'       => 48,
                'order'               => 72,
                'question'            => '<p class="g3">&nbsp;Dificultan el logro de los resultados del trabajo&nbsp;</p>',
                'created_at'          => '2025-07-23 03:17:00',
            ],
        ];

        foreach ($questions as $q) {
            Question::updateOrCreate(
                [
                    'evaluations_type_id' => $q['evaluations_type_id'],
                    'competence_id'       => $q['competence_id'],
                    'order'               => $q['order'],
                ],
                [
                    'question'       => $q['question'],
                    'comment'        => $now,
                    'answer_type_id' => 1,
                    'status'         => 1,
                    'created_at'     => $q['created_at'],
                    'updated_at'     => $now,
                ]
            );
        }
    }
}
