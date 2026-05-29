<?php

namespace App\Services;

use App\Models\PsychometricEvaluation;
use App\Models\EvaluationUserAnswer;

class PsychometricScoringService
{
    public function calculate(PsychometricEvaluation $evaluation)
    {
        // 9=MossWess, 10=Moss, 11=Cleaver, 12=Kostick, 13=Terman-Merril
        switch ($evaluation->evaluations_type_id) {
            case 10:
                return $this->calculateMoss($evaluation);
            case 11:
                return $this->calculateCleaver($evaluation);
            case 12:
                return $this->calculateKostick($evaluation);
            case 9:
                return $this->calculateMossWess($evaluation);
            case 13:
                return $this->calculateTerman($evaluation);
            default:
                return ['error' => 'Tipo de evaluación no soportado. ID: ' . $evaluation->evaluations_type_id];
        }
    }

    /**
     * LÓGICA DE MOSS WESS (Clima Social / Work Environment Scale)  COMPLETO SIN INTERPRETACIÓN
     * Estructura: Preguntas -> Subescalas (10) -> Dimensiones (3) -> Baremos
     */
    private function calculateMossWess($evaluation)
    {
        // =========================================================================
        // PASO 0: TEXTOS DESCRIPTIVOS (INFO DE TU SOLICITUD)
        // =========================================================================

        // A. Descripciones de Dimensiones
        // (Ajusté las llaves para que coincidan con $dimensionsMap de abajo)
        $dimensionesInfo = [
            'Relaciones' => 'Relaciones es una dimensión integrada por las subescalas implicación cohesión y apoyo, que evalúan el grado en los empleados están interesados y comprometidos en su trabajo y el grado en que la dirección apoya a los empleados y les anima a apoyarse unos a otros.',
            'Auto-realización' => 'La dimensión autorrealización u orientación hacia unos objetivos se aprecian por medio de sus escalas autonomía, organización y presión, que evalúan el grado en que se estimula a los empleados a ser autosuficientes y a tomar sus propias decisiones; de importancia que se da a la buena planificación, eficiencia y terminación de las tareas y el grado en que la presión en el trabajo o la urgencia dominan el ambiente laboral.',
            'Estabilidad/Cambio' => 'Estabilidad/ cambio es la dimensión apreciada por las subescuelas claridad, control innovación y comodidad. Estas subescalas evalúan el grado en que los empleados conocen lo que esperan de su tarea diaria y como se les explican las normas y planes de trabajo; el grado en que la dirección utiliza las normas y la presión para controlar a los empleados; la importancia que se da a la variedad, al cambio de las nuevas propuestas y, por ultimo, el grado entorno físico contribuye a crear un ambiente de trabajo agradable.'
        ];

        // B. Información de Subescalas (Nombre y Descripción)
        $subescalasInfo = [
            'IM' => ['nombre' => 'IMPLICACION', 'descripcion' => 'Grado en que los empleados se preocupan por su actividad y se entregan a ella.'],
            'CO' => ['nombre' => 'COHESION', 'descripcion' => 'Grado en que los empleados se ayudan entra si y se muestran amables con los compañeros.'],
            'AP' => ['nombre' => 'APOYO', 'descripcion' => 'Grado en que los jefes ayudan y animan al personal para crear un buen clima social.'],
            'AU' => ['nombre' => 'AUTONOMIA', 'descripcion' => 'Grado en que se anima a los empleados a ser autosuficientes y a tomar iniciativas propias.'],
            'OR' => ['nombre' => 'ORGANIZACIÓN', 'descripcion' => 'Grado en que se subraya una buena planificación, eficiencia y terminación de la tarea.'],
            'PR' => ['nombre' => 'PRESION', 'descripcion' => 'Grado en que la urgencia o la presión en el trabajo domina el ambiente laboral.'],
            'CL' => ['nombre' => 'CLARIDAD', 'descripcion' => 'Grado en que se conocen las expectativas de las tareas diarias y se explican las reglas y planes para el trabajo.'],
            'CN' => ['nombre' => 'CONTROL', 'descripcion' => 'Grado en que los jefes utilizan las reglas y las presiones para tener controlados a los empleados.'],
            'IN' => ['nombre' => 'INNOVACION', 'descripcion' => 'Grado en que se subraya la variedad, el cambio y los nuevos enfoques.'],
            'CF' => ['nombre' => 'COMODIDAD', 'descripcion' => 'Grado en que el ambiente físico contribuyo a crear un ambiente laboral agradable.']
        ];

        // =========================================================================
        // PASO 1: ARRAYS DE CONFIGURACIÓN TÉCNICA
        // =========================================================================

        // 1.1 CLAVE DE RESPUESTAS (CORRECTION KEY)
        $correctionKey = [
            // Subescala 1: Implicación (IM)
            1 => 'V', 11 => 'F', 21 => 'F', 31 => 'V', 41 => 'V', 51 => 'F', 61 => 'V', 71 => 'F', 81 => 'V',
            // Subescala 2: Cohesión (CO)
            2 => 'V', 12 => 'F', 22 => 'V', 32 => 'F', 42 => 'V', 52 => 'V', 62 => 'F', 72 => 'V', 82 => 'F',
            // Subescala 3: Apoyo (AP)
            3 => 'F', 13 => 'V', 23 => 'F', 33 => 'V', 43 => 'F', 53 => 'V', 63 => 'F', 73 => 'V', 83 => 'V',
            // Subescala 4: Autonomía (AU)
            4 => 'F', 14 => 'V', 24 => 'V', 34 => 'V', 44 => 'V', 54 => 'F', 64 => 'V', 74 => 'V', 84 => 'V',
            // Subescala 5: Organización (OR)
            5 => 'V', 15 => 'F', 25 => 'V', 35 => 'V', 45 => 'V', 55 => 'V', 65 => 'V', 75 => 'F', 85 => 'F',
            // Subescala 6: Presión (PR)
            6 => 'V', 16 => 'V', 26 => 'V', 36 => 'F', 46 => 'F', 56 => 'V', 66 => 'F', 76 => 'V', 86 => 'V',
            // Subescala 7: Claridad (CL)
            7 => 'F', 17 => 'V', 27 => 'F', 37 => 'V', 47 => 'F', 57 => 'F', 67 => 'F', 77 => 'F', 87 => 'V',
            // Subescala 8: Control (CN)
            8 => 'V', 18 => 'F', 28 => 'V', 38 => 'V', 48 => 'V', 58 => 'V', 68 => 'V', 78 => 'V', 88 => 'F',
            // Subescala 9: Innovación (IN)
            9 => 'V', 19 => 'V', 29 => 'V', 39 => 'F', 49 => 'F', 59 => 'F', 69 => 'F', 79 => 'V', 89 => 'V',
            // Subescala 10: Comodidad Física (CF)
            10 => 'F', 20 => 'V', 30 => 'F', 40 => 'V', 50 => 'F', 60 => 'V', 70 => 'F', 80 => 'V', 90 => 'V',
        ];

        // 1.2 MAPA DE SUBESCALAS (Preguntas)
        $subscalesMap = [
            'IM' => [1, 11, 21, 31, 41, 51, 61, 71, 81],
            'CO' => [2, 12, 22, 32, 42, 52, 62, 72, 82],
            'AP' => [3, 13, 23, 33, 43, 53, 63, 73, 83],
            'AU' => [4, 14, 24, 34, 44, 54, 64, 74, 84],
            'OR' => [5, 15, 25, 35, 45, 55, 65, 75, 85],
            'PR' => [6, 16, 26, 36, 46, 56, 66, 76, 86],
            'CL' => [7, 17, 27, 37, 47, 57, 67, 77, 87],
            'CN' => [8, 18, 28, 38, 48, 58, 68, 78, 88],
            'IN' => [9, 19, 29, 39, 49, 59, 69, 79, 89],
            'CF' => [10, 20, 30, 40, 50, 60, 70, 80, 90]
        ];

        // 1.3 MAPA DE DIMENSIONES
        $dimensionsMap = [
            'Relaciones' => ['IM', 'CO', 'AP'],
            'Auto-realización' => ['AU', 'OR', 'PR'],
            'Estabilidad/Cambio' => ['CL', 'CN', 'IN', 'CF']
        ];

        // 1.4 TABLA DE BAREMOS SUBESCALAS (Acierto Raw -> Valor Transformado + Categoría)
        $baremosSubescalas = [
            0 => ['IM' => 30, 'CO' => 18, 'AP' => 28, 'AU' => 29, 'OR' => 21, 'PR' => 13, 'CL' => 18, 'CN' => 13, 'IN' => 35, 'CF' => 20, 'CATEGORIA' => 'Deficitaria'],
            1 => ['IM' => 36, 'CO' => 26, 'AP' => 34, 'AU' => 36, 'OR' => 29, 'PR' => 23, 'CL' => 27, 'CN' => 21, 'IN' => 43, 'CF' => 27, 'CATEGORIA' => 'Deficitaria'],
            2 => ['IM' => 42, 'CO' => 34, 'AP' => 40, 'AU' => 43, 'OR' => 36, 'PR' => 32, 'CL' => 37, 'CN' => 30, 'IN' => 50, 'CF' => 34, 'CATEGORIA' => 'Deficitaria'],
            3 => ['IM' => 48, 'CO' => 41, 'AP' => 46, 'AU' => 50, 'OR' => 44, 'PR' => 41, 'CL' => 47, 'CN' => 38, 'IN' => 58, 'CF' => 41, 'CATEGORIA' => 'Deficitaria'],
            4 => ['IM' => 53, 'CO' => 49, 'AP' => 52, 'AU' => 57, 'OR' => 51, 'PR' => 51, 'CL' => 56, 'CN' => 46, 'IN' => 65, 'CF' => 48, 'CATEGORIA' => 'Mala'],
            5 => ['IM' => 59, 'CO' => 57, 'AP' => 58, 'AU' => 64, 'OR' => 59, 'PR' => 60, 'CL' => 66, 'CN' => 54, 'IN' => 73, 'CF' => 55, 'CATEGORIA' => 'Promedio'],
            6 => ['IM' => 65, 'CO' => 64, 'AP' => 64, 'AU' => 71, 'OR' => 66, 'PR' => 69, 'CL' => 76, 'CN' => 62, 'IN' => 80, 'CF' => 62, 'CATEGORIA' => 'Promedio'],
            7 => ['IM' => 71, 'CO' => 72, 'AP' => 70, 'AU' => 77, 'OR' => 73, 'PR' => 79, 'CL' => 85, 'CN' => 70, 'IN' => 88, 'CF' => 69, 'CATEGORIA' => 'Tiende a Buena'],
            8 => ['IM' => 76, 'CO' => 80, 'AP' => 76, 'AU' => 84, 'OR' => 81, 'PR' => 88, 'CL' => 95, 'CN' => 78, 'IN' => 96, 'CF' => 76, 'CATEGORIA' => 'Buena'],
            9 => ['IM' => 82, 'CO' => 87, 'AP' => 82, 'AU' => 91, 'OR' => 88, 'PR' => 97, 'CL' => null, 'CN' => 86, 'IN' => null, 'CF' => 83, 'CATEGORIA' => 'Excelente']
        ];

        // 1.5 BAREMOS DIMENSIONES (Acumulado)
        $baremosDimensions = [
            'Relaciones' => [
                ['max' => 5, 'label' => 'Deficitaria', 'color' => 'red'],
                ['max' => 10, 'label' => 'Mala', 'color' => 'orange'],
                ['max' => 15, 'label' => 'Promedio', 'color' => 'gray'],
                ['max' => 20, 'label' => 'Tiende a Buena', 'color' => 'blue'],
                ['max' => 24, 'label' => 'Buena', 'color' => 'green'],
                ['max' => 99, 'label' => 'Excelente', 'color' => 'green'],
            ],
            'Auto-realización' => [
                ['max' => 4, 'label' => 'Deficitaria', 'color' => 'red'],
                ['max' => 9, 'label' => 'Mala', 'color' => 'orange'],
                ['max' => 14, 'label' => 'Promedio', 'color' => 'gray'],
                ['max' => 18, 'label' => 'Tiende a Buena', 'color' => 'blue'],
                ['max' => 23, 'label' => 'Buena', 'color' => 'green'],
                ['max' => 99, 'label' => 'Excelente', 'color' => 'green'],
            ],
            'Estabilidad/Cambio' => [
                ['max' => 6, 'label' => 'Deficitaria', 'color' => 'red'],
                ['max' => 13, 'label' => 'Mala', 'color' => 'orange'],
                ['max' => 15, 'label' => 'Promedio', 'color' => 'gray'],
                ['max' => 19, 'label' => 'Tiende a Buena', 'color' => 'blue'],
                ['max' => 24, 'label' => 'Buena', 'color' => 'green'],
                ['max' => 99, 'label' => 'Excelente', 'color' => 'green'],
            ],
        ];

        // =========================================================================
        // PASO 2: CÁLCULO DE PUNTOS
        // =========================================================================

        // 2.1 Obtener respuestas
        $userAnswers = EvaluationUserAnswer::where('psychometric_evaluation_id', $evaluation->id)
            ->join('answers', 'evaluation_user_answers.answer_id', '=', 'answers.id')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->select('questions.order', 'answers.text')
            ->get();

        // 2.2 Calcular RAW SCORE por Subescala
        $rawScores = array_fill_keys(array_keys($subscalesMap), 0);

        foreach ($userAnswers as $ans) {
            $qNum = $ans->order;
            if (isset($correctionKey[$qNum])) {
                $expected = $correctionKey[$qNum];
                $userVal = '';
                if (stripos($ans->text, 'V') !== false) $userVal = 'V';
                elseif (stripos($ans->text, 'F') !== false) $userVal = 'F';

                if ($userVal === $expected) {
                    foreach ($subscalesMap as $subKey => $qList) {
                        if (in_array($qNum, $qList)) {
                            $rawScores[$subKey]++;
                            break;
                        }
                    }
                }
            }
        }

        // =========================================================================
        // PASO 2.3: PROCESAR SUBESCALAS (AGREGAMOS NOMBRE Y DESCRIPCIÓN)
        // =========================================================================
        $detailedSubscales = [];

        foreach ($rawScores as $code => $score) {
            // Aseguramos que el score no pase de 9
            $safeScore = min($score, 9);
            $baremosData = $baremosSubescalas[$safeScore] ?? null;

            $category = 'N/A';
            $standardValue = 0;
            $color = 'gray';

            if ($baremosData) {
                $category = $baremosData['CATEGORIA'];
                $standardValue = $baremosData[$code] ?? 0;

                if (str_contains($category, 'Excelente') || str_contains($category, 'Buena')) $color = 'green';
                elseif (str_contains($category, 'Tiende a Buena')) $color = 'blue';
                elseif (str_contains($category, 'Promedio')) $color = 'gray';
                elseif (str_contains($category, 'Mala')) $color = 'orange';
                else $color = 'red';
            }

            // >>> AQUÍ INYECTAMOS TU DATA DE SUBESCALAS <<<
            $info = $subescalasInfo[$code] ?? ['nombre' => $code, 'descripcion' => ''];

            $detailedSubscales[$code] = [
                'name' => $info['nombre'],         // <-- Nombre completo (IMPLICACION)
                'description' => $info['descripcion'], // <-- Descripción
                'raw_score' => $score,
                'standard_score' => $standardValue,
                'category' => $category,
                'color' => $color
            ];
        }

        // =========================================================================
        // PASO 2.4: PROCESAR DIMENSIONES (AGREGAMOS DESCRIPCIÓN)
        // =========================================================================
        $dimensionResults = [];

        foreach ($dimensionsMap as $dimName => $subKeys) {
            $dimTotal = 0;
            foreach ($subKeys as $sub) {
                if (isset($rawScores[$sub])) {
                    $dimTotal += $rawScores[$sub];
                }
            }

            $categoryLabel = 'N/A';
            $categoryColor = 'gray';

            if (isset($baremosDimensions[$dimName])) {
                foreach ($baremosDimensions[$dimName] as $rango) {
                    if ($dimTotal <= $rango['max']) {
                        $categoryLabel = $rango['label'];
                        $categoryColor = $rango['color'];
                        break;
                    }
                }
            }

            // >>> AQUÍ INYECTAMOS TU DATA DE DIMENSIONES <<<
            $dimDesc = $dimensionesInfo[$dimName] ?? '';

            $dimensionResults[$dimName] = [
                'completeName' => $dimName, // Mantenemos compatibilidad con el blade
                'description' => $dimDesc,  // <-- Descripción rica
                'score' => $dimTotal,
                'category' => $categoryLabel,
                'color' => $categoryColor
            ];
        }

        // =========================================================================
        // PASO 3: RETORNO
        // =========================================================================

        return [
            'test_name' => 'Moss Wess (Clima Social)',
            'chart_type' => 'bar_grouped',
            'dimensions' => $dimensionResults,
            'subscales' => $detailedSubscales,
            'summary' => "Perfil de Clima Social basado en " . count($dimensionResults) . " dimensiones."
        ];
    }

