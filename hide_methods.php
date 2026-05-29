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

function forceHideMethod($path, $keepList) {
    $files = glob($path . '/*.php');
    foreach ($files as $file) {
        $basename = basename($file);
        if (!in_array($basename, $keepList)) {
            $content = file_get_contents($file);
            
            // If it has the method, replace its body to return false;
            if (preg_match('/public\s+static\s+function\s+shouldRegisterNavigation\(\)\s*:\s*bool\s*\{[^}]+\}/i', $content)) {
                $content = preg_replace('/public\s+static\s+function\s+shouldRegisterNavigation\(\)\s*:\s*bool\s*\{[^}]+\}/i', "public static function shouldRegisterNavigation(): bool\n    {\n        return false;\n    }", $content);
                file_put_contents($file, $content);
                echo "Replaced shouldRegisterNavigation method in $basename\n";
            }
        }
    }
}

forceHideMethod(__DIR__ . '/app/Filament/Resources', $keepResources);
forceHideMethod(__DIR__ . '/app/Filament/Pages', $keepPages);
