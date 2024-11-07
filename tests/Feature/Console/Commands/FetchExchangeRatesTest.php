<?php
declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Jobs\FetchExchangeRateJob;
use App\Interfaces\ExchangeRateInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FetchExchangeRatesTest extends TestCase
{
    public function testExchangeFetchCommandCalculatesExchangeRateDifference(): void
    {
        $date = Carbon::today()->format('Y-m-d');
        $previousDate = Carbon::yesterday()->format('Y-m-d');
        $currency = 'USD';
        $baseCurrency = 'RUR';
        $this->createMock(ExchangeRateInterface::class);

        Queue::fake();

        $currentRate = '75.5';
        $previousRate = '74.0';

        Redis::shouldReceive('get')
            ->with(sprintf(config('cache.exchange_rate_key'), $currency, $baseCurrency, $date))
            ->andReturn($currentRate);

        Redis::shouldReceive('get')
            ->with(sprintf(config('cache.exchange_rate_key'), $currency, $baseCurrency, $previousDate))
            ->andReturn($previousRate);

        $this->artisan('exchange:fetch', [
            'date' => $date,
            'currency' => $currency,
            'baseCurrency' => $baseCurrency
        ])
            ->expectsOutput("Exchange rate for $currency in terms of $baseCurrency on $date fetched successfully.")
            ->expectsOutput("Exchange rate for $currency in terms of $baseCurrency on $date: $currentRate")
            ->expectsOutput("Exchange rate for $currency in terms of $baseCurrency on $previousDate: $previousRate")
            ->expectsOutput("Difference between previous day: " . ($currentRate - $previousRate))
            ->assertExitCode(0);
    }

    public function testExchangeFetchCommandDispatchesJobsWhenCacheMiss(): void
    {
        $date = Carbon::today()->format('Y-m-d');
        $previousDate = Carbon::yesterday()->format('Y-m-d');
        $currency = 'USD';
        $baseCurrency = 'RUR';
        $this->createMock(ExchangeRateInterface::class);

        Queue::fake();

        Redis::shouldReceive('get')
            ->with(sprintf(config('cache.exchange_rate_key'), $currency, $baseCurrency, $date))
            ->andReturn(null);

        Redis::shouldReceive('get')
            ->with(sprintf(config('cache.exchange_rate_key'), $currency, $baseCurrency, $previousDate))
            ->andReturn(null);

        $this->artisan('exchange:fetch', [
            'date' => $date,
            'currency' => $currency,
            'baseCurrency' => $baseCurrency
        ])
            ->expectsOutput("Exchange rate for $currency in terms of $baseCurrency on $date fetched successfully.")
            ->expectsOutput("Cannot get $currency for $baseCurrency on $date.")
            ->assertExitCode(-1);

        Queue::assertPushed(FetchExchangeRateJob::class, function ($job) use ($date, $baseCurrency) {
            return $job->getDate() === $date && $job->getBaseCurrency() === $baseCurrency;
        });

        Queue::assertPushed(FetchExchangeRateJob::class, function ($job) use ($previousDate, $baseCurrency) {
            return $job->getDate() === $previousDate && $job->getBaseCurrency() === $baseCurrency;
        });
    }
}