    // --- MÉTODOS ANTERIORES (Kostick, Moss, Cleaver) SE MANTIENEN IGUAL ---

    private function calculateKostick($evaluation)
    {
        $answers = EvaluationUserAnswer::where('psychometric_evaluation_id', $evaluation->id)
            ->join('answers', 'evaluation_user_answers.answer_id', '=', 'answers.id')
            ->select('answers.code')
            ->get();

        // Inicializamos las 20 dimensiones en 0 para que la gráfica salga completa
        $scores = [
            'G' => 0, 'L' => 0, 'P' => 0, 'I' => 0, 'T' => 0, 'V' => 0, 'X' => 0, 'S' => 0, 'B' => 0, 'O' => 0,
            'R' => 0, 'D' => 0, 'C' => 0, 'Z' => 0, 'E' => 0, 'K' => 0, 'F' => 0, 'W' => 0, 'N' => 0, 'A' => 0
        ];
        $factoresAgrupados=[
            1=>[
                'Título'=>'Grado de Energía',
                'Dimension'=>['N','G','A']
            ],
            2=>[
                'Titulo'=>'Liderazgo',
                'Dimension'=>['L','P','I']
            ],
            3=>[
                'Titulo'=>'Modo de Vida',
                'Dimension'=>['T','V']
                ],
            4=>[
                'Titulo'=>'Naturaleza Social',
                'Dimension'=>['X','S','B','O'],
                ],
            5=>[
                'Título'=>'Adaptación al Trabajo',
                'Dimension'=>['R','D','C']
                ],
            6=>[
                'Titulo'=>'Relaciones Interpersonales',
                'Dimension'=>['Z','E','K']
            ],
            7=>[
                'Titulo'=>'Subordinación',
                'Dimension'=>['F','W']
            ]

        ];
        $kostickInterpretation= [
            'A' => [
                1 => ['detalle' => 'NECESIDAD DE REALIZAR ( INICIATIVA)', 'glosa' => '1.- Tiene dificultad para terminar lo que inicia, no tiene iniciativa, no encuentra recompensa en el trabajo, realizándose en otros planos.'],
                2 => ['detalle' => 'NECESIDAD DE REALIZAR ( INICIATIVA)', 'glosa' => '2.- Tiende a tener dificultades en terminar lo que inicia, necesita ser presionado para realizar su trabajo.'],
                3 => ['detalle' => 'NECESIDAD DE REALIZAR ( INICIATIVA)', 'glosa' => '3.- No siente preocupación de terminar lo que inicia; no es ambicioso, tiende a no realizarse a través de ejecución de tareas.'],
                4 => ['detalle' => 'NECESIDAD DE REALIZAR ( INICIATIVA)', 'glosa' => '4.- Siente la necesidad de terminar una tarea cuando la inicia; tiene un grado regular de ambición.'],
                5 => ['detalle' => 'NECESIDAD DE REALIZAR ( INICIATIVA)', 'glosa' => '5.- Tiene iniciativa. Tiene un grado de ambición regular; se realiza a través del trabajo.'],
                6 => ['detalle' => 'NECESIDAD DE REALIZAR ( INICIATIVA)', 'glosa' => '6.- Es ambicioso, toma la iniciativa; tiene una necesidad intensa de realizar; tiene el deseo de ser el mejor; fija altos padrones de ejecución.'],
                7 => ['detalle' => 'NECESIDAD DE REALIZAR ( INICIATIVA)', 'glosa' => '7.- Es ambicioso. Tiene una necesidad intensa de realizar, fija padrones de ejecución muy altos para sí y para los otros; siente la necesidad de ser el mejor.'],
                8 => ['detalle' => 'NECESIDAD DE REALIZAR ( INICIATIVA)', 'glosa' => '8.- Es muy ambicioso; necesita ser el mejor; tiene una necesidad exagerada de realizar. Tiende a fijar padrones de ejecución irrealísticamente altos.'],
                9 => ['detalle' => 'NECESIDAD DE REALIZAR ( INICIATIVA)', 'glosa' => '9.- Es exageradamente ambicioso; fija padrones de ejecución extremadamente altos para sí y para los otros, se frustra con facilidad al no conseguir alcanzar los padrones que estipula.'],
            ],
            'B' => [
                1 => ['detalle' => 'NECESIDAD DE PERTENECER A GRUPOS.', 'glosa' => '1.- Independiente, no presta importancia a la participación en grupos. Tiene opiniones y puntos de vista propios, tiende a entrar en dificultad cuando trabaja en equipo.'],
                2 => ['detalle' => 'NECESIDAD DE PERTENECER A GRUPOS.', 'glosa' => '2.- Comportamiento independiente, no se preocupa de estar de acuerdo con los miembros del grupo, no siendo influenciable por las opiniones del mismo. Tiene dificultad en mudar de opinión y de trabajar en equipo.'],
                3 => ['detalle' => 'NECESIDAD DE PERTENECER A GRUPOS.', 'glosa' => '3.- Persona no influenciable por las actitudes y puntos de vista del grupo. es independiente pudiendo entrar en conflicto con las opiniones del grupo.'],
                4 => ['detalle' => 'NECESIDAD DE PERTENECER A GRUPOS.', 'glosa' => '4.- Está en igualdad con el grupo, al mismo tiempo que influencia al grupo puede ceder en sus puntos de vista.'],
                5 => ['detalle' => 'NECESIDAD DE PERTENECER A GRUPOS.', 'glosa' => '5.- Participa del grupo recibiendo cierta influencia, pudiendo influenciarse por las opiniones del grupo.'],
                6 => ['detalle' => 'NECESIDAD DE PERTENECER A GRUPOS.', 'glosa' => '6.- Le agrada escuchar y seguir al grupo. Es influenciables por las actitudes y puntos de vista del grupo.'],
                7 => ['detalle' => 'NECESIDAD DE PERTENECER A GRUPOS.', 'glosa' => '7.- Necesita comportarse de acuerdo con las actitudes y puntos de vista del grupo tendiendo a depender del mismo.'],
                8 => ['detalle' => 'NECESIDAD DE PERTENECER A GRUPOS.', 'glosa' => '8.- Fuertemente influenciable por los puntos de vista y actitudes del grupo. es dependiente de la aprobación del grupo.'],
                9 => ['detalle' => 'NECESIDAD DE PERTENECER A GRUPOS.', 'glosa' => '9.- Es dependiente del grupo. Subordina sus opiniones y actitudes comportándose totalmente de acuerdo con el grupo. Motivado por el trabajo apenas cuando es estimulado por el grupo.'],
            ],
            'C' => [
                1 => ['detalle' => 'TIPO ORGANIZADO', 'glosa' => '1.- Extremadamente desorganizado.'],
                2 => ['detalle' => 'TIPO ORGANIZADO', 'glosa' => '2.- Mínima preocupación de orden y organización.'],
                3 => ['detalle' => 'TIPO ORGANIZADO', 'glosa' => '3.- Poca preocupación de orden y organización.'],
                4 => ['detalle' => 'TIPO ORGANIZADO', 'glosa' => '4.- Poco organizado.'],
                5 => ['detalle' => 'TIPO ORGANIZADO', 'glosa' => '5.- Es una persona con agrado regular de organización.'],
                6 => ['detalle' => 'TIPO ORGANIZADO', 'glosa' => '7/6.- Muy organizado, trata de estar con todo su material siempre en orden.'],
                7 => ['detalle' => 'TIPO ORGANIZADO', 'glosa' => '7/6.- Muy organizado, trata de estar con todo su material siempre en orden.'],
                8 => ['detalle' => 'TIPO ORGANIZADO', 'glosa' => '8.- Extremadamente organizado, No consigue trabajar en ambiente desorganizado.'],
                9 => ['detalle' => 'TIPO ORGANIZADO', 'glosa' => '9.- Exagerada preocupación con el orden y organización, no consigue trabajar en ambiente desorganizado.'],
            ],
            'D' => [
                1 => ['detalle' => 'INTERESADO EN TRABAJAR CON DETALLES', 'glosa' => '1.- No valoriza detalles, corre serie riesgo de no prestar atención a detalles importantes para la corrección de los trabajos.'],
                2 => ['detalle' => 'INTERESADO EN TRABAJAR CON DETALLES', 'glosa' => '2.- Trabaja sin detenerse en detalles, tendiendo a perder detalles importantes para el éxito de su trabajo.'],
                3 => ['detalle' => 'INTERESADO EN TRABAJAR CON DETALLES', 'glosa' => '3.- Poco interés por detalles, prefiriendo la visión del conjunto.'],
                4 => ['detalle' => 'INTERESADO EN TRABAJAR CON DETALLES', 'glosa' => '4.- Interés personal regular por detalles.'],
                5 => ['detalle' => 'INTERESADO EN TRABAJAR CON DETALLES', 'glosa' => '5.- Buena capacidad de ver detalles y trabajar con ellos.'],
                6 => ['detalle' => 'INTERESADO EN TRABAJAR CON DETALLES', 'glosa' => '7/6.- Le agrada realizar trabajos que exijan atención en detalles.'],
                7 => ['detalle' => 'INTERESADO EN TRABAJAR CON DETALLES', 'glosa' => '7/6.- Le agrada realizar trabajos que exijan atención en detalles.'],
                8 => ['detalle' => 'INTERESADO EN TRABAJAR CON DETALLES', 'glosa' => '8.- Se dedica a eliminar pormenores, perdiendo la visión del conjunto.'],
                9 => ['detalle' => 'INTERESADO EN TRABAJAR CON DETALLES', 'glosa' => '9.- Gran interés en detalles. Tiende a omitir conceptos importantes y perder la visión del conjunto.'],
            ],
            'E' => [
                1 => ['detalle' => 'TIPO EMOCIONALMENTE CONTENIDO', 'glosa' => '1.- Deja reflejar sus emociones en el trabajo, necesitando ser dinámico, se torna completamente envuelto emocionalmente con el trabajo que realiza.'],
                2 => ['detalle' => 'TIPO EMOCIONALMENTE CONTENIDO', 'glosa' => '2.- Deja reflejar sus emociones, tiene expresión dinámica y dramática; gasta mucha energía cuando trabaja, tornándose emocionalmente envuelto con su trabajo.'],
                3 => ['detalle' => 'TIPO EMOCIONALMENTE CONTENIDO', 'glosa' => '3.- Tiende a envolverse emocionalmente con su trabajo, es dinámico en su expresión, dejando reflejar sus emociones.'],
                4 => ['detalle' => 'TIPO EMOCIONALMENTE CONTENIDO', 'glosa' => '4.- Grado regular de envolvimiento emocional con el trabajo, se esfuerza para que sus emociones no interfieran en el trabajo.'],
                5 => ['detalle' => 'TIPO EMOCIONALMENTE CONTENIDO', 'glosa' => '5.- Poco envolvimiento emocional en el trabajo, equilibra sus emociones.'],
                6 => ['detalle' => 'TIPO EMOCIONALMENTE CONTENIDO', 'glosa' => '6.- Tiende a ser calmado y formal en el trabajo, controla sus emociones.'],
                7 => ['detalle' => 'TIPO EMOCIONALMENTE CONTENIDO', 'glosa' => '7.- Es calmado y formal en el trabajo, contiene sus emociones difícilmente demuestra lo que está sintiendo.'],
                8 => ['detalle' => 'TIPO EMOCIONALMENTE CONTENIDO', 'glosa' => '8.- Es frío y formal en el trabajo; característicamente racional; no demuestra lo que siente, tiende a esconder sus emociones, no dando valor a personas emotivas.'],
                9 => ['detalle' => 'TIPO EMOCIONALMENTE CONTENIDO', 'glosa' => '9.- Es racional y formal en su trabajo, racionalizando sus emociones, no se permite demostraciones afectivas en el trabajo. No consigue trabajar con personas emotivas.'],
            ],
            'F' => [
                1 => ['detalle' => 'NECESIDAD DE OBEDIENCIA A LA AUTORIDAD.', 'glosa' => '1.- Es rebelde ante la autoridad. Necesita sentirse libre y exento de control de la jefatura; su opinión respecto de su trabajo es el factor que lo motiva y no la opinión de la jefatura.'],
                2 => ['detalle' => 'NECESIDAD DE OBEDIENCIA A LA AUTORIDAD.', 'glosa' => '2.- Comportamiento independiente, poca preocupación en estar de acuerdo con la autoridad, eventualmente enfrenta a la autoridad y prefiere no recibir supervisión.'],
                3 => ['detalle' => 'NECESIDAD DE OBEDIENCIA A LA AUTORIDAD.', 'glosa' => '3.- Auto-confiado y motivado por el trabajo y no por el reconocimiento del jefe. No necesita del incentivo del jefe. Tiende a ser resistente a la autoridad. Se siente libre para expresar sus puntos de vista.'],
                4 => ['detalle' => 'NECESIDAD DE OBEDIENCIA A LA AUTORIDAD.', 'glosa' => '4.- Tiene confianza en sí mismo, no dependiendo del control de la jefatura, eventualmente puede argumentar con la jefatura.'],
                5 => ['detalle' => 'NECESIDAD DE OBEDIENCIA A LA AUTORIDAD.', 'glosa' => '5.- Tiene confianza en sí mismo, conviviendo en igualdad con la autoridad.'],
                6 => ['detalle' => 'NECESIDAD DE OBEDIENCIA A LA AUTORIDAD.', 'glosa' => '6.- Influenciable por las opiniones y puntos de vista de sus superiores; trata de corresponder a la expectativa de su superiores.'],
                7 => ['detalle' => 'NECESIDAD DE OBEDIENCIA A LA AUTORIDAD.', 'glosa' => '7.- Necesita obedecer al jefe para recibir estímulos que lo motiven.'],
                8 => ['detalle' => 'NECESIDAD DE OBEDIENCIA A LA AUTORIDAD.', 'glosa' => '8.- Se preocupa mucho en respetar y obedecer al jefe, tratando de asegurarse del valor de su trabajo.'],
                9 => ['detalle' => 'NECESIDAD DE OBEDIENCIA A LA AUTORIDAD.', 'glosa' => '9.- No tiene confianza en sí mismo, depende del apoyo de su jefe para poder trabajar. Necesita ser constantemente motivado por la autoridad.'],
            ],
            'G' => [
                1 => ['detalle' => 'DESEMPEÑO DEL TRABAJO ARDUO Y CONCENTRADO ( RESPONSABILIDAD ).', 'glosa' => '1.- No le agradan trabajos que exijan esfuerzo, puede dejar para el día siguiente lo que podía hacer hoy.'],
                2 => ['detalle' => 'DESEMPEÑO DEL TRABAJO ARDUO Y CONCENTRADO ( RESPONSABILIDAD ).', 'glosa' => '2.- Dedica poco esfuerzo a la realización de sus trabajos.'],
                3 => ['detalle' => 'DESEMPEÑO DEL TRABAJO ARDUO Y CONCENTRADO ( RESPONSABILIDAD ).', 'glosa' => '3.- Le agrada realizar trabajos que no exijan esfuerzo para ser realizados.'],
                4 => ['detalle' => 'DESEMPEÑO DEL TRABAJO ARDUO Y CONCENTRADO ( RESPONSABILIDAD ).', 'glosa' => '5/4.- Identificación regular con trabajos difíciles.'],
                5 => ['detalle' => 'DESEMPEÑO DEL TRABAJO ARDUO Y CONCENTRADO ( RESPONSABILIDAD ).', 'glosa' => '5/4.- Identificación regular con trabajos difíciles.'],
                6 => ['detalle' => 'DESEMPEÑO DEL TRABAJO ARDUO Y CONCENTRADO ( RESPONSABILIDAD ).', 'glosa' => '6.- Identificación sobre regular con trabajos difíciles.'],
                7 => ['detalle' => 'DESEMPEÑO DEL TRABAJO ARDUO Y CONCENTRADO ( RESPONSABILIDAD ).', 'glosa' => '7.- Prefiere trabajos que exijan esfuerzo para ser realizado.'],
                8 => ['detalle' => 'DESEMPEÑO DEL TRABAJO ARDUO Y CONCENTRADO ( RESPONSABILIDAD ).', 'glosa' => '8.- Es bastante dedicado al trabajo que exige esfuerzo para ser realizado.'],
                9 => ['detalle' => 'DESEMPEÑO DEL TRABAJO ARDUO Y CONCENTRADO ( RESPONSABILIDAD ).', 'glosa' => '9.- Es extremadamente dedicado a trabajos que exigen esfuerzos para ser realizados.'],
            ],
            'I' => [
                1 => ['detalle' => 'FACILIDAD EN LA TOMA DE DECISIONES.', 'glosa' => '1.- Siente dificultad para decidirse, el proceso de decisión le crea angustia y malestar; no le agrada tomar decisiones.'],
                2 => ['detalle' => 'FACILIDAD EN LA TOMA DE DECISIONES.', 'glosa' => '2.- Es lento en la toma de decisiones. Se preocupa mucho por la calidad de la decisión ( tiende a dejar de tomar decisiones ).'],
                3 => ['detalle' => 'FACILIDAD EN LA TOMA DE DECISIONES.', 'glosa' => '3.- Piensa para decidirse; tiende a ser reflexivo, es lento en el proceso de toma de decisiones.'],
                4 => ['detalle' => 'FACILIDAD EN LA TOMA DE DECISIONES.', 'glosa' => '4.- Grado regular de capacidad para decidirse.'],
                5 => ['detalle' => 'FACILIDAD EN LA TOMA DE DECISIONES.', 'glosa' => '5.- Buena capacidad para decidirse; trata de imprimir en sus decisiones el mismo grado de calidad y rapidez.'],
                6 => ['detalle' => 'FACILIDAD EN LA TOMA DE DECISIONES.', 'glosa' => '6.- Toma decisiones con facilidad sin, entretanto, apresurarse en dejar de medir las consecuencias de sus decisiones.'],
                7 => ['detalle' => 'FACILIDAD EN LA TOMA DE DECISIONES.', 'glosa' => '7.- Rápido para decidirse; se preocupa poco de las consecuencias de sus decisiones, dando más énfasis a la velocidad en la toma de las mismas.'],
                8 => ['detalle' => 'FACILIDAD EN LA TOMA DE DECISIONES.', 'glosa' => '8.- Impulsivo, da más énfasis a la velocidad que a la seguridad de la decisiones. Puede tomar decisiones apresuradas.'],
                9 => ['detalle' => 'FACILIDAD EN LA TOMA DE DECISIONES.', 'glosa' => '9.- Extremadamente rápido para decidirse; corre el riesgo de tomar decisiones no pensadas.'],
            ],
            'K' => [
                1 => ['detalle' => 'NECESIDAD DE SER DEFENSIVAMENTE AGRESIVO', 'glosa' => '1.- No manifiesta sus opiniones francamente, estando a la defensiva casi siempre.'],
                2 => ['detalle' => 'NECESIDAD DE SER DEFENSIVAMENTE AGRESIVO', 'glosa' => '2.- Está a la defensiva la mayor parte del tiempo, difícilmente se manifiesta abiertamente.'],
                3 => ['detalle' => 'NECESIDAD DE SER DEFENSIVAMENTE AGRESIVO', 'glosa' => '3.- Posee reserva en la manifestación de su s opiniones. Tiende a estar en la defensiva.'],
                4 => ['detalle' => 'NECESIDAD DE SER DEFENSIVAMENTE AGRESIVO', 'glosa' => '4.- Tiende a estar a la defensiva con las personas.'],
                5 => ['detalle' => 'NECESIDAD DE SER DEFENSIVAMENTE AGRESIVO', 'glosa' => '5.- Grado medio de defensa, tiende a ser reservado.'],
                6 => ['detalle' => 'NECESIDAD DE SER DEFENSIVAMENTE AGRESIVO', 'glosa' => '6.- Capacidad para enfrentar y argumentar con las personas.'],
                7 => ['detalle' => 'NECESIDAD DE SER DEFENSIVAMENTE AGRESIVO', 'glosa' => '7.- Enfrenta las personas, es abierto y sincero.'],
                8 => ['detalle' => 'NECESIDAD DE SER DEFENSIVAMENTE AGRESIVO', 'glosa' => '9/8.- Persona que se opone y enfrenta franca y abiertamente a los otros.'],
                9 => ['detalle' => 'NECESIDAD DE SER DEFENSIVAMENTE AGRESIVO', 'glosa' => '9/8.- Persona que se opone y enfrenta franca y abiertamente a los otros.'],
            ],
            'L' => [
                1 => ['detalle' => 'PAPEL DE LIDERAZGO.', 'glosa' => '1.- No acepta el papel de líder, prefiere ser liderado a liderar, en posición de jefatura, tiene a evitar el liderazgo.'],
                2 => ['detalle' => 'PAPEL DE LIDERAZGO.', 'glosa' => '2.- Tiene problemas con liderazgo. Prefiere ser orientado por los otros . No obtiene suficiente recompensa interior en el papel de liderazgo.'],
                3 => ['detalle' => 'PAPEL DE LIDERAZGO.', 'glosa' => '3.- No obtiene suficiente recompensa interior como líder, tiende a transferir los problemas de liderazgo.'],
                4 => ['detalle' => 'PAPEL DE LIDERAZGO.', 'glosa' => '4.- Grado medio de confianza en sí mismo como líder, pudiendo igualmente ejercer el liderazgo y ser liderado.'],
                5 => ['detalle' => 'PAPEL DE LIDERAZGO.', 'glosa' => '5.- Confía en sí mismo como líder, tiende a ejercer el liderazgo.'],
                6 => ['detalle' => 'PAPEL DE LIDERAZGO.', 'glosa' => '6.- Le agrada liderar es una persona que asume el liderazgo del grupo.'],
                7 => ['detalle' => 'PAPEL DE LIDERAZGO.', 'glosa' => '7.- Tiene confianza en sí como líder; le agrada tomar el liderazgo del grupo.'],
                8 => ['detalle' => 'PAPEL DE LIDERAZGO.', 'glosa' => '8.- Muy confiado como líder; es considerado como un líder en el grupo.'],
                9 => ['detalle' => 'PAPEL DE LIDERAZGO.', 'glosa' => '9.- Es impulsado por un fuerte deseo de liderazgo. Es auto-confiante a punto de tomar frecuentemente el liderazgo.'],
            ],
            'N' => [
                1 => ['detalle' => 'NECESIDAD DE COMPLETAR LA TAREA.', 'glosa' => '1.- Persona que se preocupa principalmente en fijar objetivos y metas, no sintiendo ninguna necesidad en acabar lo que inicia. Necesita realizar muchas tareas simultáneamente, pudiendo dejar trabajos incompletos.'],
                2 => ['detalle' => 'NECESIDAD DE COMPLETAR LA TAREA.', 'glosa' => '2.- Persona que no siente la necesidad de completar tareas personalmente, prefiere descentralizar los trabajos para permanecer en una actividad de coordinación.'],
                3 => ['detalle' => 'NECESIDAD DE COMPLETAR LA TAREA.', 'glosa' => '3.- Puede delegar sus trabajos. Puede realizar muchas tareas simultáneamente.'],
                4 => ['detalle' => 'NECESIDAD DE COMPLETAR LA TAREA.', 'glosa' => '4.- Puede delegar sus trabajos. Puede realizar algunas tareas simultáneamente.'],
                5 => ['detalle' => 'NECESIDAD DE COMPLETAR LA TAREA.', 'glosa' => '5.- Puede delegar parte de sus trabajos, dejando para sí la realización completa de buena parte de ella.'],
                6 => ['detalle' => 'NECESIDAD DE COMPLETAR LA TAREA.', 'glosa' => '6.- Tiene necesidad de completar sus tareas.'],
                7 => ['detalle' => 'NECESIDAD DE COMPLETAR LA TAREA.', 'glosa' => '7.- Necesita mucho terminar lo que comienza; fija toda su atención en la realización de una tarea hasta terminarla.'],
                8 => ['detalle' => 'NECESIDAD DE COMPLETAR LA TAREA.', 'glosa' => '8.- Persistente. Tiene dificultad en dejar la tarea que está haciendo. Tiene que completar lo que comienza. Siente dificultad en delegar.'],
                9 => ['detalle' => 'NECESIDAD DE COMPLETAR LA TAREA.', 'glosa' => '9.- No consigue dejar lo que está haciendo. Es extremadamente preocupado con la necesidad de completar una tarea. Queda frustrado y ansioso al no terminar lo que inicia. Siente mucha dificultad para delegar.'],
            ],
            'O' => [
                1 => ['detalle' => 'NECESIDAD AFECTIVA.', 'glosa' => '1.- Tiene un modo racional de abordar las cosas (intelectualizada) cierta dificultad de relacionamiento.'],
                2 => ['detalle' => 'NECESIDAD AFECTIVA.', 'glosa' => '3/2.- Mantiene un relacionamiento formal con las personas.'],
                3 => ['detalle' => 'NECESIDAD AFECTIVA.', 'glosa' => '3/2.- Mantiene un relacionamiento formal con las personas.'],
                4 => ['detalle' => 'NECESIDAD AFECTIVA.', 'glosa' => '4.- Le agrada pertenecer al grupo y participar con otras personas.'],
                5 => ['detalle' => 'NECESIDAD AFECTIVA.', 'glosa' => '5.- Se relaciona cálidamente con las personas.'],
                6 => ['detalle' => 'NECESIDAD AFECTIVA.', 'glosa' => '6.- Persona que le agrada recibir el afecto de los otros.'],
                7 => ['detalle' => 'NECESIDAD AFECTIVA.', 'glosa' => '7.- Necesita tener el afecto y apoyo de los otros.'],
                8 => ['detalle' => 'NECESIDAD AFECTIVA.', 'glosa' => '8.- Sensible, necesita obtener el afecto y apoyo de los otros en su relacionamiento.'],
                9 => ['detalle' => 'NECESIDAD AFECTIVA.', 'glosa' => '9.- Es extremadamente afectivo en su relacionamiento; siente la necesidad de ser querido.'],
            ],
            'P' => [
                1 => ['detalle' => 'NECESIDAD DE CONTROLAR A LOS OTROS ( DOMINANCIA ).', 'glosa' => '1.- No le agrada asumir responsabilidad por terceros, tiene dificultad en controlar a los otros.'],
                2 => ['detalle' => 'NECESIDAD DE CONTROLAR A LOS OTROS ( DOMINANCIA ).', 'glosa' => '2.- Se inclina por sí mismo, poco interés en controlar personas, tiene dificultad en controlar a los otros.'],
                3 => ['detalle' => 'NECESIDAD DE CONTROLAR A LOS OTROS ( DOMINANCIA ).', 'glosa' => '3.- Prefiere no responsabilizarse por los otros. Tiende a no controlar a los otros.'],
                4 => ['detalle' => 'NECESIDAD DE CONTROLAR A LOS OTROS ( DOMINANCIA ).', 'glosa' => '4.- Agrado de sí mismo y respeta a los otros. Grado regular de preocupación en controlar a los otros.'],
                5 => ['detalle' => 'NECESIDAD DE CONTROLAR A LOS OTROS ( DOMINANCIA ).', 'glosa' => '5.- Se interesa por las personas, pudiendo eventualmente manejarlas, a través de la imagen de protector.'],
                6 => ['detalle' => 'NECESIDAD DE CONTROLAR A LOS OTROS ( DOMINANCIA ).', 'glosa' => '6.- Le agrada influenciar a las personas transmitiéndoles sus puntos de vista.'],
                7 => ['detalle' => 'NECESIDAD DE CONTROLAR A LOS OTROS ( DOMINANCIA ).', 'glosa' => '7.- Le agrada ser responsable de las personas; necesita influenciar a los otros.'],
                8 => ['detalle' => 'NECESIDAD DE CONTROLAR A LOS OTROS ( DOMINANCIA ).', 'glosa' => '8.- Se preocupa de orientar y dirigir a las personas, controlándolas de acuerdo con sus puntos de vista.'],
                9 => ['detalle' => 'NECESIDAD DE CONTROLAR A LOS OTROS ( DOMINANCIA ).', 'glosa' => '9.- Persona fuertemente dominante. Se preocupa de dirigir a las personas según su voluntad.'],
            ],
            'R' => [
                1 => ['detalle' => 'TIPO TEORICO ( PRACTICA )', 'glosa' => '1.- Ejecuta los trabajos sin planificar.'],
                2 => ['detalle' => 'TIPO TEORICO ( PRACTICA )', 'glosa' => '2.- Tiene dificultades en planificar sus trabajos, prefiriendo ejecutar a planificar.'],
                3 => ['detalle' => 'TIPO TEORICO ( PRACTICA )', 'glosa' => '3.- Prefiere ejecutar a planificar.'],
                4 => ['detalle' => 'TIPO TEORICO ( PRACTICA )', 'glosa' => '5/4.- Prefiere planificar y formular estrategias 40 a 50% del tiempo.'],
                5 => ['detalle' => 'TIPO TEORICO ( PRACTICA )', 'glosa' => '5/4.- Prefiere planificar y formular estrategias 40 a 50% del tiempo.'],
                6 => ['detalle' => 'TIPO TEORICO ( PRACTICA )', 'glosa' => '7/6.- Prefiere planificar y formular estrategias 70% del tiempo.'],
                7 => ['detalle' => 'TIPO TEORICO ( PRACTICA )', 'glosa' => '7/6.- Prefiere planificar y formular estrategias 70% del tiempo.'],
                8 => ['detalle' => 'TIPO TEORICO ( PRACTICA )', 'glosa' => '8.- Gasta la mayor parte del tiempo, 80 a 90%, planificando y formulando estrategias.'],
                9 => ['detalle' => 'TIPO TEORICO ( PRACTICA )', 'glosa' => '9.- Gasta la totalidad de su tiempo planificando, tiene dificultades en ejecutar tareas.'],
            ],
            'S' => [
                1 => ['detalle' => 'DISPOSICION SOCIAL', 'glosa' => '1.- Persona introvertida, sin preocupación por la comunicación social. Socialmente sin condición.'],
                2 => ['detalle' => 'DISPOSICION SOCIAL', 'glosa' => '2.- Es una persona reservada en su relacionamiento social.'],
                3 => ['detalle' => 'DISPOSICION SOCIAL', 'glosa' => '3.- Posee cierta reserva en la comunicación social.'],
                4 => ['detalle' => 'DISPOSICION SOCIAL', 'glosa' => '4.- Preocupación regular (50%) con la comunicación social.'],
                5 => ['detalle' => 'DISPOSICION SOCIAL', 'glosa' => '5.- Buena capacidad de escuchar y comunicarse socialmente.'],
                6 => ['detalle' => 'DISPOSICION SOCIAL', 'glosa' => '6.- Posee buen relacionamiento social.'],
                7 => ['detalle' => 'DISPOSICION SOCIAL', 'glosa' => '7.- Buena disposición social. Persona muy receptiva.'],
                8 => ['detalle' => 'DISPOSICION SOCIAL', 'glosa' => '8.- Persona extrovertida, con óptima capacidad de comunicación.'],
                9 => ['detalle' => 'DISPOSICION SOCIAL', 'glosa' => '9.- Persona muy participativa y receptiva a la comunicación.'],
            ],
            'T' => [
                1 => ['detalle' => 'TIPO ACTIVO-INQUIETO Y AGIL ( STRESS )', 'glosa' => '1.- No le agrada trabajar con presión de plazos, tiende a no dar importancia al tiempo establecido.'],
                2 => ['detalle' => 'TIPO ACTIVO-INQUIETO Y AGIL ( STRESS )', 'glosa' => '2.- Trabaja calmadamente, poco preocupado en cuanto a límites de tiempo, tiene dificultad en manejar plazos pre - establecidos, no se siente bien con trabajos que exijan plazos pre - establecidos.'],
                3 => ['detalle' => 'TIPO ACTIVO-INQUIETO Y AGIL ( STRESS )', 'glosa' => '3.- Se preocupa eventualmente por los límites de tiempo, prefiriendo no trabajar en base a presión de plazos.'],
                4 => ['detalle' => 'TIPO ACTIVO-INQUIETO Y AGIL ( STRESS )', 'glosa' => '4.- Es responsable en cuanto a límites de tiempo. Tiende a ejecutar sus deberes dentro de los plazos determinados.'],
                5 => ['detalle' => 'TIPO ACTIVO-INQUIETO Y AGIL ( STRESS )', 'glosa' => '5.- Se preocupa por lo límites de tiempo. Trabaja dentro de los límites de tiempo establecidos.'],
                6 => ['detalle' => 'TIPO ACTIVO-INQUIETO Y AGIL ( STRESS )', 'glosa' => '6.- Responsabilidad sobre regular en cuanto a límites de tiempo.'],
                7 => ['detalle' => 'TIPO ACTIVO-INQUIETO Y AGIL ( STRESS )', 'glosa' => '7.- Es una persona inquieta y muy preocupada con plazos, tiene mucha necesidad de realizar sus trabajos dentro de los límites de tiempo.'],
                8 => ['detalle' => 'TIPO ACTIVO-INQUIETO Y AGIL ( STRESS )', 'glosa' => '8.- Posee mucha tensión interna. Fuertemente preocupado con los límites de tiempo.'],
                9 => ['detalle' => 'TIPO ACTIVO-INQUIETO Y AGIL ( STRESS )', 'glosa' => '9.- Persona que esta permanentemente tensa y fuertemente impulsada a trabajar dentro de los límites de tiempo.'],
            ],
            'V' => [
                1 => ['detalle' => 'TIPO CON VIGOR FISICO.', 'glosa' => '1.- No le agradan trabajos que exijan movimiento; necesita actividades que pueden ser realizadas sentadas.'],
                2 => ['detalle' => 'TIPO CON VIGOR FISICO.', 'glosa' => '2.- Prefiere trabajos que pueden ser realizados sentado.'],
                3 => ['detalle' => 'TIPO CON VIGOR FISICO.', 'glosa' => '3.- Poco interés en actividades que exijan movimiento. Prefiere trabajar sentado.'],
                4 => ['detalle' => 'TIPO CON VIGOR FISICO.', 'glosa' => '5/4.- Grado regular de vigor físico. Tiende a preferir funciones que exijan movimiento limitado.'],
                5 => ['detalle' => 'TIPO CON VIGOR FISICO.', 'glosa' => '5/4.- Grado regular de vigor físico. Tiende a preferir funciones que exijan movimiento limitado.'],
                6 => ['detalle' => 'TIPO CON VIGOR FISICO.', 'glosa' => '6.- Le agrada estar en movimiento.'],
                7 => ['detalle' => 'TIPO CON VIGOR FISICO.', 'glosa' => '7.- Necesita estar en constante movimiento.'],
                8 => ['detalle' => 'TIPO CON VIGOR FISICO.', 'glosa' => '8.- Es muy dinámico, tiene dificultad en realizar actividades que lo obliguen a estar parado en un ambiente.'],
                9 => ['detalle' => 'TIPO CON VIGOR FISICO.', 'glosa' => '9.- Es extremadamente inquieto, necesita realizar actividades que exijan bastante movimiento.'],
            ],
            'W' => [
                1 => ['detalle' => 'NECESIDAD DE REGLAMENTO Y SUPERVISION.', 'glosa' => '1.- No le agrada seguir reglamentos, le agrada ir y venir libremente, es auto-dirigido, no le agrada ser orientado.'],
                2 => ['detalle' => 'NECESIDAD DE REGLAMENTO Y SUPERVISION.', 'glosa' => '2.- Persona que prefiere no seguir normas. Le agrada ser libre. Prefiere auto-dirigirse a ser orientado.'],
                3 => ['detalle' => 'NECESIDAD DE REGLAMENTO Y SUPERVISION.', 'glosa' => '3.- Poca necesidad de reglamentos y normas, prefiere recibir supervisión apenas ocasionalmente.'],
                4 => ['detalle' => 'NECESIDAD DE REGLAMENTO Y SUPERVISION.', 'glosa' => '4.- Regular interés por seguir normas y reglamentos.'],
                5 => ['detalle' => 'NECESIDAD DE REGLAMENTO Y SUPERVISION.', 'glosa' => '5.- Le agrada seguir reglamentos y obtener "la palabra oficial".'],
                6 => ['detalle' => 'NECESIDAD DE REGLAMENTO Y SUPERVISION.', 'glosa' => '6.- Le agrada seguir normas y reglamentos.'],
                7 => ['detalle' => 'NECESIDAD DE REGLAMENTO Y SUPERVISION.', 'glosa' => '7.- Necesita respetar normas y reglamentos.'],
                8 => ['detalle' => 'NECESIDAD DE REGLAMENTO Y SUPERVISION.', 'glosa' => '8.- Se preocupa mucho en respetar normas y reglamentos.'],
                9 => ['detalle' => 'NECESIDAD DE REGLAMENTO Y SUPERVISION.', 'glosa' => '9.- Necesita de normas y reglamentos para poder trabajar.'],
            ],
            'X' => [
                1 => ['detalle' => 'NECESIDAD DE SER CONSIDERADO', 'glosa' => '1.- Prefiere mantenerse reservado, le agrada pasar desapercibido. No le agrada ser el centro de las atenciones.'],
                2 => ['detalle' => 'NECESIDAD DE SER CONSIDERADO', 'glosa' => '2.- Es reservado en sus contactos sociales, tiende a no sentirse bien cuando es el centro de las atenciones.'],
                3 => ['detalle' => 'NECESIDAD DE SER CONSIDERADO', 'glosa' => '3.- Es sincero en sus contactos sociales.'],
                4 => ['detalle' => 'NECESIDAD DE SER CONSIDERADO', 'glosa' => '4.- Grado regular de solicitud , tiende a saber escuchar a las personas.'],
                5 => ['detalle' => 'NECESIDAD DE SER CONSIDERADO', 'glosa' => '5.- Es solícito buscando amistad en el apoyo de los otros.'],
                6 => ['detalle' => 'NECESIDAD DE SER CONSIDERADO', 'glosa' => '6.- Persona que le agrada recibir atención de los otros y de ser notado.'],
                7 => ['detalle' => 'NECESIDAD DE SER CONSIDERADO', 'glosa' => '7.- Le agrada hablar respecto de sus actividades con el fin de sentirse valorizado y aceptado.'],
                8 => ['detalle' => 'NECESIDAD DE SER CONSIDERADO', 'glosa' => '8.- Necesita sentirse valorizado por las personas, llamando la atención sobre sí mismo.'],
                9 => ['detalle' => 'NECESIDAD DE SER CONSIDERADO', 'glosa' => '9.- Exageradamente dependiente de las opiniones de los otros, trata de hacerse notar en el grupo en el cual se encuentra.'],
            ],
            'Z' => [
                1 => ['detalle' => 'NECESIDAD DE CAMBIO. NECESIDAD DE IDENTIFICARSE', 'glosa' => '1.- Tiene gran dificultad para enfrentar nuevas situaciones y cambios; necesita trabajos rutinarios y repetidos en situaciones estables e inmutables.'],
                2 => ['detalle' => 'NECESIDAD DE CAMBIO. NECESIDAD DE IDENTIFICARSE', 'glosa' => '2.- Resistente a las mudanzas, es más indicado para trabajos de rutina y repetidos.'],
                3 => ['detalle' => 'NECESIDAD DE CAMBIO. NECESIDAD DE IDENTIFICARSE', 'glosa' => '3.- Posee cierta reserva a los cambios. Prefiere trabajos de rutina y repetidos.'],
                4 => ['detalle' => 'NECESIDAD DE CAMBIO. NECESIDAD DE IDENTIFICARSE', 'glosa' => '4.- Medianamente receptivo a los cambios, dependiendo de las circunstancias que las envuelven, puede ofrecer resistencia a los mismos.'],
                5 => ['detalle' => 'NECESIDAD DE CAMBIO. NECESIDAD DE IDENTIFICARSE', 'glosa' => '5.- Receptivo a los cambios. Se ajusta a los mismos.'],
                6 => ['detalle' => 'NECESIDAD DE CAMBIO. NECESIDAD DE IDENTIFICARSE', 'glosa' => '6.- Se ajusta fácilmente a los cambios; tiene flexibilidad de pensamiento.'],
                7 => ['detalle' => 'NECESIDAD DE CAMBIO. NECESIDAD DE IDENTIFICARSE', 'glosa' => '7.- Necesita variar sus actividades, identificándose con cambios y con lo que es nuevo. Prefiere trabajos que exijan creatividad.'],
                8 => ['detalle' => 'NECESIDAD DE CAMBIO. NECESIDAD DE IDENTIFICARSE', 'glosa' => '8.- Es impulsado a mudar frecuentemente sus preferencias, necesita cambios continuamente en el trabajo.'],
                9 => ['detalle' => 'NECESIDAD DE CAMBIO. NECESIDAD DE IDENTIFICARSE', 'glosa' => '9.- Es impulsado por una fuerte necesidad de cambiar constantemente sus actividades, siendo inconstante en sus preferencias y actividades.'],
            ],
        ];


        foreach ($answers as $ans) {
            if ($ans->code && isset($scores[$ans->code])) {
                $scores[$ans->code]++;
            }
        }

        return [
            'test_name'            => 'Kostick',
            'chart_type'           => 'radar',
            'scores'               => $scores,
            'factoresAgrupados'    => $factoresAgrupados,
            'kostickInterpretation'=> $kostickInterpretation,
            'summary'              => "Perfil de comportamiento y preferencias."
        ];
    }

