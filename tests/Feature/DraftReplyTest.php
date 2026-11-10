
<?php

use App\Contracts\TicketAiService;
use App\Enums\AiStatus;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

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
        'ai_status' => AiStatus::DONE,
    ]);
});

test('an agent can generate an AI draft reply', function () {
    $aiService = Mockery::mock(TicketAiService::class);

    $aiService->shouldReceive('draftReply')
        ->once()
        ->with(Mockery::on(
            fn ($ticket) => $ticket->id === $this->ticket->id
        ))
        ->andReturn('Dear John, we will review your payment issue.');

    $this->app->instance(TicketAiService::class, $aiService);

    $response = $this->actingAs($this->agent)->post(
        route('tickets.generateReply', $this->ticket)
    );

    $response->assertSessionHas(
        'generated_reply',
        'Dear John, we will review your payment issue.'
    );
});


test('generated AI replies are cached for 10 minutes', function () {
    $aiService = Mockery::mock(TicketAiService::class);

    $aiService->shouldReceive('draftReply')
        ->once()
        ->andReturn('Dear John, we will review your payment issue.');

    $this->app->instance(TicketAiService::class, $aiService);

    $this->actingAs($this->agent);

    $firstResponse = $this->post(
        route('tickets.generateReply', $this->ticket)
    );

    $firstResponse->assertSessionHas(
        'generated_reply',
        'Dear John, we will review your payment issue.'
    );

    $secondResponse = $this->post(
        route('tickets.generateReply', $this->ticket)
    );

    $secondResponse->assertSessionHas(
        'generated_reply',
        'Dear John, we will review your payment issue.'
    );
});


test('an agent can save an edited reply and clear the cached draft', function () {
    $cacheKey = "ticket:{$this->ticket->id}:draft-reply";

    Cache::put(
        $cacheKey,
        'Original AI-generated draft.',
        now()->addMinutes(10)
    );

    $editedReply = 'Dear John, thank you for contacting us. We will investigate your payment issue.';

    $response = $this->actingAs($this->agent)->post(
        route('tickets.saveReply', $this->ticket),
        [
            'reply' => $editedReply,
        ]
    );

    $response->assertSessionHasNoErrors();

    $this->ticket->refresh();

    expect($this->ticket->reply)->toBe($editedReply);

    expect(Cache::has($cacheKey))->toBeFalse();

    $this->assertDatabaseHas('tickets', [
        'id' => $this->ticket->id,
        'reply' => $editedReply,
    ]);
});


test('changing ticket classification clears the cached draft reply', function () {
    $cacheKey = "ticket:{$this->ticket->id}:draft-reply";

    Cache::put(
        $cacheKey,
        'Old AI-generated draft.',
        now()->addMinutes(10)
    );

    $response = $this->actingAs($this->agent)->post(
        route('tickets.updateClassification', $this->ticket),
        [
            'category' => 'technical',
            'priority' => 'high',
        ]
    );

    $response->assertSessionHasNoErrors();

    expect(Cache::has($cacheKey))->toBeFalse();
});


test('unchanged ticket classification keeps the cached draft reply', function () {
    $this->ticket->update([
        'category' => 'billing',
        'priority' => 'high',
    ]);

    $cacheKey = "ticket:{$this->ticket->id}:draft-reply";

    Cache::put(
        $cacheKey,
        'Existing AI-generated draft.',
        now()->addMinutes(10)
    );

    $response = $this->actingAs($this->agent)->post(
        route('tickets.updateClassification', $this->ticket),
        [
            'category' => 'billing',
            'priority' => 'high',
        ]
    );

    $response->assertSessionHasNoErrors();

    expect(Cache::has($cacheKey))->toBeTrue();
});


test('an unauthenticated visitor cannot generate an AI draft reply', function () {
    $response = $this->post(
        route('tickets.generateReply', $this->ticket)
    );

    $response->assertRedirect(route('login'));

    expect(
        Cache::has("ticket:{$this->ticket->id}:draft-reply")
    )->toBeFalse();
});


test('an agent is rate limited after 10 draft generation requests per minute', function () {
    $aiService = Mockery::mock(TicketAiService::class);

    $aiService->shouldReceive('draftReply')
        ->once()
        ->andReturn('Dear John, we will review your issue.');

    $this->app->instance(TicketAiService::class, $aiService);

    $this->actingAs($this->agent);

    $lastResponse = null;

    for ($i = 0; $i < 11; $i++) {
        $lastResponse = $this->post(
            route('tickets.generateReply', $this->ticket)
        );

        if ($i < 10) {
            $lastResponse->assertStatus(302);
        }
    }

    $lastResponse->assertStatus(429);
});
