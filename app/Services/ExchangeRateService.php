<?php
declare(strict_types=1);

namespace App\Services;

use App\Interfaces\ExchangeRateInterface;
use App\Jobs\FetchExchangeRateJob;
use Carbon\Carbon;

final class ExchangeRateService implements ExchangeRateInterface
{
    public function fetchRates(int $days, string $baseCurrency = 'RUR'): void
    {
        $today = Carbon::today();

        for ($i = 0; $i < $days; $i++) {
            $date = $today->copy()->subDays($i)->format('Y-m-d');
            FetchExchangeRateJob::dispatch($date, $baseCurrency);
        }
    }
}
