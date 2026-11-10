<?php

namespace App\Services\Ai;

use App\Contracts\TicketAiService;
use App\DTOs\TicketClassification;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketSentiment;
use App\Models\Ticket;

class FakeTicketAiService implements TicketAiService
{
    /**
     * Simulate AI ticket classification.
     */
    public function classify(Ticket $ticket): TicketClassification
    {
        return new TicketClassification(
            category: TicketCategory::BILLING,
            priority: TicketPriority::HIGH,
            sentiment: TicketSentiment::NEGATIVE,
            summary: 'Customer reports being charged twice for their monthly plan.',
            language: 'en',
        );
    }

    /**
     * Simulate AI reply generation.
     */
    public function draftReply(Ticket $ticket): string
    {
        return "Hello {$ticket->name},\n\nThank you for contacting SmartDesk. We apologise for the inconvenience. We understand your concern about the duplicate charge and will look into this matter for you.\n\nThank you for your patience.\n\nBest regards,\nSmartDesk Support Team";
    }
}