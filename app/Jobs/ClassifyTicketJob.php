<?php

namespace App\Jobs;

use App\Contracts\TicketAiService;
use App\Enums\AiStatus;
use App\Enums\ClassificationSource;
use App\Models\Ticket;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use Throwable;
use DateTimeInterface;

class ClassifyTicketJob implements ShouldQueue
{
    use Queueable;

    // Allow jobs to wait for an available rate-limit slot.
    public int $tries = 100;

    // Stop after the first actual exception, rather than retrying API failures.
    public int $maxExceptions = 1;

    public function __construct(
        public Ticket $ticket
    ) {}

    public function middleware(): array
    {
        if (config('ai.driver') !== 'gemini') {
            return [];
        }

        return [
            new RateLimited('gemini-classification'),
        ];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addHours(2);
    }

    public function handle(TicketAiService $aiService): void
    {
        try {
            $this->ticket->update([
                'ai_status' => AiStatus::PENDING,
            ]);

            $classification = $aiService->classify($this->ticket);

            $this->ticket->update([
                'category' => $classification->category,
                'priority' => $classification->priority,
                'sentiment' => $classification->sentiment,
                'summary' => $classification->summary,
                'language' => $classification->language,
                'category_source' => ClassificationSource::AI,
                'priority_source' => ClassificationSource::AI,
                'ai_status' => AiStatus::DONE,
            ]);

        } catch (Throwable $exception) {
            $this->ticket->update([
                'ai_status' => AiStatus::FAILED,
            ]);

            Log::error('Ticket AI classification failed.', [
                'ticket_id' => $this->ticket->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}