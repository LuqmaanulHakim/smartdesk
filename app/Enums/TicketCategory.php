<?php

namespace App\Enums;

enum TicketCategory: string
{
    case BILLING = 'billing';
    case TECHNICAL = 'technical';
    case ACCOUNT = 'account';
    case FEATURE_REQUEST = 'feature_request';
    case OTHER = 'other';
}