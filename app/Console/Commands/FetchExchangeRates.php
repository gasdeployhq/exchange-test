<?php
declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\FetchExchangeRateJob;
use Illuminate\Console\Command;
use App\Interfaces\ExchangeRateInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class FetchExchangeRates extends Command
{
    protected $signature = 'exchange:fetch {date?} {currency=USD} {baseCurrency=RUR}';
    protected $description = 'Fetch exchange rate for a specific date, currency, and base currency, and display the difference from the previous trading day';

    public function __construct(protected ExchangeRateInterface $exchangeService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date'))->format('Y-m-d') : Carbon::today()->format('Y-m-d');
        $currency = $this->argument('currency');
        $baseCurrency = $this->argument('baseCurrency');

        $this->exchangeService->fetchRates(2);
        $this->info("Exchange rate for $currency in terms of $baseCurrency on $date fetched successfully.");

        $previousDay = Carbon::parse($date)->subDay()->format('Y-m-d');

        $currentRate = $this->getExchangeRateFromCache($currency, $baseCurrency, $date);
        $previousRate = $this->getExchangeRateFromCache($currency, $baseCurrency, $previousDay);

        if ($currentRate === null) {
            FetchExchangeRateJob::dispatchSync($date, $baseCurrency);
            $currentRate = $this->getExchangeRateFromCache($currency, $baseCurrency, $date);
        }

        if ($previousRate === null) {
            FetchExchangeRateJob::dispatchSync($previousDay, $baseCurrency);
            $previousRate = $this->getExchangeRateFromCache($currency, $baseCurrency, $previousDay);
        }

        if ($currentRate && $previousRate) {
            $difference = $currentRate - $previousRate;
            $this->info("Exchange rate for $currency in terms of $baseCurrency on $date: $currentRate");
            $this->info("Exchange rate for $currency in terms of $baseCurrency on $previousDay: $previousRate");
            $this->info("Difference between previous day: $difference");
            return 0;
        }

        $this->error("Cannot get $currency for $baseCurrency on $date.");
        return -1;
    }

    private function getExchangeRateFromCache(string $currency, string $baseCurrency, string $date): ?string
    {
        $currencyRate = Redis::get(sprintf(config('cache.exchange_rate_key'), $currency,$baseCurrency, $date));
        if (!$currencyRate) {
            return null;
        }

        return $currencyRate;
    }
}
