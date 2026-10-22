<?php

namespace App\Services;

use App\Models\PrivacyNotice;
use App\Models\PrivacyNoticeAcceptance;
use App\Models\Sede;
use App\Models\User;

class PrivacyNoticeService
{
    /**
     * Verifica de forma ultra-rápida (Caché + Sesión + Índice Único B-Tree)
     * si el usuario ya aceptó el aviso de privacidad vigente.
     */
    public function hasUserAcceptedActiveNotice(?User $user): bool
    {
        if (!$user) {
            return true;
        }

        $activeNotice = PrivacyNotice::getActive();

        if (!$activeNotice) {
            return true;
        }

        $sessionKey = $this->getSessionAcceptanceKey($user->id);

        if (session($sessionKey) === $activeNotice->id) {
            return true;
        }

        // Consulta optimizada usando el índice único compuesto uq_privacy_notice_user (privacy_notice_id, user_id)
        $hasAccepted = PrivacyNoticeAcceptance::query()
            ->where('privacy_notice_id', $activeNotice->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($hasAccepted) {
            session([$sessionKey => $activeNotice->id]);
        }

        return $hasAccepted;
    }

    /**
     * Resuelve los datos dinámicos del Responsable según el tipo de configuración (scope_type)
     * del Aviso de Privacidad y las relaciones del colaborador.
     */
    public function resolveDataForUser(PrivacyNotice $notice, ?User $user): array
    {
        $user?->loadMissing(['razonSocial', 'sede.razonSocials']);

        $providerName = trim((string) ($notice->provider_name ?: 'NetInnfode'));
        $fallbackCompany = trim((string) ($notice->general_company_name ?: ''));
        $fallbackAddress = trim((string) ($notice->general_address ?: ''));

        $companyName = '';
        $address = '';

        if ($notice->scope_type === PrivacyNotice::SCOPE_RAZON_SOCIAL) {
            $companyName = $user?->razonSocial?->name
                ?: $user?->sede?->company_name
                ?: $user?->sede?->razonSocials?->first()?->name
                ?: $fallbackCompany;

            $address = $user?->razonSocial?->fiscal_address
                ?: $this->formatSedeAddress($user?->sede)
                ?: $fallbackAddress;
        } elseif ($notice->scope_type === PrivacyNotice::SCOPE_SEDE) {
            $companyName = $user?->sede?->company_name
                ?: $user?->sede?->razonSocials?->first()?->name
                ?: $user?->razonSocial?->name
                ?: $user?->sede?->name
                ?: $fallbackCompany;

            $address = $this->formatSedeAddress($user?->sede)
                ?: $user?->sede?->razonSocials?->first()?->fiscal_address
                ?: $user?->razonSocial?->fiscal_address
                ?: $fallbackAddress;
        } else {
            // SCOPE_GENERAL: Usa los valores generales configurados en el aviso
            $companyName = $notice->general_company_name
                ?: $user?->razonSocial?->name
                ?: '';

            $address = $notice->general_address
                ?: $user?->razonSocial?->fiscal_address
                ?: '';
        }

        // Si aún no se ha resuelto una empresa (ej. usuarios administrativos sin razón social asignada)
        if (empty($companyName)) {
            $companyName = \App\Models\RazonSocial::where('status', 1)->value('name')
                ?: \App\Models\RazonSocial::value('name')
                ?: 'LA EMPRESA';
        }

        if (empty($address)) {
            $address = \App\Models\RazonSocial::where('status', 1)->whereNotNull('fiscal_address')->value('fiscal_address')
                ?: \App\Models\RazonSocial::whereNotNull('fiscal_address')->value('fiscal_address')
                ?: '';
        }

        return [
            'provider_name' => $providerName,
            'company_name' => trim($companyName),
            'address' => trim($address),
            'razon_social_name' => $user?->razonSocial?->name,
            'sede_name' => $user?->sede?->name,
        ];
    }

    /**
     * Reemplaza las variables dinámicas en el HTML del aviso con los datos resueltos del usuario.
     */
    public function renderContent(?string $content, array $resolvedData): string
    {
        if (empty($content)) {
            return '';
        }

        $provider = e($resolvedData['provider_name'] ?? 'NetInnfode');
        $company = e($resolvedData['company_name'] ?? '');
        $address = e($resolvedData['address'] ?? '');

        $replacements = [
            '[NOMBRE DE LA EMPRESA PROVEEDORA]' => '<strong>' . $provider . '</strong>',
            '{empresa_proveedora}' => '<strong>' . $provider . '</strong>',
            '[NOMBRE DE LA EMPRESA CONTRATANTE]' => '<strong>' . $company . '</strong>',
            '[NOMBRE DE LA EMPRESA / uno por cada empresa]' => '<strong>' . $company . '</strong>',
            '[NOMBRE DE LA EMPRESA / UNO POR CADA EMPRESA]' => '<strong>' . $company . '</strong>',
            '[NOMBRE DE LA EMPRESA]' => '<strong>' . $company . '</strong>',
            '{empresa_contratante}' => '<strong>' . $company . '</strong>',
            '{razon_social}' => '<strong>' . $company . '</strong>',
            '[DIRECCIÓN COMPLETA]' => '<strong>' . $address . '</strong>',
            '[DIRECCION COMPLETA]' => '<strong>' . $address . '</strong>',
            '{direccion_completa}' => '<strong>' . $address . '</strong>',
            '{direccion_fiscal}' => '<strong>' . $address . '</strong>',
        ];

        return str_ireplace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Registra la aceptación del aviso de privacidad por parte del usuario.
     */
    public function recordAcceptance(
        PrivacyNotice $notice,
        User $user,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): PrivacyNoticeAcceptance {
        $resolved = $this->resolveDataForUser($notice, $user);

        $acceptance = PrivacyNoticeAcceptance::updateOrCreate(
            [
                'privacy_notice_id' => $notice->id,
                'user_id' => $user->id,
            ],
            [
                'razon_social_id' => $user->razon_social_id,
                'sede_id' => $user->sede_id,
                'resolved_company_name' => $resolved['company_name'],
                'resolved_address' => $resolved['address'],
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'accepted_at' => now(),
            ]
        );

        session([$this->getSessionAcceptanceKey($user->id) => $notice->id]);

        return $acceptance;
    }

    /**
     * Formatea la dirección completa de una Sede si cuenta con campos capturados.
     */
    public function formatSedeAddress(?Sede $sede): ?string
    {
        if (!$sede) {
            return null;
        }

        $parts = array_filter([
            trim((string) $sede->address),
            $sede->cp ? 'C.P. ' . trim((string) $sede->cp) : null,
            trim((string) $sede->city),
            trim((string) $sede->state),
        ]);

        // Si solo tiene ciudad/estado pero no dirección de calle, preferimos que use el fallback fiscal si no hay calle
        if (empty(trim((string) $sede->address))) {
            return null;
        }

        return !empty($parts) ? implode(', ', $parts) : null;
    }

    public function getSessionAcceptanceKey(int $userId): string
    {
        return "optra_privacy_accepted_user_{$userId}";
    }

    /**
     * Plantilla por defecto del Paso 1: Aviso Importante sobre Protección de Datos Personales.
     */
    public static function getDefaultInitialContent(): string
    {
        return <<<'HTML'
<p><strong>Aviso Importante sobre Protección de Datos Personales:</strong> La presente plataforma es proporcionada por [NOMBRE DE LA EMPRESA PROVEEDORA] exclusivamente como herramienta tecnológica. Los datos personales ingresados y tratados a través de la misma son responsabilidad de [NOMBRE DE LA EMPRESA CONTRATANTE], quien actúa como Responsable del tratamiento conforme a la Ley Federal de Protección de Datos Personales en Posesión de los Particulares y pone a disposición de los titulares el Aviso de Privacidad correspondiente.</p>
HTML;
    }

    /**
     * Plantilla por defecto del Paso 2: Aviso de Privacidad Simplificado.
     */
    public static function getDefaultSimplifiedContent(): string
    {
        return <<<'HTML'
<h3>AVISO DE PRIVACIDAD SIMPLIFICADO</h3>
<h4>ACCESO A PLATAFORMAS Y SISTEMAS INSTITUCIONALES</h4>

<p><strong>1.</strong> [NOMBRE DE LA EMPRESA / uno por cada empresa], con domicilio en [DIRECCIÓN COMPLETA], es el responsable del tratamiento y protección de sus datos personales en el marco de la relación laboral y el uso de las herramientas tecnológicas de la organización.</p>

<p><strong>2.</strong> Los datos personales que recabamos a través de nuestras plataformas y sistemas serán utilizados para las siguientes finalidades primarias, necesarias para garantizar la seguridad de la información y la continuidad operativa:</p>

<h4>Gestión de Identidad y Acceso</h4>
<ul>
    <li>Verificar la identidad de los usuarios.</li>
    <li>Administrar credenciales de acceso.</li>
    <li>Gestionar perfiles, permisos y niveles de autorización dentro de los sistemas corporativos.</li>
    <li>Controlar el acceso a información, aplicaciones y recursos tecnológicos.</li>
</ul>

<h4>Seguridad de la Información y Monitoreo</h4>
<ul>
    <li>Registrar y auditar actividades realizadas dentro de las plataformas.</li>
    <li>Monitorear el uso de herramientas tecnológicas corporativas.</li>
    <li>Proteger los activos de información de la organización.</li>
    <li>Detectar, prevenir e investigar incidentes de seguridad, accesos no autorizados, fraudes o fugas de información.</li>
</ul>

<h4>Cumplimiento Normativo y Organizacional</h4>
<ul>
    <li>Verificar el cumplimiento de las políticas internas, el Código de Ética y Conducta, así como de las disposiciones legales y regulatorias aplicables.</li>
    <li>Atender requerimientos de auditoría interna o externa.</li>
    <li>Fortalecer los sistemas de control interno, prevención de corrupción y gestión de riesgos corporativos.</li>
</ul>

<h4>3.- Fundamento Jurídico</h4>
<p>El tratamiento de los datos personales asociados al acceso y uso de las plataformas digitales corporativas se encuentra sustentado en:</p>

<h4>Marco Constitucional</h4>
<ul>
    <li>Artículos 6° y 16 de la Constitución Política de los Estados Unidos Mexicanos, relativos al derecho a la privacidad y protección de datos personales.</li>
</ul>

<h4>Marco Legal</h4>
<ul>
    <li>Ley Federal de Protección de Datos Personales en Posesión de los Particulares.</li>
    <li>Ley Federal del Trabajo.</li>
    <li>Ley General de Responsabilidades Administrativas.</li>
    <li>Demás disposiciones legales aplicables en materia laboral, tecnológica, seguridad de la información y cumplimiento corporativo.</li>
</ul>
HTML;
    }

    /**
     * Plantilla por defecto del Paso 3: Aviso de Privacidad Extendido.
     */
    public static function getDefaultExtendedContent(): string
    {
        return <<<'HTML'
<h3>AVISO DE PRIVACIDAD EXTENDIDO (INTEGRAL)</h3>
<h4>ACCESO Y USO DE LA PLATAFORMA INSTITUCIONAL</h4>

<p>En [NOMBRE DE LA EMPRESA CONTRATANTE] nos comprometemos a proteger la privacidad y confidencialidad de nuestros colaboradores y usuarios. El presente Aviso de Privacidad Integral tiene como finalidad informarle de manera detallada sobre la forma en que recabamos, utilizamos, almacenamos y protegemos sus datos personales, en estricto cumplimiento con la <strong>Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP)</strong>, su Reglamento y demás normatividad aplicable en los Estados Unidos Mexicanos.</p>

<h4>1. Identidad y Domicilio del Responsable</h4>
<p>[NOMBRE DE LA EMPRESA CONTRATANTE], con domicilio fiscal en [DIRECCIÓN COMPLETA], haciendo uso de la plataforma tecnológica provista por [NOMBRE DE LA EMPRESA PROVEEDORA], es el Responsable del uso, tratamiento, almacenamiento y protección de sus datos personales.</p>

<h4>2. Datos Personales Recabados</h4>
<p>Para la correcta operación de la plataforma y el cumplimiento de las obligaciones derivadas de la relación laboral y organizacional, podemos tratar las siguientes categorías de datos personales:</p>
<ul>
    <li><strong>Datos de identificación:</strong> Nombre completo, número o código de empleado, CURP, RFC, Número de Seguridad Social (NSS), fecha y lugar de nacimiento, nacionalidad, estado civil y fotografía de perfil.</li>
    <li><strong>Datos de contacto:</strong> Correo electrónico institucional y/o personal, número telefónico, domicilio particular y contacto de emergencia.</li>
    <li><strong>Datos laborales y académicos:</strong> Puesto, departamento, centro de trabajo (sede), razón social, antigüedad, turno, escolaridad, historial de desempeño, indicadores (KPIs), planes de desarrollo, solicitudes de vacaciones y portafolio de expediente digital.</li>
    <li><strong>Datos de evaluación organizacional y psicométrica:</strong> Respuestas y resultados de evaluaciones de desempeño (360°, 9-Box, One-to-One), encuestas de clima organizacional, cuestionarios de identificación de factores de riesgo psicosocial (NOM-035-STPS-2018) y pruebas psicométricas laborales.</li>
    <li><strong>Datos de navegación y auditoría tecnológica:</strong> Registros de inicio de sesión, dirección IP, navegador o dispositivo utilizado, fecha y hora de acceso y bitácoras de aceptación electrónica.</li>
</ul>

<h4>3. Finalidades del Tratamiento</h4>
<p>Los datos personales recabados serán utilizados para las siguientes finalidades primarias y necesarias:</p>
<ul>
    <li>Gestión de identidad, control de acceso y administración de cuentas de usuario y permisos dentro de los sistemas institucionales.</li>
    <li>Administración de capital humano, expedientes digitales, control de vacaciones e historial laboral.</li>
    <li>Ejecución y seguimiento de evaluaciones de desempeño laboral, competencias, retroalimentación y planes de sucesión.</li>
    <li>Aplicación y análisis de cuestionarios vinculados a la Norma Oficial Mexicana <strong>NOM-035-STPS-2018</strong> y Clima Organizacional.</li>
    <li>Aplicación y evaluación de baterías psicométricas internas.</li>
    <li>Seguridad de la información, auditoría operativa, monitoreo del uso de herramientas tecnológicas y prevención de accesos no autorizados o fugas de información.</li>
    <li>Cumplimiento de disposiciones legales, fiscales, de seguridad social (IMSS/SUA) y requerimientos de autoridades competentes.</li>
</ul>

<h4>4. Transferencia de Datos Personales</h4>
<p>Sus datos personales no serán transferidos a terceros ajenos sin su consentimiento, salvo en las excepciones previstas en el artículo 37 de la LFPDPPP y en los siguientes supuestos:</p>
<ul>
    <li>Cuando lo requiera la Secretaría del Trabajo y Previsión Social (STPS), el Instituto Mexicano del Seguro Social (IMSS) u otras autoridades competentes en ejercicio de sus facultades de verificación, inspección o auditoría.</li>
    <li>Entre empresas afiliadas, subsidiarias o pertenecientes al mismo grupo corporativo que operen bajo los mismos procesos y políticas internas de protección de datos.</li>
    <li>Cuando sea indispensable para el cumplimiento de obligaciones legales o contractuales derivadas de la relación jurídica con el titular.</li>
</ul>

<h4>5. Medidas de Seguridad y Confidencialidad</h4>
<p>[NOMBRE DE LA EMPRESA CONTRATANTE], en conjunto con la infraestructura tecnológica de [NOMBRE DE LA EMPRESA PROVEEDORA], implementa medidas de seguridad técnicas, administrativas y físicas para proteger sus datos personales contra daño, pérdida, alteración, destrucción o el uso, acceso o tratamiento no autorizado. Los datos relacionados con factores de riesgo psicosocial y evaluaciones psicométricas cuentan con controles de acceso restringido por rol y cifrado de sesión.</p>

<h4>6. Ejercicio de Derechos ARCO (Acceso, Rectificación, Cancelación y Oposición)</h4>
<p>Usted tiene derecho a conocer qué datos personales tenemos de usted, para qué los utilizamos y las condiciones del uso que les damos (<strong>Acceso</strong>). Asimismo, es su derecho solicitar la corrección de su información personal en caso de que esté desactualizada, sea inexacta o incompleta (<strong>Rectificación</strong>); que la eliminemos de nuestros registros o bases de datos cuando considere que la misma no está siendo utilizada conforme a los principios, deberes y obligaciones previstas en la normativa (<strong>Cancelación</strong>); así como oponerse al uso de sus datos personales para fines específicos (<strong>Oposición</strong>).</p>
<p>Para el ejercicio de cualquiera de los Derechos ARCO, puede presentar su solicitud directamente ante el área de Recursos Humanos de [NOMBRE DE LA EMPRESA CONTRATANTE], indicando su nombre completo, número de colaborador, descripción clara de los datos respecto de los que busca ejercer alguno de los derechos y copia de su identificación oficial.</p>

<h4>7. Conservación de los Datos Personales</h4>
<p>Sus datos personales serán conservados únicamente durante el tiempo que dure la relación laboral o contractual y por los plazos legales de prescripción establecidos en la legislación laboral, fiscal y de seguridad social aplicable.</p>

<h4>8. Cambios al Aviso de Privacidad</h4>
<p>El presente Aviso de Privacidad puede sufrir modificaciones, cambios o actualizaciones derivadas de nuevos requerimientos legales o de nuestras propias políticas internas de privacidad. Cualquier actualización será notificada y puesta a su disposición directamente al ingresar a esta plataforma para su lectura y aceptación correspondiente.</p>
HTML;
    }

    /**
     * Leyenda por defecto para la casilla de verificación de aceptación.
     */
    public static function getDefaultCheckboxLabel(): string
    {
        return 'He leído y comprendido el presente Aviso de Privacidad Simplificado, así como los Términos de Uso de la Plataforma, y acepto el tratamiento de mis datos personales para las finalidades descritas.';
    }
}
