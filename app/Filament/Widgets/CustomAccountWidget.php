<?php

namespace App\Filament\Widgets;


use Filament\Widgets\Widget;
use App\Models\User;
use Carbon\Carbon;

class CustomAccountWidget extends Widget
{
    protected static ?int $sort = -3;

    protected static bool $isLazy = false;
    public $cumple;


    /**
     * @var view-string
     */
    protected static string $view = 'filament.resources.dashboard-resource.widgets.account-widget';

    public function mount()
    {
        $hoy = Carbon::now()->format('m-d');
        if (\DB::getDriverName()==='mysql'){
            $this->cumple = User::whereRaw("DATE_FORMAT(birthdate, '%m-%d') = '$hoy'")
                ->where('id',auth()->user()->id??null)
                ->get();
        }else{
            $this->cumple = User::whereRaw("TO_CHAR(birthdate, 'MM-DD') = '$hoy'")
                ->where('id',auth()->user()->id??null)
                ->get();
        }


    }


}

