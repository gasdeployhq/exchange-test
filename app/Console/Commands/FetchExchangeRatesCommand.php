<?php
declare(strict_types=1);


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Interfaces\ExchangeRateInterface;

class FetchExchangeRatesCommand extends Command
{
    protected $signature = 'fetch:exchange-rates {--days=180} {--baseCurrencies=USD,EUR,GBP}';
    protected $description = 'Fetch exchange rates for the past N days for multiple base currencies and save all combinations in cache';

    public function __construct(protected ExchangeRateInterface $exchangeService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $days = (int) $this->option('days');
        $baseCurrencies = explode(',', $this->option('baseCurrencies'));

        foreach ($baseCurrencies as $baseCurrency) {
            $this->exchangeService->fetchRates($days, $baseCurrency);
            $this->info("Exchange rates for the past $days days have been dispatched to the queue for base currency $baseCurrency.");
        }

        $this->info("All exchange rate combinations for the specified base currencies have been queued for caching.");
    }
}
