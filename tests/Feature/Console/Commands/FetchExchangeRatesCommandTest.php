<?php
declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Interfaces\ExchangeRateInterface;
use Mockery;
use Tests\TestCase;

class FetchExchangeRatesCommandTest extends TestCase
{
    public function testCommandDispatchesFetchRatesForMultipleBaseCurrencies(): void
    {
        $days = 180;
        $baseCurrencies = ['USD', 'EUR', 'GBP'];

        $exchangeServiceMock = Mockery::mock(ExchangeRateInterface::class);

        foreach ($baseCurrencies as $baseCurrency) {
            $exchangeServiceMock->shouldReceive('fetchRates')
                ->once()
                ->with($days, $baseCurrency);
        }

        $this->app->instance(ExchangeRateInterface::class, $exchangeServiceMock);

        $this->artisan('fetch:exchange-rates', [
            '--days' => $days,
            '--baseCurrencies' => implode(',', $baseCurrencies),
        ])
            ->expectsOutput("Exchange rates for the past $days days have been dispatched to the queue for base currency USD.")
            ->expectsOutput("Exchange rates for the past $days days have been dispatched to the queue for base currency EUR.")
            ->expectsOutput("Exchange rates for the past $days days have been dispatched to the queue for base currency GBP.")
            ->expectsOutput("All exchange rate combinations for the specified base currencies have been queued for caching.")
            ->assertExitCode(0);
    }
}
