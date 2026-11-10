<?php

use App\Contracts\TicketAiService;
use App\Enums\AiStatus;
use App\Enums\ClassificationSource;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketSentiment;
use App\Enums\TicketStatus;
use App\Jobs\ClassifyTicketJob;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Ai\FakeTicketAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->agent = User::create([
        'name' => 'Test Agent',
        'email' => 'testagent@example.com',
        'password' => 'password123',
    ]);

    $this->ticket = Ticket::create([
        'reference' => 'SD-000001',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Payment issue',
        'message' => 'I was charged twice for my subscription.',
        'status' => TicketStatus::NEW,
        'ai_status' => AiStatus::PENDING,
    ]);
});

test('fake AI successfully classifies a ticket', function () {
    $job = new ClassifyTicketJob($this->ticket);

    $job->handle(new FakeTicketAiService());

    $this->ticket->refresh();

    expect($this->ticket->category)->toBe(TicketCategory::BILLING)
        ->and($this->ticket->priority)->toBe(TicketPriority::HIGH)
        ->and($this->ticket->sentiment)->toBe(TicketSentiment::NEGATIVE)
        ->and($this->ticket->summary)->toBe(
            'Customer reports being charged twice for their monthly plan.'
        )
        ->and($this->ticket->language)->toBe('en')
        ->and($this->ticket->ai_status)->toBe(AiStatus::DONE)
        ->and($this->ticket->category_source)->toBe(ClassificationSource::AI)
        ->and($this->ticket->priority_source)->toBe(ClassificationSource::AI);
});

test('failed AI classification marks the ticket as failed', function () {
    $aiService = Mockery::mock(TicketAiService::class);

    $aiService->shouldReceive('classify')
        ->once()
        ->andThrow(new RuntimeException('AI provider unavailable'));

    Log::spy();

    $job = new ClassifyTicketJob($this->ticket);

    expect(fn () => $job->handle($aiService))
        ->toThrow(RuntimeException::class, 'AI provider unavailable');

    $this->ticket->refresh();

    expect($this->ticket->ai_status)->toBe(AiStatus::FAILED);

    Log::shouldHaveReceived('error')->once();
});

test('an agent can retry failed AI classification', function () {
    Queue::fake();

    $this->ticket->update([
        'ai_status' => AiStatus::FAILED,
    ]);

    $response = $this->actingAs($this->agent)->post(
        route('tickets.retryClassification', $this->ticket)
    );

    $response->assertSessionHasNoErrors();

    $this->ticket->refresh();

    expect($this->ticket->ai_status)->toBe(AiStatus::PENDING);

    Queue::assertPushed(ClassifyTicketJob::class, 1);
});

test('an agent cannot retry classification if it has not failed', function () {
    Queue::fake();

    $this->ticket->update([
        'ai_status' => AiStatus::DONE,
    ]);

    $response = $this->actingAs($this->agent)->post(
        route('tickets.retryClassification', $this->ticket)
    );

    $response->assertSessionHasNoErrors();

    $this->ticket->refresh();

    expect($this->ticket->ai_status)->toBe(AiStatus::DONE);

    Queue::assertNothingPushed();
});
