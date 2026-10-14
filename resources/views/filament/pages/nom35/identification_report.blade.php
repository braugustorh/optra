<!DOCTYPE html>
<html lang="es">
<head>


    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte NOM-035-STPS-2018</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #ffffff;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2c5282;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #2c5282;
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header h2 {
            color: #4a5568;
            font-size: 14px;
            font-weight: normal;
        }

        /* Company Info */
        .company-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: bold;
            color: #2d3748;
            font-size: 10px;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .info-value {
            color: #4a5568;
            font-size: 12px;
            font-weight: 600;
        }

        /* Summary Cards */
        .summary-section {
            margin-bottom: 30px;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .summary-card.total {
            border-color: #3182ce;
        }

        .summary-card.no-clinical {
            border-color: #38a169;
        }

        .summary-card.clinical {
            border-color: #e53e3e;
        }

        .card-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .card-number.total { color: #3182ce; }
        .card-number.no-clinical { color: #38a169; }
        .card-number.clinical { color: #e53e3e; }

        .card-label {
            font-size: 10px;
            color: #4a5568;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Chart Container */
        .chart-container {
            background: #f7fafc;
            border: 2px solid #cbd5e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        /* PIE CHART - ¡Ahora funciona! */
        .pie-chart {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: conic-gradient(
                #38a169 0% {{$noClinicalPercent}}%,
                #e53e3e {{$noClinicalPercent}}% 100%
            );
            position: relative;
            margin: 0 auto 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .pie-legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 15px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            font-weight: 600;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 3px;
        }

        .legend-color.no-clinical { background: #38a169; }
        .legend-color.clinical { background: #e53e3e; }

        /* Page breaks for PDF */
        .page-break {
            page-break-before: always;
        }

        .page-break-after {
            page-break-after: always;
        }

        /* Results Table */
        .results-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 15px;
            text-align: center;
            background: #edf2f7;
            padding: 10px;
            border-radius: 8px;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .results-table th,
        .results-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 4px;
            text-align: center;
        }

        .results-table th {
            background: #2c5282;
            color: white;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }

        .results-table tr:nth-child(even) {
            background: #f7fafc;
        }

        .status-No {
            background: #c6f6d5;
            color: #22543d;
            font-weight: bold;
            border-radius: 3px;
            padding: 2px 4px;
        }

        .status-Sí {
            background: #fed7d7;
            color: #742a2a;
            font-weight: bold;
            border-radius: 3px;
            padding: 2px 4px;
        }

        .result-clinical {
            background: #feb2b2;
            color: #742a2a;
            font-weight: bold;
        }

        .result-no-clinical {
            background: #c6f6d5;
            color: #22543d;
            font-weight: bold;
        }

        /* Individual Report */
        .individual-report {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }

        .individual-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2c5282;
        }

        .individual-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .result-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }

        .result-badge.clinical {
            background: #fed7d7;
            color: #742a2a;
            border: 2px solid #e53e3e;
        }

        .result-badge.no-clinical {
            background: #c6f6d5;
            color: #22543d;
            border: 2px solid #38a169;
        }

        /* Print specific styles */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .container {
                padding: 15px;
            }

            .page-break {
                page-break-before: always;
                height: 0;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>Identificación de Sujetos Expuestos a Eventos Traumáticos Severos</h1>
        <h2>Norma Oficial Mexicana NOM-035-STPS-2018</h2>
    </div>

    <!-- Company Information -->
    <div class="company-info">
        <div class="info-item">
            <span class="info-label">Nombre de la empresa</span>
            <span class="info-value">{{ $company ?? 'ADC Administradora de Centrales' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Fecha de reporte</span>
            <span class="info-value">{{ $reportDate ?? '' }}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Período de aplicación</span>
            <span class="info-value">{{ $period ?? ''}}</span>
        </div>
        <div class="info-item">
            <span class="info-label">Total de encuestas</span>
            <span class="info-value">{{ $totalSurveys ?? '' }}</span>
        </div>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="summary-cards">
            <div class="summary-card total">
                <div class="card-number total">{{ $totalSurveys ?? '' }}</div>
                <div class="card-label">Total de Evaluaciones</div>
            </div>
            <div class="summary-card no-clinical">
                <div class="card-number no-clinical">{{ $noClinical ?? '' }}</div>
                <div class="card-label">No Requieren Valoración Clínica</div>
            </div>
            <div class="summary-card clinical">
                <div class="card-number clinical">{{ $clinical ?? '' }}</div>
                <div class="card-label">Requieren Valoración Clínica</div>
            </div>
        </div>

        <!-- Chart - ¡La gráfica de pastel ahora funciona! -->
        <div class="chart-container">
            <div class="pie-chart"></div>
            <div class="pie-legend">
                <div class="legend-item">
                    <div class="legend-color no-clinical"></div>
                   <span>No Requiere Valoración ({{ isset($noClinicalPercent) && $noClinicalPercent !== null ?$noClinicalPercent :'0' }}%)</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color clinical"></div>
                    <span>Requiere Valoración ({{ isset($clinicalPercent) && $clinicalPercent !== null ?$clinicalPercent :'0' }}%)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Page break before results table -->
    <div class="page-break"></div>

    <!-- Results Table -->
    <div class="results-section">
        <h3 class="section-title">RESULTADO GENERAL - Guía de Referencia I</h3>
        <table class="results-table">
            <thead>
            <tr>
                <th>No.</th>
                <th>Encuestado</th>
                <th>Sección I</th>
                <th>Sección II</th>
                <th>Sección III</th>
                <th>Sección IV</th>
                <th>Resultado</th>
            </tr>
            </thead>
            <tbody>

            @foreach($totalPersonsSurvey ?? [] as $index=> $employee)

                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $employee['name'] ?? '' }}</td>
                    <td><span class="status-{{ $employee['section1'] ?? 'false' }}">{{ strtoupper($employee['section1'] ?? 'No') }}</span></td>
                    <td><span class="status-{{ $employee['section2'] ?? 'false' }}">{{ strtoupper($employee['section2'] ?? 'No') }}</span></td>
                    <td><span class="status-{{ $employee['section3'] ?? 'false' }}">{{ strtoupper($employee['section3'] ?? 'No') }}</span></td>
                    <td><span class="status-{{ $employee['section4'] ?? 'false' }}">{{ strtoupper($employee['section4'] ?? 'No') }}</span></td>
                    <td class="result-{{ $employee['requires_clinical'] ? 'clinical' : 'no-clinical' }}">
                        {{ $employee['requires_clinical'] ? 'Requiere valoración clínica' : 'No requiere valoración clínica' }}
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if(isset($individualEmployees))
        <!-- Page break before individual report -->
        @foreach($individualEmployees as $individualEmployee)
            <div class="page-break"></div>
            <div class="individual-report">
                <div class="individual-header">
                    <h3 class="section-title">RESULTADO INDIVIDUAL</h3>
                    <p>Guía de Referencia I - Norma Oficial Mexicana NOM-035-STPS-2018</p>
                </div>

                <div class="individual-info">
                    <div class="info-item">
                        <span class="info-label">Nombre de la empresa</span>
                        <span class="info-value">{{ $company ?? 'adc Administradora de Centrales' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Nombre del encuestado</span>
                        <span class="info-value">{{ $individualEmployee['name'] ?? '' }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Fecha de aplicación</span>
                        <span class="info-value">{{ $reportDate ?? null }}</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Puesto</span>
                        <span class="info-value">{{ $individualEmployee['position'] ?? '' }}</span>
                    </div>
                    @if(!empty($individualEmployee['event_type']))
                    <div class="info-item">
                        <span class="info-label">Acontecimiento Identificado</span>
                        <span class="info-value">{{ $individualEmployee['event_type'] }}</span>
                    </div>
                    @endif
                    <div class="info-item">
                        <span class="info-label">Resultado</span>
                        <span class="result-badge {{ $individualEmployee['requires_clinical'] ? 'clinical' : 'no-clinical' }}">
                        {{ $individualEmployee['requires_clinical'] ? 'Requiere valoración clínica' : 'No requiere valoración clínica' }}
                    </span>
                    </div>

                </div>

                <div class="summary-section" style="text-align: center;">
                    <div class="info-item" style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <span class="info-label" style="margin-right: 10px;"><strong>Fecha de entrega de resultado</strong></span>

                        <textarea class="info-value" style="border: 1px solid #e2e8f0; margin-bottom: 10px; border-radius: 4px; padding: 4px 8px; width: 100%; min-height: 80px;"></textarea>
                    </div>

                    <div class="info-item" style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <span class="info-label" style="margin-right: 10px;"><strong>Fecha en que asiste a su atención</strong></span>
                        <textarea class="info-value" style="border: 1px solid #e2e8f0; margin-bottom: 10px; border-radius: 4px; padding: 4px 8px; width: 100%; min-height: 80px;"></textarea>
                    </div>
                    <div class="info-item" style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <span class="info-label" style="margin-right: 10px;"><strong>A qué lugar asiste a atención</strong></span>
                        <textarea class="info-value" style="border: 1px solid #e2e8f0; margin-bottom: 10px; border-radius: 4px; padding: 4px 8px; width: 100%; min-height: 80px;"></textarea>
                    </div>
                    <div class="info-item" style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <span class="info-label" style="margin-right: 10px;"><strong>Documento que acredita</strong></span>
                        <textarea class="info-value" style="border: 1px solid #e2e8f0; margin-bottom: 10px; border-radius: 4px; padding: 4px 8px; width: 100%; min-height: 80px;"></textarea>
                    </div>
                    <div class="info-item" style="display: flex; align-items: center; justify-content: center; margin-bottom: 10px;">
                        <span class="info-label" style="margin-right: 10px;"><strong>Diagnóstico de la Institución o del Médico</strong></span>
                        <textarea class="info-value" style="border: 1px solid #e2e8f0; margin-bottom: 10px; border-radius: 4px; padding: 4px 8px; width: 100%; min-height: 80px;"></textarea>
                    </div>
                </div>
            </div>
        @endforeach

    @endif
</div>
</body>
</html>

