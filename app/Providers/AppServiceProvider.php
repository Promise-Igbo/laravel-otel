<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            TracerInterface::class,
            static fn (): TracerInterface => Globals::tracerProvider()->getTracer('learning/little-poll'),
        );
    }

    public function boot(): void {}
}