    //Interpretación de Moss COMPLETA y FUNCIONANDO
    private function calculateMoss($evaluation)
    {
        // 1. MAPEO: ¿A qué dimensión pertenece cada pregunta? (Estándar Moss)
        // Formato: [Número de Pregunta => ID de Dimensión]
        $map = [
            1 => 4, 2 => 1, 3 => 1, 4 => 2, 5 => 5,
            6 => 2, 7 => 3, 8 => 5, 9 => 3, 10 => 4,
            11 => 4, 12 => 3, 13 => 4, 14 => 3, 15 => 5,
            16 => 1, 17 => 5, 18 => 1, 19 => 3, 20 => 2,
            21 => 3, 22 => 5, 23 => 2, 24 => 1, 25 => 4,
            26 => 3, 27 => 3, 28 => 5, 29 => 2, 30 => 1
        ];
        $dimensions = [
            1 => [
                'name' => 'Habilidad de Supervisión',
                'completeName' => 'Habilidad de Supervisión',
                'description' => 'Es la eficacia con que propicia que el personal a su cargo cumpla con las actividades encomendadas.',
                'total_questions' => 6,
                'score' => 0
            ],
            2 => [
                'name' => 'Capacidad de Decisión',
                'completeName' => 'Capacidad de decisión en las Relaciones Humanas', // (Capacidad de decisión en las Relaciones Humanas)
                'description' => 'Es el criterio y toma de decisiones con respecto a la forma de interactuar con los demás.',
                'total_questions' => 5,
                'score' => 0
            ],
            3 => [
                'name' => 'Capacidad de Evaluación',
                'completeName' => 'Capacidad para Evaluar Problemas Interpersonales', // (Capacidad para Evaluar Problemas Interpersonales)
                'description' => 'Criterio y juicio con respecto a situaciones sociales que presentan conflicto con cierta problemática.',
                'total_questions' => 8,
                'score' => 0
            ],
            4 => [
                'name' => 'Habilidad de Relacionarse',
                'completeName' => 'Capacidad para Establecer Relaciones Interpersonales', // (Capacidad para Establecer Relaciones Interpersonales)
                'description' => 'Es la facultad con que cuenta para establecer contacto con los demás de manera adaptativa y eficiente.',
                'total_questions' => 5,
                'score' => 0
            ],
            5 => [
                'name' => 'Sentido Común y Tacto',
                'completeName' => 'Sentido común y tacto en las Relaciones Interpersonales', // (Sentido común y tacto en las Relaciones Interpersonales)
                'description' => 'Capacidad de llevarse bien con los demás manteniendo una conducta basada en el buen juicio y la lógica ante dificultades o conflictos.',
                'total_questions' => 6,
                'score' => 0
            ],
        ];

        $feedbackMatrix = [
            'Habilidad de Supervisión' => [
                'Excelente' => [
                    'interpretation' => 'Liderazgo excepcional, delegación precisa y monitoreo constante.',
                    'recommendation' => 'Asignar proyectos estratégicos y fomentar su mentoría a otros supervisores.'
                ],
                'Superior' => [
                    'interpretation' => 'Sobresale en supervisión, aunque puede perfeccionar aspectos menores de liderazgo.',
                    'recommendation' => 'Ofrecer oportunidades de liderazgo en proyectos clave.'
                ],
                'Superior al Término Medio' => [
                    'interpretation' => 'Buen desempeño en supervisión, con áreas específicas para mejorar en eficiencia o seguimiento.',
                    'recommendation' => 'Capacitación en habilidades avanzadas de supervisión y liderazgo.'
                ],
                'Término Medio' => [
                    'interpretation' => 'Supervisión adecuada pero inconsistente en situaciones críticas.',
                    'recommendation' => 'Reforzar habilidades con talleres de supervisión y seguimiento estructurado.'
                ],
                'Inferior al Término Medio' => [
                    'interpretation' => 'Falta de claridad en liderazgo y supervisión, impactando la productividad del equipo.',
                    'recommendation' => 'Capacitación en fundamentos de supervisión y asignación de tareas guiadas.'
                ],
                'Inferior' => [
                    'interpretation' => 'Deficiencias significativas en supervisión, dificultando la ejecución eficiente del trabajo.',
                    'recommendation' => 'Coaching intensivo y supervisión directa por un líder experimentado.'
                ],
                'Deficiente' => [
                    'interpretation' => 'Incapacidad para supervisar adecuadamente, afectando el logro de objetivos organizacionales.',
                    'recommendation' => 'Implementar un plan de desarrollo intensivo y reevaluar su ajuste al rol de supervisión.'
                ],
            ],

            'Capacidad de Decisión' => [
                'Excelente' => [
                    'interpretation' => 'Habilidad excepcional para crear conexiones y mantener relaciones laborales positivas.',
                    'recommendation' => 'Asignar tareas que requieran mediación o manejo de conflictos sensibles.'
                ],
                'Superior' => [
                    'interpretation' => 'Relaciones humanas destacadas, con capacidad de comunicación efectiva y empatía.',
                    'recommendation' => 'Delegar roles donde la interacción interpersonal sea clave, como recursos humanos o ventas.'
                ],
                'Superior al Término Medio' => [
                    'interpretation' => 'Buenas habilidades interpersonales, con margen de mejora en empatía o resolución de conflictos.',
                    'recommendation' => 'Fomentar actividades de desarrollo de inteligencia emocional.'
                ],
                'Término Medio' => [
                    'interpretation' => 'Relaciones humanas aceptables, pero limitadas en contextos más exigentes.',
                    'recommendation' => 'Ofrecer talleres sobre habilidades interpersonales y manejo de conflictos.'
                ],
                'Inferior al Término Medio' => [
                    'interpretation' => 'Dificultades para establecer relaciones positivas o mantenerlas en el tiempo.',
                    'recommendation' => 'Entrenamiento en habilidades sociales y comunicación efectiva.'
                ],
                'Inferior' => [
                    'interpretation' => 'Problemas evidentes en relaciones interpersonales que afectan la dinámica de equipo.',
                    'recommendation' => 'Asignar un mentor y monitorear su progreso en ambientes colaborativos.'
                ],
                'Deficiente' => [
                    'interpretation' => 'Relaciones humanas deficientes, con riesgo de afectar el clima laboral de manera crítica.',
                    'recommendation' => 'Intervención inmediata con coaching especializado en inteligencia emocional y relaciones.'
                ],
            ],
            'Capacidad de Evaluación' => [
                'Excelente' => [
                    'interpretation' => 'Gran precisión al analizar y evaluar situaciones, con criterio confiable y decisiones efectivas.',
                    'recommendation' => 'Asignar roles de evaluación estratégica en proyectos críticos.'
                ],
                'Superior' => [
                    'interpretation' => 'Capacidad destacada para evaluar, aunque con posibles mejoras en ciertos matices de análisis.',
                    'recommendation' => 'Fomentar su participación en evaluaciones grupales o auditorías.'
                ],
                'Superior al Término Medio' => [
                    'interpretation' => 'Evaluación adecuada, aunque podría mejorar en detalle y profundidad en situaciones específicas.',
                    'recommendation' => 'Proveer herramientas y capacitación avanzada en análisis y evaluación crítica.'
                ],
                'Término Medio' => [
                    'interpretation' => 'Evaluaciones consistentes, pero faltan aspectos clave de detalle en contextos más complejos.',
                    'recommendation' => 'Promover el uso de metodologías estructuradas de análisis.'
                ],
                'Inferior al Término Medio' => [
                    'interpretation' => 'Dificultades para realizar evaluaciones precisas y confiables.',
                    'recommendation' => 'Entrenamiento en métodos básicos de evaluación con prácticas supervisadas.'
                ],
                'Inferior' => [
                    'interpretation' => 'Evaluaciones inconsistentes y poco precisas, afectando la toma de decisiones.',
                    'recommendation' => 'Capacitación intensiva en procesos de evaluación y análisis crítico.'
                ],
                'Deficiente' => [
                    'interpretation' => 'Incapacidad para evaluar, lo que puede generar errores graves en decisiones organizacionales.',
                    'recommendation' => 'Reevaluar las responsabilidades asignadas y realizar un plan de desarrollo intensivo.'
                ],
            ],
            'Habilidad de Relacionarse' => [
                'Excelente' => [
                    'interpretation' => 'Habilidad sobresaliente para interactuar con otros, establecer conexiones sólidas y resolver conflictos de manera efectiva.',
                    'recommendation' => 'Asignar tareas que requieran mediación, liderazgo en equipo, o gestión de relaciones clave con clientes.'
                ],
                'Superior' => [
                    'interpretation' => 'Relaciones interpersonales destacadas, aunque puede haber pequeñas áreas de mejora en situaciones de alta complejidad.',
                    'recommendation' => 'Proveer oportunidades para representar a la organización en eventos clave o liderar proyectos colaborativos.'
                ],
                'Superior al Término Medio' => [
                    'interpretation' => 'Competente en interacciones sociales, pero puede mostrar inconsistencias en contextos de alta presión o incertidumbre.',
                    'recommendation' => 'Implementar capacitaciones avanzadas en empatía, manejo de conflictos y comunicación asertiva.'
                ],
                'Término Medio' => [
                    'interpretation' => 'Capacidad básica para mantener relaciones, pero limitada en contextos desafiantes o dinámicas complejas.',
                    'recommendation' => 'Ofrecer talleres sobre habilidades interpersonales, con un enfoque en empatía y escucha activa.'
                ],
                'Inferior al Término Medio' => [
                    'interpretation' => 'Dificultad para establecer relaciones positivas o resolver conflictos, lo que afecta la colaboración en equipo.',
                    'recommendation' => 'Entrenamiento en habilidades sociales y sesiones de coaching en manejo de relaciones.'
                ],
                'Inferior' => [
                    'interpretation' => 'Relaciones interpersonales deficientes, con impacto negativo en la dinámica del equipo y la comunicación.',
                    'recommendation' => 'Supervisión cercana, mentoría directa y asignación de roles con interacción limitada inicialmente.'
                ],
                'Deficiente' => [
                    'interpretation' => 'Incapacidad para relacionarse adecuadamente con otros, generando conflictos o aislamiento.',
                    'recommendation' => 'Plan de intervención intensiva con coaching especializado y actividades prácticas de integración.'
                ],
            ],
            'Sentido Común y Tacto' => [
                'Excelente' => [
                    'interpretation' => 'Capacidad sobresaliente para aplicar juicio práctico y manejar situaciones delicadas con sensibilidad.',
                    'recommendation' => 'Asignar roles que requieran resolución de conflictos o toma de decisiones críticas con impacto humano.'
                ],
                'Superior' => [
                    'interpretation' => 'Maneja situaciones con tacto y sentido común en la mayoría de los contextos.',
                    'recommendation' => 'Delegar tareas donde la diplomacia y la empatía sean esenciales, como atención a clientes o mediación.'
                ],
                'Superior al Término Medio' => [
                    'interpretation' => 'Buen juicio práctico y tacto en general, pero puede carecer de refinamiento en circunstancias excepcionales.',
                    'recommendation' => 'Brindar oportunidades para practicar toma de decisiones en entornos desafiantes.'
                ],
                'Término Medio' => [
                    'interpretation' => 'Sentido común adecuado, pero con falta de consistencia en situaciones complejas.',
                    'recommendation' => 'Ofrecer capacitación en habilidades de resolución de problemas y toma de decisiones práctica.'
                ],
                'Inferior al Término Medio' => [
                    'interpretation' => 'Dificultad para aplicar juicio práctico en situaciones cotidianas y resolver problemas con tacto.',
                    'recommendation' => 'Implementar talleres de desarrollo de juicio crítico y empatía.'
                ],
                'Inferior' => [
                    'interpretation' => 'Frecuentes errores de juicio práctico y falta de tacto en sus interacciones.',
                    'recommendation' => 'Reforzar habilidades de juicio a través de simulaciones y ejercicios guiados.'
                ],
                'Deficiente' => [
                    'interpretation' => 'Incapacidad para manejar situaciones con sentido común, lo que puede afectar relaciones y decisiones.',
                    'recommendation' => 'Requiere intervención inmediata con coaching intensivo y seguimiento cercano.'
                ],
            ],
        ];


        // 2. OBTENER ACIERTOS DEL USUARIO
        // Necesitamos unir con 'questions' para saber el número de pregunta ('order')
        $userAnswers = EvaluationUserAnswer::where('psychometric_evaluation_id', $evaluation->id)
            ->join('answers', 'evaluation_user_answers.answer_id', '=', 'answers.id')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->where('answers.weight', '>', 0) // Solo traemos las correctas (Aciertos)
            ->select('questions.order', 'answers.weight')
            ->get();

        $totalRawScore = 0;

        // 3. PROCESAMIENTO
        foreach ($userAnswers as $ans) {
            $qNum = $ans->order; // Número de pregunta (1 al 30)

            // Verificamos a qué dimensión pertenece
            if (isset($map[$qNum])) {
                $dimId = $map[$qNum];
                $dimensions[$dimId]['score'] += $ans->weight; // Sumamos 1 punto
            }
            $totalRawScore += $ans->weight;
        }

        // 4. PREPARAR RESULTADOS POR DIMENSIÓN (Porcentaje de efectividad)
        $dimensionScores = [];
        foreach ($dimensions as $key => $data) {
            // Calculamos porcentaje: (Aciertos / Total Preguntas de esa área) * 100
            $percentage = ($data['score'] > 0)
                ? round(($data['score'] / $data['total_questions']) * 100)
                : 0;

            // Determinación del rango basado en el porcentaje de la dimensión
            $dimensionRange = '';
            $dimensionPercentile = 0;

            if ($percentage <= 24) {
                $dimensionPercentile = 5;
                $dimensionRange = 'Deficiente';
            } elseif ($percentage <= 39) {
                $dimensionPercentile = 10;
                $dimensionRange = 'Inferior';
            } elseif ($percentage <= 49) {
                $dimensionPercentile = 20;
                $dimensionRange = 'Inferior al Término Medio';
            } elseif ($percentage <= 59) {
                $dimensionPercentile = 30;
                $dimensionRange = 'Término Medio';
            } elseif ($percentage <= 64) {
                $dimensionPercentile = 40;
                $dimensionRange = 'Superior al Término Medio';
            } elseif ($percentage <= 89) {
                $dimensionPercentile = 50;
                $dimensionRange = 'Superior';
            } else {
                $dimensionPercentile = 60;
                $dimensionRange = 'Excelente';
            }

            $dimensionScores[$data['name']] = [
                'completeName' => $data['completeName'],
                'description' => $data['description'],
                'percentage' => $percentage,
                'range' => $dimensionRange,
                'interpretation' => $feedbackMatrix[$data['name']][$dimensionRange]['interpretation'],
                'recommendation' => $feedbackMatrix[$data['name']][$dimensionRange]['recommendation'],
                'percentile' => $dimensionPercentile,
                'raw_score' => $data['score']
            ];
        }

        // 5. RANGO GLOBAL (Percentil General)
        // Tabla de baremos estándar (Ajustar si tu imagen tiene otros rangos)
        $percentile = 0;
        $range = '';

        if ($totalRawScore <= 7) { $percentile = 10;  $range = 'Deficiente'; }
        elseif ($totalRawScore <= 11) { $percentile = 25; $range = 'Inferior'; }
        elseif ($totalRawScore <= 14) { $percentile = 40; $range = 'Inferior al Término Medio'; }
        elseif ($totalRawScore <= 17) { $percentile = 50; $range = 'Término Medio'; }
        elseif ($totalRawScore <= 22) { $percentile = 60; $range = 'Superior al Término Medio'; }
        elseif ($totalRawScore <= 26) { $percentile = 75; $range = 'Superior'; }
        elseif ($totalRawScore <= 30) { $percentile = 90; $range = 'Excelente'; }


        return [
            'test_name' => 'Moss (Habilidades Gerenciales)',
            'chart_type' => 'bar', // Cambiamos a barras para ver las 5 dimensiones
            'raw_score' => $totalRawScore,
            'percentile' => $percentile,
            'range' => $range,
            'scores' => $dimensionScores, // Aquí va el array de las 5 dimensiones con sus %
            'summary' => "Rango Global: $range ($percentile%)"
        ];
    }

