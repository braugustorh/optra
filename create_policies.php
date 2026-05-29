<?php

$policies = [
    'User' => 'user',
    'Sede' => 'sede',
    'Department' => 'department',
    'Position' => 'position',
    'Portfolio' => 'portfolio',
    'PsychometricEvaluation' => 'psychometric-evaluation',
    'AnswerType' => 'answer-type',
    'Competence' => 'competence',
    'EvaluationsTypes' => 'evaluations-types',
    'Question' => 'question',
];

$dir = __DIR__ . '/app/Policies';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}

foreach ($policies as $model => $kebab) {
    $content = <<<PHP
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class {$model}Policy
{
    use HandlesAuthorization;

    public function viewAny(User \$user): bool
    {
        return \$user->can('view-any {$kebab}');
    }

    public function view(User \$user): bool
    {
        return \$user->can('view {$kebab}');
    }

    public function create(User \$user): bool
    {
        return \$user->can('create {$kebab}');
    }

    public function update(User \$user): bool
    {
        return \$user->can('update {$kebab}');
    }

    public function delete(User \$user): bool
    {
        return \$user->can('delete {$kebab}');
    }

    public function restore(User \$user): bool
    {
        return \$user->can('restore {$kebab}');
    }

    public function forceDelete(User \$user): bool
    {
        return \$user->can('force-delete {$kebab}');
    }
}
PHP;

    file_put_contents("{$dir}/{$model}Policy.php", $content);
    echo "Created {$model}Policy.php\n";
}
