<?php

namespace App\DTOs;

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketSentiment;

class TicketClassification
{
    public function __construct(
        public TicketCategory $category,
        public TicketPriority $priority,
        public TicketSentiment $sentiment,
        public string $summary,
        public string $language,
    ) {}
}