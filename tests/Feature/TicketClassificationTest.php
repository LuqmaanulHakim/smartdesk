<?php

use App\Enums\AiStatus;
use App\Enums\ClassificationSource;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        'category' => TicketCategory::BILLING,
        'priority' => TicketPriority::HIGH,
        'category_source' => ClassificationSource::AI,
        'priority_source' => ClassificationSource::AI,
        'ai_status' => AiStatus::DONE,
    ]);
});

test('an agent can change the ticket category', function () {
    $response = $this->actingAs($this->agent)->post(
        route('tickets.updateClassification', $this->ticket),
        [
            'category' => 'technical',
            'priority' => 'high',
        ]
    );

    $response->assertSessionHasNoErrors();

    $this->ticket->refresh();

    expect($this->ticket->category)->toBe(TicketCategory::TECHNICAL)
        ->and($this->ticket->category_source)->toBe(ClassificationSource::AGENT)
        ->and($this->ticket->priority_source)->toBe(ClassificationSource::AI);
});

test('an agent can change the ticket priority', function () {
    $response = $this->actingAs($this->agent)->post(
        route('tickets.updateClassification', $this->ticket),
        [
            'category' => 'billing',
            'priority' => 'urgent',
        ]
    );

    $response->assertSessionHasNoErrors();

    $this->ticket->refresh();

    expect($this->ticket->priority)->toBe(TicketPriority::URGENT)
        ->and($this->ticket->category_source)->toBe(ClassificationSource::AI)
        ->and($this->ticket->priority_source)->toBe(ClassificationSource::AGENT);
});

test('an agent changing both fields marks both sources as agent', function () {
    $response = $this->actingAs($this->agent)->post(
        route('tickets.updateClassification', $this->ticket),
        [
            'category' => 'technical',
            'priority' => 'urgent',
        ]
    );

    $response->assertSessionHasNoErrors();

    $this->ticket->refresh();

    expect($this->ticket->category_source)->toBe(ClassificationSource::AGENT)
        ->and($this->ticket->priority_source)->toBe(ClassificationSource::AGENT);
});

test('an unauthenticated visitor cannot update ticket classification', function () {
    $response = $this->post(
        route('tickets.updateClassification', $this->ticket),
        [
            'category' => 'technical',
            'priority' => 'urgent',
        ]
    );

    $response->assertRedirect(route('login'));

    $this->ticket->refresh();

    expect($this->ticket->category)->toBe(TicketCategory::BILLING)
        ->and($this->ticket->priority)->toBe(TicketPriority::HIGH)
        ->and($this->ticket->category_source)->toBe(ClassificationSource::AI)
        ->and($this->ticket->priority_source)->toBe(ClassificationSource::AI);
});

test('an unauthenticated visitor cannot access the agent dashboard', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('an unauthenticated visitor cannot view ticket details', function () {
    $response = $this->get(
        route('tickets.show', $this->ticket)
    );

    $response->assertRedirect(route('login'));
});
