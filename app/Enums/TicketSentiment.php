<?php

namespace App\Enums;

enum TicketSentiment: string
{
    case POSITIVE = 'positive';
    case NEUTRAL = 'neutral';
    case NEGATIVE = 'negative';
}