<?php

use App\Jobs\ClassifyTicketJob;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('a customer can submit a valid ticket', function () {
    Queue::fake();

    $response = $this->post(route('tickets.store'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Payment issue',
        'message' => 'I need help with a payment that was charged twice.',
    ]);

    $ticket = Ticket::firstOrFail();

    $response->assertRedirect(route('tickets.success', $ticket->id));
    $response->assertSessionHas('reference', $ticket->reference);

    expect($ticket->reference)->toMatch('/^SD-\d{6}$/');

    $this->assertDatabaseHas('tickets', [
        'id' => $ticket->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Payment issue',
        'status' => 'new',
        'ai_status' => 'pending',
    ]);

    Queue::assertPushed(ClassifyTicketJob::class, 1);
});

test('ticket submission requires all fields', function () {
    $response = $this->post(route('tickets.store'), []);

    $response->assertSessionHasErrors([
        'name',
        'email',
        'subject',
        'message',
    ]);
});

test('ticket submission rejects an invalid email', function () {
    $response = $this->post(route('tickets.store'), [
        'name' => 'John Doe',
        'email' => 'invalid-email',
        'subject' => 'Payment issue',
        'message' => 'I need help with a payment issue.',
    ]);

    $response->assertSessionHasErrors('email');

    $this->assertDatabaseCount('tickets', 0);
});

test('ticket submission rejects messages shorter than 20 characters', function () {
    $response = $this->post(route('tickets.store'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Payment issue',
        'message' => 'Too short',
    ]);

    $response->assertSessionHasErrors('message');

    $this->assertDatabaseCount('tickets', 0);
});

test('ticket submission rejects messages longer than 5000 characters', function () {
    $response = $this->post(route('tickets.store'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Payment issue',
        'message' => str_repeat('A', 5001),
    ]);

    $response->assertSessionHasErrors('message');

    $this->assertDatabaseCount('tickets', 0);
});


test('public ticket submission is rate limited to 5 requests per minute', function () {
    Queue::fake();

    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'subject' => 'Payment issue',
        'message' => 'I need help with a payment that was charged twice.',
    ];

    $lastResponse = null;

    for ($i = 0; $i < 6; $i++) {
        $lastResponse = $this->post(
            route('tickets.store'),
            $data
        );

        if ($i < 5) {
            $lastResponse->assertRedirect();
        }
    }

    $lastResponse->assertStatus(429);

    $this->assertDatabaseCount('tickets', 5);
});
