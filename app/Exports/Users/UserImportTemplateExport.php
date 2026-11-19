<?php

declare(strict_types=1);

namespace App\Exports\Users;

use App\Exports\Users\Sheets\CollaboratorsTemplateSheet;
use App\Exports\Users\Sheets\InstructionsSheet;
use App\Exports\Users\Sheets\OrganizationalCatalogSheet;
use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Plantilla multi-hoja para la importación masiva de colaboradores:
 * 1. Colaboradores (captura con validaciones y formato texto)
 * 2. Catálogo Organizacional (sedes, razones sociales, departamentos y puestos activos)
 * 3. Guía de Llenado (reglas, equivalencias y ejemplo)
 */
class UserImportTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private readonly ?User $actor = null,
    ) {
    }

    public function sheets(): array
    {
        return [
            new CollaboratorsTemplateSheet($this->actor),
            new OrganizationalCatalogSheet($this->actor),
            new InstructionsSheet(),
        ];
    }
}