    public function calculateCleaver($evaluation)
    {
        // 1. Cargar configuraciones y diccionarios
        $originalDictionary = config('cleaver.plantilla');
        $percentilesDB = config('cleaver.percentiles');
        $interpretationsDB = config('cleaver.interpretations'); // Asegúrate de que el nombre del config sea el correcto

        // 2. Normalizar la plantilla de palabras a mayúsculas
        $dictionary = [];
        foreach ($originalDictionary as $word => $values) {
            $normalizedWord = mb_strtoupper(trim($word), 'UTF-8');
            $dictionary[$normalizedWord] = $values;
        }

        // 3. Consulta a la base de datos para obtener las respuestas
        $answers = EvaluationUserAnswer::where('psychometric_evaluation_id', $evaluation->id)
            ->join('answers', 'evaluation_user_answers.answer_id', '=', 'answers.id')
            ->join('questions', 'answers.question_id', '=', 'questions.id')
            ->select('questions.order', 'evaluation_user_answers.attribute', 'answers.text')
            ->get();

        // 4. Inicializar contadores de puntuación bruta
        $scoresMas = ['D' => 0, 'I' => 0, 'S' => 0, 'C' => 0];
        $scoresMenos = ['D' => 0, 'I' => 0, 'S' => 0, 'C' => 0];

        // 5. Iterar y evaluar cada respuesta
        foreach ($answers as $ans) {
            $word = mb_strtoupper(trim($ans->text), 'UTF-8');
            $selectionAttribute = strtoupper($ans->attribute);

            // Determinar si fue "MÁS" (Most) o "MENOS" (Least)
            $selectionType = in_array($selectionAttribute, ['MOST', 'MAS', 'M']) ? 'MOST' :
                (in_array($selectionAttribute, ['LEAST', 'MENOS', 'L']) ? 'LEAST' : null);

            if (!$selectionType || !isset($dictionary[$word])) {
                continue; // Si no es válido o no está en el diccionario, saltar
            }

            $domainLetter = $dictionary[$word][$selectionType];

            if ($domainLetter) {
                if ($selectionType === 'MOST') {
                    $scoresMas[$domainLetter]++;
                } else {
                    $scoresMenos[$domainLetter]++;
                }
            }
        }

        // 6. Calcular T (Comportamiento Diario / Total) = M - L
        $scoresTotales = [
            'D' => $scoresMas['D'] - $scoresMenos['D'],
            'I' => $scoresMas['I'] - $scoresMenos['I'],
            'S' => $scoresMas['S'] - $scoresMenos['S'],
            'C' => $scoresMas['C'] - $scoresMenos['C'],
        ];

        // 7. VALIDACIÓN CRÍTICA DEL MANUAL CLEAVER
        $sumaT = array_sum($scoresTotales);
        $isValid = ($sumaT >= -3 && $sumaT <= 3);

        if (!$isValid) {
            \Illuminate\Support\Facades\Log::warning("Evaluación Cleaver ID: {$evaluation->id} es INVÁLIDA. Suma de T = {$sumaT}. Posible manipulación o confusión del candidato.");
        }

        // 8. Convertir Puntuación Bruta a Percentiles (Función anónima de seguridad)
        $getPercentile = function($type, $domain, $score) use ($percentilesDB) {
            $table = $percentilesDB[$type][$domain] ?? [];
            if (empty($table)) return 0;

            if (isset($table[$score])) {
                return $table[$score];
            }

            // Fallback de seguridad si el puntaje sale de los límites
            $minKey = min(array_keys($table));
            $maxKey = max(array_keys($table));
            return $score < $minKey ? $table[$minKey] : $table[$maxKey];
        };

        $percentilesMas = [
            'D' => $getPercentile('M', 'D', $scoresMas['D']),
            'I' => $getPercentile('M', 'I', $scoresMas['I']),
            'S' => $getPercentile('M', 'S', $scoresMas['S']),
            'C' => $getPercentile('M', 'C', $scoresMas['C']),
        ];

        $percentilesMenos = [
            'D' => $getPercentile('L', 'D', $scoresMenos['D']),
            'I' => $getPercentile('L', 'I', $scoresMenos['I']),
            'S' => $getPercentile('L', 'S', $scoresMenos['S']),
            'C' => $getPercentile('L', 'C', $scoresMenos['C']),
        ];

        $percentilesTotales = [
            'D' => $getPercentile('T', 'D', $scoresTotales['D']),
            'I' => $getPercentile('T', 'I', $scoresTotales['I']),
            'S' => $getPercentile('T', 'S', $scoresTotales['S']),
            'C' => $getPercentile('T', 'C', $scoresTotales['C']),
        ];

        // 9. Generar las interpretaciones completas (Enviamos los 3 arreglos de percentiles)
        $interpretations = $this->generateInterpretations(
            $percentilesMas,
            $percentilesMenos,
            $percentilesTotales,
            $interpretationsDB
        );

        // 10. Retornar el payload completo para Filament / Frontend
        $data = [
            'test_name'       => 'Cleaver (DISC)',
            'is_valid'        => $isValid, // Bandera para mostrar alerta en UI si es false
            'suma_t'          => $sumaT,
            'chart_type'      => 'bar',
            'raw_scores'      => [
                'M' => $scoresMas,
                'L' => $scoresMenos,
                'T' => $scoresTotales,
            ],
            'percentiles'     => [
                'M' => $percentilesMas,
                'L' => $percentilesMenos,
                'T' => $percentilesTotales,
            ],
            'scores'          => $percentilesTotales, // Para compatibilidad con tu gráfica
            'interpretations' => $interpretations, // Textos estructurados por M, L y T
            'summary'         => "D:{$percentilesTotales['D']}% I:{$percentilesTotales['I']}% S:{$percentilesTotales['S']}% C:{$percentilesTotales['C']}%"
        ];
        return $data;
    }

