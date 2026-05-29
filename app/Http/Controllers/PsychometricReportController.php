<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
            'isPdfExport'         => true,
        ])->render();

        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        $payload = [
            'source'    => $html,
            'landscape' => false,
            'use_print' => true,
            'margin'    => ['top' => '15px', 'bottom' => '15px', 'left' => '15px', 'right' => '15px'],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-Key'    => config('services.pdfshift.api_key'),
        ])->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
          ->post('https://api.pdfshift.io/v3/convert/pdf');

        if (! $response->successful()) {
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

