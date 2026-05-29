<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RenewVacationBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'vacations:renew-balances';
    protected $description = 'Renueva los balances de vacaciones de usuarios que cumplen año';

    public function handle(): int
    {
        $this->info('Renovando balances de vacaciones...');

        $today = now()->format('m-d');

        // Buscar usuarios que cumplen año hoy
        $users = User::whereNotNull('entry_date')
            ->get()
            ->filter(function ($user) use ($today) {
                return Carbon::parse($user->entry_date)->format('m-d') === $today;
            });

        $count = 0;
        foreach ($users as $user) {
            VacationService::createOrUpdateBalance($user);
            $count++;
            $this->info("Balance renovado para: {$user->name}");
        }

        $this->info("Total de balances renovados: {$count}");

        return Command::SUCCESS;
    }
}
