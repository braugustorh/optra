<?php

namespace App\Services;

use App\Models\User;
use App\Models\VacationBalance;
use Carbon\Carbon;

class VacationService
{
    /**
     * Calcula los días de vacaciones según la LFT y antigüedad
     */
    public static function calculateVacationDays(int $yearsOfService): int
    {
        return match (true) {
            $yearsOfService === 1 => 12,
            $yearsOfService === 2 => 14,
            $yearsOfService === 3 => 16,
            $yearsOfService === 4 => 18,
            $yearsOfService === 5 => 20,
            $yearsOfService >= 6 && $yearsOfService <= 10 => 22,
            $yearsOfService >= 11 && $yearsOfService <= 15 => 24,
            $yearsOfService >= 16 && $yearsOfService <= 20 => 26,
            $yearsOfService >= 21 && $yearsOfService <= 25 => 28,
            $yearsOfService >= 26 && $yearsOfService <= 30 => 30,
            $yearsOfService >= 31 => 32,
            default => 0,
        };
    }

    /**
     * Obtiene los años de antigüedad de un usuario
     */
    public static function getYearsOfService(User $user): int
    {
        if (!$user->entry_date) {
            return 0;
        }

        return Carbon::parse($user->entry_date)->diffInYears(now());
    }

    /**
     * Crea o actualiza el balance de vacaciones para el período actual
     */
    public static function createOrUpdateBalance(User $user): VacationBalance
    {
        $entryDate = Carbon::parse($user->entry_date);
        $currentYear = now()->year;

        // Calculamos el período basado en la fecha de entrada
        $periodStart = $entryDate->copy()->setYear($currentYear);
        if ($periodStart->isFuture()) {
            $periodStart->subYear();
        }

        $periodEnd = $periodStart->copy()->addYear()->subDay();

        $yearsOfService = self::getYearsOfService($user);
        $totalDays = self::calculateVacationDays($yearsOfService);

        return VacationBalance::updateOrCreate(
            [
                'user_id' => $user->id,
                'year' => $periodStart->year,
            ],
            [
                'total_days' => $totalDays,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ]
        );
    }

    /**
     * Obtiene el balance actual del usuario
     */
    public static function getCurrentBalance(User $user): ?VacationBalance
    {
        $entryDate = Carbon::parse($user->entry_date);
        $currentYear = now()->year;

        $periodStart = $entryDate->copy()->setYear($currentYear);
        if ($periodStart->isFuture()) {
            $periodStart->subYear();
        }

        return VacationBalance::where('user_id', $user->id)
            ->where('year', $periodStart->year)
            ->first();
    }

    /**
     * Calcula días hábiles entre dos fechas (excluyendo fines de semana)
     */
    public static function calculateBusinessDays(Carbon $startDate, Carbon $endDate): int
    {
        $days = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            if (!$currentDate->isWeekend()) {
                $days++;
            }
            $currentDate->addDay();
        }

        return $days;
    }
}
