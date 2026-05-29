<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Campaign;
use App\Observers\CampaignObserver;
use App\Models\VacationRequest;
use App\Observers\VacationRequestObserver;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Campaign::observe(CampaignObserver::class);
        VacationRequest::observe(VacationRequestObserver::class);

    }
}