    /**
     * Helper privado para procesar las interpretaciones de Motivación (M), Presión (L) y Comportamiento Diario (T).
     */
    private function generateInterpretations($percentilesMas, $percentilesMenos, $percentilesTotales, $interpretationsDB)
    {
        $result = [];
        $domains = ['D', 'I', 'S', 'C'];
        $flattenedCount = 0;

        // Validación defensiva por si el config no se cargó correctamente
        if (!$interpretationsDB) {
            \Illuminate\Support\Facades\Log::error("CRÍTICO: No se cargó config('cleaver.interpretations'). Revisa la caché o la ruta del archivo.");
            $interpretationsDB = [];
        }

        foreach ($domains as $domain) {
            // 1. Extraer los puntajes de las 3 gráficas
            $scoreM = $percentilesMas[$domain];
            $scoreL = $percentilesMenos[$domain];
            $scoreT = $percentilesTotales[$domain];

            // 2. Determinar si cruzaron la línea media (50)
            $levelM = ($scoreM > 50) ? 'high' : 'low';
            $levelL = ($scoreL > 50) ? 'high' : 'low';
            $levelT = ($scoreT > 50) ? 'high' : 'low';

            // 3. Detectar aplanamiento basándonos en la gráfica T
            if ($scoreT >= 40 && $scoreT <= 60) {
                $flattenedCount++;
            }

            // 4. Armar el árbol de interpretación con protección contra nulos (??)
            $result[$domain] = [
                'name' => $interpretationsDB[$domain]['name'] ?? 'Nombre de dominio no disponible',

                // --- Análisis de MOTIVACIÓN (M) ---
                'motivacion' => [
                    'score' => $scoreM,
                    'title' => ($levelM === 'high') ? "Alta ({$domain}+)" : "Baja ({$domain}-)",
                    'text'  => $interpretationsDB['situational'][$domain]['M'][$levelM] ?? 'Interpretación situacional no disponible.'
                ],

                // --- Análisis de PRESIÓN (L) ---
                'presion' => [
                    'score' => $scoreL,
                    'title' => ($levelL === 'high') ? "Alta ({$domain}+)" : "Baja ({$domain}-)",
                    'text'  => $interpretationsDB['situational'][$domain]['L'][$levelL] ?? 'Interpretación situacional no disponible.'
                ],

                // --- Análisis de COMPORTAMIENTO DIARIO (T) ---
                'diario' => [
                    'score'    => $scoreT,
                    'title'    => $interpretationsDB[$domain][$levelT]['title'] ?? 'N/A',
                    'traits'   => $interpretationsDB[$domain][$levelT]['traits'] ?? 'N/A',
                    'behavior' => $interpretationsDB[$domain][$levelT]['behavior'] ?? 'N/A',
                    'text'     => $interpretationsDB['situational']['T']['general'] ?? 'Esta puntuación refleja su comportamiento natural y diario en condiciones normales de trabajo.'
                ]
            ];
        }

        // Si 3 o más factores están aplanados en T, agregamos la alerta
        if ($flattenedCount >= 3 && isset($interpretationsDB['alerts']['flattened_profile'])) {
            $result['alert'] = $interpretationsDB['alerts']['flattened_profile'];
        }

        return $result;
    }

