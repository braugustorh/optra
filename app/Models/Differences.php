<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Differences extends Model
{
    use Sushi;

    protected $schema = [
        'nombre' => 'string',
        'suma_file1' => 'string',
        'suma_file2' => 'string',
        'diferencia' => 'string',
    ];

    public function getRows(): array
    {
        return static::getDifferencesData()->toArray();
    }

    public static function setDifferencesData(Collection $data): void
    {
        Cache::put('differences_data', $data->toArray(), now()->addHours(24));
    }

    public static function getDifferencesData(): Collection
    {
        $data = Cache::get('differences_data', []);
        return collect($data);
    }

    public static function clearDifferencesData(): void
    {
        Cache::forget('differences_data');
        Cache::forget('sushi.'.static::class);
    }

    protected $fillable = [
        'nombre',
        'suma_file1',
        'suma_file2',
        'diferencia',
    ];

    protected $primaryKey = 'nombre';
    public $incrementing = false;
    protected $keyType = 'string';
}
