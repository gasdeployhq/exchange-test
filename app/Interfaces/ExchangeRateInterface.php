<?php

namespace App\Interfaces;

interface ExchangeRateInterface
{
    public function fetchRates(int $days): void;
//    public function getRateForDate(string $currency, string $baseCurrency, \DateTime $date): array;
}