    // =========================================================================
    // TERMAN-MERRIL — Medición de Inteligencia (CI)
    // IDs de competencias: 56=Serie I … 65=Serie X
    // =========================================================================
    private function calculateTerman(PsychometricEvaluation $evaluation): array
    {
        // ─── TABLAS DE BAREMOS ───────────────────────────────────────────────

        // Convierte puntaje bruto → puntaje de CI
        $equivalencias = [
            67 => 80,  68 => 80,  69 => 80,  70 => 81,  71 => 81,  72 => 82,  73 => 82,  74 => 82,
            75 => 83,  76 => 83,  77 => 84,  78 => 84,  79 => 84,  80 => 84,  81 => 85,  82 => 85,
            83 => 86,  84 => 86,  85 => 86,  86 => 87,  87 => 88,  88 => 88,  89 => 88,  90 => 88,
            91 => 89,  92 => 89,  93 => 89,  94 => 90,  95 => 90,  96 => 90,  97 => 91,  98 => 91,
            99 => 91, 100 => 92, 101 => 92, 102 => 92, 103 => 93, 104 => 93, 105 => 94, 106 => 94,
           107 => 95, 108 => 95, 109 => 95, 110 => 95, 111 => 96, 112 => 96, 113 => 96, 114 => 96,
           115 => 97, 116 => 97, 117 => 98, 118 => 98, 119 => 98, 120 => 99, 121 => 99, 122 => 99,
           123 => 99, 124 => 100, 125 => 100, 126 => 101, 127 => 101, 128 => 101, 129 => 101,
           130 => 102, 131 => 102, 132 => 102, 133 => 102, 134 => 103, 135 => 103, 136 => 103,
           137 => 103, 138 => 104, 139 => 104, 140 => 104, 141 => 104, 142 => 105, 143 => 105,
           144 => 105, 145 => 105, 146 => 106, 147 => 106, 148 => 106, 149 => 106, 150 => 107,
           151 => 107, 152 => 107, 153 => 107, 154 => 108, 155 => 108, 156 => 108, 157 => 108,
           158 => 109, 159 => 109, 160 => 110, 161 => 110, 162 => 110, 163 => 111, 164 => 111,
           165 => 111, 166 => 111, 167 => 112, 168 => 113, 169 => 113, 170 => 113, 171 => 114,
           172 => 114, 173 => 114, 174 => 115, 175 => 115, 176 => 116, 177 => 116, 178 => 117,
           179 => 117, 180 => 117, 181 => 118, 182 => 118, 183 => 118, 184 => 119, 185 => 119,
           186 => 120, 187 => 121, 188 => 122, 189 => 123, 190 => 124, 191 => 125, 192 => 126,
           193 => 127, 194 => 128, 195 => 129, 196 => 130, 197 => 131, 198 => 132, 199 => 133,
           200 => 134, 201 => 135, 202 => 136, 203 => 137, 204 => 138, 205 => 139, 206 => 140,
           207 => 141,
        ];

        // Rangos de CI → clasificación intelectual
        $rangos_ci = [
            ['min' => 140, 'max' => 999, 'clasificacion' => 'Sobresaliente'],
            ['min' => 120, 'max' => 139, 'clasificacion' => 'Superior'],
            ['min' => 110, 'max' => 119, 'clasificacion' => 'Término Medio Alto'],
            ['min' => 90,  'max' => 109, 'clasificacion' => 'Normal'],
            ['min' => 80,  'max' => 89,  'clasificacion' => 'Término Medio Bajo'],
            ['min' => 70,  'max' => 79,  'clasificacion' => 'Inferior'],
            ['min' => 0,   'max' => 69,  'clasificacion' => 'Deficiente'],
        ];

        // Rangos puntaje bruto → capacidad de aprendizaje
        $rangos_capacidad = [
            ['min' => 172, 'max' => 186, 'clasificacion' => 'Sobresaliente'],
            ['min' => 151, 'max' => 171, 'clasificacion' => 'Superior'],
            ['min' => 137, 'max' => 150, 'clasificacion' => 'Término Medio Alto'],
            ['min' => 123, 'max' => 136, 'clasificacion' => 'Normal'],
            ['min' => 102, 'max' => 122, 'clasificacion' => 'Término Medio Bajo'],
            ['min' => 95,  'max' => 101, 'clasificacion' => 'Inferior'],
            ['min' => 67,  'max' => 94,  'clasificacion' => 'Deficiente'],
        ];

        // Rangos por serie (clave = número de serie 1-10, valor = baremos por nivel)
        $rangos_por_serie = [
            1  => ['Sobresaliente' => ['min' => 16, 'max' => 16], 'Superior' => ['min' => 15, 'max' => 15], 'Término Medio Alto' => ['min' => 14, 'max' => 14], 'Normal' => ['min' => 12, 'max' => 13], 'Término Medio Bajo' => ['min' => 10, 'max' => 11], 'Inferior' => ['min' => 8,  'max' => 9],  'Deficiente' => ['min' => 0, 'max' => 7]],
            2  => ['Sobresaliente' => ['min' => 22, 'max' => 22], 'Superior' => ['min' => 20, 'max' => 20], 'Término Medio Alto' => ['min' => 18, 'max' => 18], 'Normal' => ['min' => 12, 'max' => 16], 'Término Medio Bajo' => ['min' => 10, 'max' => 10], 'Inferior' => ['min' => 8,  'max' => 8],  'Deficiente' => ['min' => 0, 'max' => 6]],
            3  => ['Sobresaliente' => ['min' => 29, 'max' => 30], 'Superior' => ['min' => 27, 'max' => 28], 'Término Medio Alto' => ['min' => 23, 'max' => 26], 'Normal' => ['min' => 14, 'max' => 22], 'Término Medio Bajo' => ['min' => 12, 'max' => 13], 'Inferior' => ['min' => 8,  'max' => 11], 'Deficiente' => ['min' => 0, 'max' => 7]],
            4  => ['Sobresaliente' => ['min' => 18, 'max' => 18], 'Superior' => ['min' => 16, 'max' => 17], 'Término Medio Alto' => ['min' => 14, 'max' => 15], 'Normal' => ['min' => 10, 'max' => 13], 'Término Medio Bajo' => ['min' => 7,  'max' => 9],  'Inferior' => ['min' => 6,  'max' => 6],  'Deficiente' => ['min' => 0, 'max' => 5]],
            5  => ['Sobresaliente' => ['min' => 24, 'max' => 24], 'Superior' => ['min' => 20, 'max' => 22], 'Término Medio Alto' => ['min' => 16, 'max' => 18], 'Normal' => ['min' => 12, 'max' => 14], 'Término Medio Bajo' => ['min' => 8,  'max' => 10], 'Inferior' => ['min' => 6,  'max' => 6],  'Deficiente' => ['min' => 0, 'max' => 4]],
            6  => ['Sobresaliente' => ['min' => 20, 'max' => 20], 'Superior' => ['min' => 18, 'max' => 19], 'Término Medio Alto' => ['min' => 15, 'max' => 17], 'Normal' => ['min' => 9,  'max' => 14], 'Término Medio Bajo' => ['min' => 7,  'max' => 8],  'Inferior' => ['min' => 5,  'max' => 6],  'Deficiente' => ['min' => 0, 'max' => 4]],
            7  => ['Sobresaliente' => ['min' => 19, 'max' => 20], 'Superior' => ['min' => 18, 'max' => 18], 'Término Medio Alto' => ['min' => 16, 'max' => 17], 'Normal' => ['min' => 9,  'max' => 15], 'Término Medio Bajo' => ['min' => 6,  'max' => 8],  'Inferior' => ['min' => 5,  'max' => 5],  'Deficiente' => ['min' => 0, 'max' => 4]],
            8  => ['Sobresaliente' => ['min' => 17, 'max' => 17], 'Superior' => ['min' => 15, 'max' => 16], 'Término Medio Alto' => ['min' => 13, 'max' => 14], 'Normal' => ['min' => 8,  'max' => 12], 'Término Medio Bajo' => ['min' => 7,  'max' => 7],  'Inferior' => ['min' => 6,  'max' => 6],  'Deficiente' => ['min' => 0, 'max' => 5]],
            9  => ['Sobresaliente' => ['min' => 18, 'max' => 18], 'Superior' => ['min' => 17, 'max' => 17], 'Término Medio Alto' => ['min' => 16, 'max' => 16], 'Normal' => ['min' => 10, 'max' => 15], 'Término Medio Bajo' => ['min' => 9,  'max' => 9],  'Inferior' => ['min' => 7,  'max' => 8],  'Deficiente' => ['min' => 0, 'max' => 6]],
            10 => ['Sobresaliente' => ['min' => 20, 'max' => 22], 'Superior' => ['min' => 18, 'max' => 18], 'Término Medio Alto' => ['min' => 16, 'max' => 16], 'Normal' => ['min' => 10, 'max' => 14], 'Término Medio Bajo' => ['min' => 8,  'max' => 8],  'Inferior' => ['min' => 6,  'max' => 6],  'Deficiente' => ['min' => 0, 'max' => 4]],
        ];

        // Mapa competence_id → número de serie (56=I … 65=X)
        $competenceToSerie = [
            56 => 1, 57 => 2, 58 => 3, 59 => 4, 60 => 5,
            61 => 6, 62 => 7, 63 => 8, 64 => 9, 65 => 10,
        ];

        // Nombres descriptivos de cada serie
        $serieNombres = [
            1  => 'Serie I — Información',
            2  => 'Serie II — Juicio',
            3  => 'Serie III — Vocabulario',
            4  => 'Serie IV — Síntesis',
            5  => 'Serie V — Concentración',
            6  => 'Serie VI — Análisis',
            7  => 'Serie VII — Abstracción',
            8  => 'Serie VIII — Planeación',
            9  => 'Serie IX — Organización',
            10 => 'Serie X — Atención',
        ];

        // ─── PASO 1: OBTENER RESPUESTAS DEL CANDIDATO ────────────────────────
        // Unimos las respuestas del usuario con:
        //   - answers   → is_correct y weight
        //   - questions → competence_id para saber a qué serie pertenece
        $userAnswers = EvaluationUserAnswer::where('psychometric_evaluation_id', $evaluation->id)
            ->whereNotNull('answer_id')
            ->join('answers', 'evaluation_user_answers.answer_id', '=', 'answers.id')
            ->join('questions', 'evaluation_user_answers.question_id', '=', 'questions.id')
            ->select(
                'questions.competence_id',
                'answers.is_correct',
                'answers.weight'
            )
            ->get();

        // ─── PASO 2: ACUMULAR PUNTAJES POR SERIE ─────────────────────────────
        $porSerie = [];
        foreach ($competenceToSerie as $compId => $serieNum) {
            $porSerie[$serieNum] = 0;
        }

        $puntajeBruto = 0;

        foreach ($userAnswers as $row) {
            if (! $row->is_correct) {
                continue;
            }
            $peso     = (float) ($row->weight ?? 1);
            $serieNum = $competenceToSerie[$row->competence_id] ?? null;
            if ($serieNum === null) {
                continue;
            }
            $porSerie[$serieNum] += $peso;
            $puntajeBruto        += $peso;
        }

        // Redondear a enteros (los pesos son 1 o 2)
        $puntajeBruto = (int) round($puntajeBruto);
        foreach ($porSerie as $k => $v) {
            $porSerie[$k] = (int) round($v);
        }

        // ─── PASO 3: CALCULAR CI desde tabla de equivalencias ─────────────────
        $ciScore = null;
        if (isset($equivalencias[$puntajeBruto])) {
            $ciScore = $equivalencias[$puntajeBruto];
        } elseif ($puntajeBruto < min(array_keys($equivalencias))) {
            $ciScore = min(array_values($equivalencias)); // límite inferior → 80
        } elseif ($puntajeBruto > max(array_keys($equivalencias))) {
            $ciScore = max(array_values($equivalencias)); // límite superior → 141
        }

        // ─── PASO 4: CLASIFICACIÓN CI ─────────────────────────────────────────
        $clasificacionCI = 'Sin clasificación';
        if ($ciScore !== null) {
            foreach ($rangos_ci as $rango) {
                if ($ciScore >= $rango['min'] && $ciScore <= $rango['max']) {
                    $clasificacionCI = $rango['clasificacion'];
                    break;
                }
            }
        }

        // ─── PASO 5: CLASIFICACIÓN CAPACIDAD DE APRENDIZAJE ──────────────────
        $clasificacionCapacidad = 'Sin clasificación';
        foreach ($rangos_capacidad as $rango) {
            if ($puntajeBruto >= $rango['min'] && $puntajeBruto <= $rango['max']) {
                $clasificacionCapacidad = $rango['clasificacion'];
                break;
            }
        }

        // ─── PASO 6: CLASIFICACIÓN POR SERIE ─────────────────────────────────
        $seriesResultados = [];
        foreach ($porSerie as $serieNum => $puntajeSerie) {
            $clasificacionSerie = 'Sin clasificación';
            if (isset($rangos_por_serie[$serieNum])) {
                foreach ($rangos_por_serie[$serieNum] as $nivel => $rango) {
                    if ($puntajeSerie >= $rango['min'] && $puntajeSerie <= $rango['max']) {
                        $clasificacionSerie = $nivel;
                        break;
                    }
                }
            }
            $seriesResultados[$serieNum] = [
                'nombre'        => $serieNombres[$serieNum] ?? "Serie {$serieNum}",
                'puntaje'       => $puntajeSerie,
                'clasificacion' => $clasificacionSerie,
            ];
        }

        ksort($seriesResultados);

        // ─── PASO 7: RETORNO ──────────────────────────────────────────────────
        return [
            'test_name'               => 'Terman-Merril (CI)',
            'puntaje_bruto'           => $puntajeBruto,
            'ci_score'                => $ciScore,
            'clasificacion_ci'        => $clasificacionCI,
            'clasificacion_capacidad' => $clasificacionCapacidad,
            'series'                  => $seriesResultados,
            'resumen'                 => "Puntaje bruto: {$puntajeBruto} | CI: {$ciScore} | Clasificación: {$clasificacionCI} | Capacidad de aprendizaje: {$clasificacionCapacidad}",
        ];
    }
}
