<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header { background-color: #4F46E5; padding: 20px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; }
        .content { padding: 30px; }
        .button { display: inline-block; padding: 12px 24px; background-color: #4F46E5; color: #ffffff !important; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        .footer { background-color: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #6b7280; }
        .link-text { word-break: break-all; color: #4F46E5; font-size: 12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Evaluación Asignada</h1>
    </div>

    <div class="content">
        <p>Hola, <strong>{{ $evaluable->name }}</strong>.</p>

        <p>Se te ha asignado una batería de evaluaciones psicométricas como parte de tu proceso en <strong>Consorcio Optra</strong>.</p>

        <p>Por favor, asegúrate de contar con tiempo suficiente (aprox. 45-60 minutos) y una conexión estable a internet antes de comenzar.</p>

        <div style="text-align: center;">
            <a href="{{ route('evaluation.landing', ['token' => $token]) }}" class="button">
                Iniciar Evaluación
            </a>
        </div>

        <p style="margin-top: 30px; font-size: 14px; color: #666;">
            Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:
        </p>
        <p class="link-text">
            {{ route('evaluation.landing', ['token' => $token]) }}
        </p>
    </div>

    <div class="footer">
        <p>Este es un correo automático, favor de no responder.<br>
            © {{ date('Y') }} SEDYCO. Todos los derechos reservados.</p>
    </div>
</div>
</body>
</html>
