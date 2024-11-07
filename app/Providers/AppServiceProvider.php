<?php

namespace App\Providers;

use App\Interfaces\ExchangeRateInterface;
use App\Services\ExchangeRateService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExchangeRateInterface::class, ExchangeRateService::class);
    }

    public function boot(): void
    {

    }
}
