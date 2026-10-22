<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class QuejaViolenciaLaboralController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quejoso_nombre' => 'required|string|max:255',
            'quejoso_puesto' => 'required|string|max:255',
            'quejoso_telefono' => 'required|string|max:20',
            'quejoso_area' => 'required|string|max:255',
            'quejoso_jefe_inmediato' => 'required|string|max:255',
            'acusado_nombre' => 'required|string|max:255',
            'acusado_puesto' => 'required|string|max:255',
            'acusado_area' => 'required|string|max:255',
            'fecha_ocurrencia' => 'required|date',
            'hora_ocurrencia' => 'required',
            'lugar_ocurrencia' => 'required|string|max:255',
            'frecuencia' => 'required|string',
            'manifestacion_hostigamiento' => 'required|string',
            'actitud_hostigador' => 'required|string',
            'reaccion_inmediata' => 'required|string',
            'caso_aislado' => 'required|string',
            'afectacion_emocional' => 'required|string',
            'afectacion_rendimiento' => 'required|string',
            'causa_particular' => 'required|string',
            'percepcion_ambiente_laboral' => 'required|string',
            'afectacion_largo_plazo' => 'required|string',
            'necesita_apoyo_psicologico' => 'required|string',
        ]);

        try {
            Mail::send('emails.queja-violencia-laboral', ['data' => $validated], function ($message) use ($validated) {
                $message->to('contactorh@adcentrales.com')
                    ->subject('Nueva Queja de Violencia Laboral - ' . $validated['quejoso_nombre']);
            });

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
