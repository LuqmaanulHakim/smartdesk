<?php

namespace App\Enums;

enum AiStatus: string
{
    case PENDING = 'pending';
    case DONE = 'done';
    case FAILED = 'failed';
}