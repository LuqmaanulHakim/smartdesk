<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use App\Contracts\TicketAiService;
use App\Services\Ai\FakeTicketAiService;
use App\Services\Ai\GeminiTicketAiService;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        $this->app->bind(TicketAiService::class, function () {
            $driver = config('ai.driver', 'fake');

            return match ($driver) {
                'fake' => app(FakeTicketAiService::class),
                'gemini' => app(GeminiTicketAiService::class),

                default => throw new InvalidArgumentException(
                    "Unsupported AI driver: {$driver}"
                ),
            };
        });
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        RateLimiter::for('gemini-classification', function () {
            return Limit::perMinute(10)->by('gemini-classification');
        });
    }
}
