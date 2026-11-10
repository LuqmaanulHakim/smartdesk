<?php

namespace App\Contracts;

use App\DTOs\TicketClassification;
use App\Models\Ticket;

interface TicketAiService
{
    /**
     * Classify a support ticket.
     */
    public function classify(Ticket $ticket): TicketClassification;

    /**
     * Generate a suggested reply for a support ticket.
     */
    public function draftReply(Ticket $ticket): string;
}