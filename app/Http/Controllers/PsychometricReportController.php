<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PsychometricReportController extends Controller
{
    /**
     * Muestra la vista previa del reporte psicométrico.
     * Los datos vienen del cache (TTL 60 min), generados desde PsychometricDashboard.
     */
    public function show(string $key)
    {
        $data = Cache::get("psych_report_{$key}");

        if (! $data) {
            abort(404, 'El reporte no existe o ha expirado. Genera uno nuevo desde el Dashboard.');
        }

        return view('psychometric.report-preview', [
            'reportKey'           => $key,
            'reportData'          => $data['ai_report'] ?? null,
            'candidateData'       => $data['candidate_data'] ?? [],
            'psychometricResults' => $data['psychometric_results'] ?? [],
            'competencias'        => $data['competencias'] ?? [],
            'cleaverIdeal'        => $data['cleaver_ideal'] ?? ['D' => 50, 'I' => 50, 'S' => 50, 'C' => 50],
            'meta'                => $data['meta'] ?? [],
            'aiAvailable'         => ! empty($data['ai_report']),
            'ajusteGlobalPhp'     => $data['ajuste_global'] ?? 0,
            'ajusteRelativoPhp'   => $data['ajuste_relativo'] ?? 0,
            'dictamenPhp'         => $data['dictamen_calculado'] ?? 'Pendiente',
            'competenciasIdeal'   => $data['competencias_ideal'] ?? [],
            'isPdfExport'         => false,
        ]);
    }

    /**
     * Genera y descarga el PDF del reporte usando PDFShift.
     */
    public function downloadPdf(string $key)
    {
        $data = Cache::get("psych_report_{$key}");

        if (! $data) {
            abort(404, 'El reporte no existe o ha expirado.');
        }

        $html = view('psychometric.report-preview', [
            'reportKey'           => $key,
            'reportData'          => $data['ai_report'] ?? null,
            'candidateData'       => $data['candidate_data'] ?? [],
            'psychometricResults' => $data['psychometric_results'] ?? [],
            'competencias'        => $data['competencias'] ?? [],
            'meta'                => $data['meta'] ?? [],
            'aiAvailable'         => ! empty($data['ai_report']),
            'cleaverIdeal'        => $data['cleaver_ideal'] ?? ['D' => 50, 'I' => 50, 'S' => 50, 'C' => 50], // ← NUEVO
            'competenciasIdeal'   => $data['competencias_ideal'] ?? [],
            'ajusteGlobalPhp'     => $data['ajuste_global'] ?? 0,
            'ajusteRelativoPhp'   => $data['ajuste_relativo'] ?? 0,
            'dictamenPhp'         => $data['dictamen_calculado'] ?? 'Pendiente',
            'isPdfExport'         => true,
        ])->render();

        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        $payload = [
            'source'    => $html,
            'landscape' => false,
            'use_print' => true,
            'margin'    => ['top' => '15px', 'bottom' => '15px', 'left' => '15px', 'right' => '15px'],
            // "wait_for" en PDFShift espera el NOMBRE de una función JS global que
            // devuelva un valor truthy (no un selector CSS). Definimos esa función
            // para esperar a que Tailwind (CDN JIT) y Chart.js terminen de pintar
            // antes de capturar, evitando un layout desfasado en el PDF.
            'javascript' => "window.pdfshiftReportReady = function() { var el = document.getElementById('report-ready-flag'); return !!el && el.getAttribute('data-report-ready') === 'true'; };",
            'wait_for'  => 'pdfshiftReportReady',
            'delay'     => 800,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Key'    => config('services.pdfshift.api_key'),
            ])->timeout(60)
              ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
              ->post('https://api.pdfshift.io/v3/convert/pdf');
        } catch (\Throwable $e) {
            Log::error('[PDFSHIFT] Excepción al llamar a la API de PDFShift', [
                'report_key' => $key,
                'message'    => $e->getMessage(),
            ]);

            abort(500, 'No se pudo generar el PDF. Intenta de nuevo más tarde.');
        }

        if (! $response->successful()) {
            Log::error('[PDFSHIFT] Respuesta no exitosa al generar el PDF', [
                'report_key' => $key,
                'status'     => $response->status(),
                'body'       => $response->body(),
            ]);

            abort(500, 'No se pudo generar el PDF. Intenta de nuevo más tarde.');
        }

        $nombre = Str::slug($data['meta']['candidato'] ?? 'reporte');
        $puesto = Str::slug($data['meta']['puesto'] ?? 'general');

        return response()->streamDownload(
            fn () => print($response->body()),
            "Reporte_SEDYCO_{$nombre}_{$puesto}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    }
}

