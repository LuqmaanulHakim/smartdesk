<?php

use App\Enums\AiStatus;
use App\Enums\ClassificationSource;
use App\Jobs\ClassifyTicketJob;
use App\Models\Ticket;
use App\Services\Ai\GeminiTicketAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

test('a valid Gemini response is saved correctly on the ticket', function () {
    config([
        'ai.api_key' => 'test-api-key',
        'ai.model' => 'gemini-test-model',
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'category' => 'billing',
                                    'priority' => 'high',
                                    'sentiment' => 'negative',
                                    'summary' => 'Customer reports being charged twice.',
                                    'language' => 'en',
                                ]),
                            ],
                        ],
                    ],
                ],
            ],
            'usageMetadata' => [
                'promptTokenCount' => 100,
                'candidatesTokenCount' => 50,
            ],
        ], 200),
    ]);

    $ticket = Ticket::create([
        'reference' => 'SD-000001',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Payment issue',
        'message' => 'I was charged twice for my subscription.',
        'status' => 'new',
        'ai_status' => AiStatus::PENDING,
    ]);

    $job = new ClassifyTicketJob($ticket);

    $job->handle(new GeminiTicketAiService());

    $ticket->refresh();

    expect($ticket->category->value)->toBe('billing')
        ->and($ticket->priority->value)->toBe('high')
        ->and($ticket->sentiment->value)->toBe('negative')
        ->and($ticket->summary)->toBe('Customer reports being charged twice.')
        ->and($ticket->language)->toBe('en')
        ->and($ticket->ai_status)->toBe(AiStatus::DONE)
        ->and($ticket->category_source)->toBe(ClassificationSource::AI)
        ->and($ticket->priority_source)->toBe(ClassificationSource::AI);

    Http::assertSent(function ($request) {
        return str_contains(
            $request->url(),
            'gemini-test-model:generateContent'
        ) && $request->hasHeader('x-goog-api-key', 'test-api-key');
    });

    $this->assertDatabaseHas('ai_requests', [
        'ticket_id' => $ticket->id,
        'type' => 'classify',
        'model' => 'gemini-test-model',
        'success' => true,
        'input_tokens' => 100,
        'output_tokens' => 50,
    ]);
});


test('invalid Gemini JSON is retried once before the ticket is marked as failed', function () {
    config([
        'ai.api_key' => 'test-api-key',
        'ai.model' => 'gemini-test-model',
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::sequence()
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'This is not valid JSON'],
                            ],
                        ],
                    ],
                ],
            ], 200)
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Still not valid JSON'],
                            ],
                        ],
                    ],
                ],
            ], 200),
    ]);

    $ticket = Ticket::create([
        'reference' => 'SD-000002',
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'Account issue',
        'message' => 'I cannot access my account after logging in.',
        'status' => 'new',
        'ai_status' => AiStatus::PENDING,
    ]);

    $job = new ClassifyTicketJob($ticket);

    Log::spy();

    expect(fn () => $job->handle(new GeminiTicketAiService()))
        ->toThrow(RuntimeException::class);

    $ticket->refresh();

    expect($ticket->ai_status)->toBe(AiStatus::FAILED);

    Http::assertSentCount(2);

    $this->assertDatabaseCount('ai_requests', 2);

    $this->assertDatabaseHas('ai_requests', [
        'ticket_id' => $ticket->id,
        'type' => 'classify',
        'success' => true,
    ]);

    Log::shouldHaveReceived('error')->twice();
});


test('an invalid priority is rejected and Gemini retries with a valid classification', function () {
    config([
        'ai.api_key' => 'test-api-key',
        'ai.model' => 'gemini-test-model',
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::sequence()
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode([
                                    'category' => 'billing',
                                    'priority' => 'critical',
                                    'sentiment' => 'negative',
                                    'summary' => 'Customer reports being charged twice.',
                                    'language' => 'en',
                                ])],
                            ],
                        ],
                    ],
                ],
            ], 200)
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode([
                                    'category' => 'billing',
                                    'priority' => 'high',
                                    'sentiment' => 'negative',
                                    'summary' => 'Customer reports being charged twice.',
                                    'language' => 'en',
                                ])],
                            ],
                        ],
                    ],
                ],
            ], 200),
    ]);

    $ticket = Ticket::create([
        'reference' => 'SD-000003',
        'name' => 'Alex Tan',
        'email' => 'alex@example.com',
        'subject' => 'Duplicate payment',
        'message' => 'I was charged twice for my subscription.',
        'status' => 'new',
        'ai_status' => AiStatus::PENDING,
    ]);

    $job = new ClassifyTicketJob($ticket);

    $job->handle(new GeminiTicketAiService());

    $ticket->refresh();

    expect($ticket->priority->value)->toBe('high')
        ->and($ticket->category->value)->toBe('billing')
        ->and($ticket->ai_status)->toBe(AiStatus::DONE);

    Http::assertSentCount(2);

    $this->assertDatabaseCount('ai_requests', 2);
});


test('Gemini retries an HTTP 500 error and successfully classifies the ticket', function () {
    config([
        'ai.api_key' => 'test-api-key',
        'ai.model' => 'gemini-test-model',
    ]);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'Internal server error']], 500)
            ->push([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode([
                                    'category' => 'billing',
                                    'priority' => 'high',
                                    'sentiment' => 'negative',
                                    'summary' => 'Customer reports being charged twice.',
                                    'language' => 'en',
                                ])],
                            ],
                        ],
                    ],
                ],
            ], 200),
    ]);

    $ticket = Ticket::create([
        'reference' => 'SD-000004',
        'name' => 'Sarah Lee',
        'email' => 'sarah@example.com',
        'subject' => 'Payment issue',
        'message' => 'I was charged twice for my subscription.',
        'status' => 'new',
        'ai_status' => AiStatus::PENDING,
    ]);

    $job = new ClassifyTicketJob($ticket);

    $job->handle(new GeminiTicketAiService());

    $ticket->refresh();

    expect($ticket->category->value)->toBe('billing')
        ->and($ticket->priority->value)->toBe('high')
        ->and($ticket->ai_status)->toBe(AiStatus::DONE);

    Http::assertSentCount(2);

    $this->assertDatabaseCount('ai_requests', 1);

    $this->assertDatabaseHas('ai_requests', [
        'ticket_id' => $ticket->id,
        'type' => 'classify',
        'success' => true,
    ]);
});
