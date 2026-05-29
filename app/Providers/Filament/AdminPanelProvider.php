<?php

namespace App\Providers\Filament;

use App\Filament\Resources\UserResource\Widgets\UsersStatsOverview;
use App\Filament\Widgets\CampaignEvaluationsWidget;
use App\Filament\Widgets\DocumentsLibraryWidget;
use App\Filament\Widgets\RecentVacationRequestsWidget;
use App\Filament\Widgets\VacancyStats;
use App\Filament\Widgets\VacationChartWidget;
use App\Filament\Widgets\VacationStatsWidget;
use App\Filament\Widgets\ViolenceProtocolWidget;
use App\Http\Middleware\CheckUserStatusAndEvaluation;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Althinect\FilamentSpatieRolesPermissions\FilamentSpatieRolesPermissionsPlugin;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;
use Swis\Filament\Backgrounds\ImageProviders\MyImages;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->sidebarCollapsibleOnDesktop()
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->login()
            ->passwordReset()
            ->brandName('Optra')
            ->darkModeBrandLogo(asset('img/optraDarkLogo.png'))
            ->brandLogoHeight('40px')
            ->brandLogo(asset('img/optraLogo.png'))
            ->brandLogoHeight('40px')
            ->colors([
                'primary' => Color::Teal,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])->plugin(FilamentSpatieRolesPermissionsPlugin::make())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\ExitSurveyPage::class,
            ])
            //->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                UsersStatsOverview::class,    // Segunda posición
                \App\Filament\Widgets\CustomAccountWidget::class,
                //Widgets\AccountWidget::class, // Primera posición
                //VacationStatsWidget::class, // Cuarta posición
                CampaignEvaluationsWidget::class, // Tercera posición
                VacancyStats::class,
                //RecentVacationRequestsWidget::class,
                //VacationChartWidget::class,

                DocumentsLibraryWidget::class,
                ViolenceProtocolWidget::class,
                //Widgets\FilamentInfoWidget::class, // Comentado
            ])
            ->profile()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                CheckUserStatusAndEvaluation::class,
            ])
            ->plugins([
                /*
                FilamentBackgroundsPlugin::make()
                    ->imageProvider(
                        MyImages::make()
                            ->directory('images/backgrounds')
                    ), */
            ])->databaseNotifications();


    }

}
