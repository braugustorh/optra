<?php

$keepResources = [
    'UserResource.php',
    'SedeResource.php',
    'DepartmentResource.php',
    'PositionResource.php',
    'CustomPermissionResource.php',
    'CustomRoleResource.php',
    'PortfolioResource.php',
    'PsychometricEvaluationResource.php',
    'AnswerTypeResource.php',
    'CompetenceResource.php',
    'EvaluationsTypesResource.php',
    'QuestionResource.php'
];

$keepPages = [
    'MyPsychometricEvaluations.php',
    'Nom035.php',
    'PsychometricDashboard.php',
    'RiskFactorTest.php',
    'RiskFactorTestOrgEnviroment.php',
    'TestGuiaI.php',
    'TakeInternalEvaluation.php'
];

function injectHiddenRule($path, $keepList) {
    $files = glob($path . '/*.php');
    foreach ($files as $file) {
        $basename = basename($file);
        if (!in_array($basename, $keepList)) {
            $content = file_get_contents($file);
            if (strpos($content, '$shouldRegisterNavigation') === false) {
                // Find class declaration and inject after {
                $content = preg_replace('/(class\s+[a-zA-Z0-9_]+\s*(?:extends\s+[a-zA-Z0-9_\\\\]+)?\s*(?:implements\s+[a-zA-Z0-9_\\\\,\s]+)?\s*\{)/i', "$1\n    protected static bool \$shouldRegisterNavigation = false;\n", $content, 1);
                file_put_contents($file, $content);
                echo "Hiding $basename\n";
            }
        }
    }
}

injectHiddenRule(__DIR__ . '/app/Filament/Resources', $keepResources);
injectHiddenRule(__DIR__ . '/app/Filament/Pages', $keepPages);

// Now handle the kept pages to use Spatie instead of hardcoded roles
$pagePermissions = [
    'MyPsychometricEvaluations.php' => 'view-page my-psychometric-evaluations',
    'Nom035.php' => 'view-page nom035',
    'PsychometricDashboard.php' => 'view-page psychometric-dashboard',
    'RiskFactorTest.php' => 'view-page risk-factor-test',
    'RiskFactorTestOrgEnviroment.php' => 'view-page risk-factor-test-org-enviroment',
    'TestGuiaI.php' => 'view-page test-guia-i',
    'TakeInternalEvaluation.php' => 'view-page take-internal-evaluation'
];

foreach ($pagePermissions as $page => $permission) {
    $file = __DIR__ . '/app/Filament/Pages/' . $page;
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Remove or replace canView if it exists
        if (strpos($content, 'public static function canView()') !== false) {
            $content = preg_replace('/public static function canView\(\)\s*:\s*bool\s*\{[^}]+\}/', "public static function canView(): bool\n    {\n        return auth()->check() && auth()->user()->can('$permission');\n    }", $content);
        } else {
            // Inject canAccess method
            if (strpos($content, 'public static function canAccess()') === false) {
                 $content = preg_replace('/(class\s+[a-zA-Z0-9_]+\s*(?:extends\s+[a-zA-Z0-9_\\\\]+)?\s*(?:implements\s+[a-zA-Z0-9_\\\\,\s]+)?\s*\{)/i', "$1\n    public static function canAccess(): bool\n    {\n        return auth()->check() && auth()->user()->can('$permission');\n    }\n", $content, 1);
            }
        }
        
        file_put_contents($file, $content);
        echo "Updated permissions for $page\n";
    }
}
