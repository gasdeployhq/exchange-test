<?php
declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class FetchExchangeRateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected string $date, protected string $baseCurrency)
    {

    }

    public function handle()
    {
        $url = "https://www.cbr.ru/scripts/XML_daily.asp?date_req=" . date('d/m/Y', strtotime($this->date));
        $response = Http::get($url)->body();
        $this->parseAndCacheRates($response, $this->date, $this->baseCurrency);
    }

    private function parseAndCacheRates(string $xmlData, string $date, string $baseCurrency): void
    {
        $xml = simplexml_load_string($xmlData);

        foreach ($xml->Valute as $currency) {
            $currencyCode = (string) $currency->CharCode;
            $rateValue = (float) str_replace(',', '.', (string) $currency->Value);
            $nominal = (int) $currency->Nominal;
            $rate = $rateValue / $nominal;

            Redis::set(sprintf(config('cache.exchange_rate_key'),$currencyCode, $baseCurrency, $date), $rate);
            Redis::expire(sprintf(config('cache.exchange_rate_key'),$currencyCode, $baseCurrency, $date), 86400);
        }
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getBaseCurrency(): string
    {
        return $this->baseCurrency;
    }
}
