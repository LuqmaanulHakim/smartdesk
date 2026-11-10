<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketSentiment;
use App\Enums\TicketStatus;
use App\Enums\AiStatus;
use App\Enums\ClassificationSource;

class Ticket extends Model
{
    protected $fillable = [
        'reference',
        'name',
        'email',
        'subject',
        'message',
        'status',
        'category',
        'priority',
        'sentiment',
        'summary',
        'language',
        'category_source',
        'priority_source',
        'ai_status',
        'reply',
    ];

    protected function casts(): array
    {
        return [
            'category' => TicketCategory::class,
            'priority' => TicketPriority::class,
            'sentiment' => TicketSentiment::class,
            'status' => TicketStatus::class,
            'ai_status' => AiStatus::class,
            'category_source' => ClassificationSource::class,
            'priority_source' => ClassificationSource::class,
        ];
    }

    public function aiRequests(): HasMany
    {
        return $this->hasMany(AiRequest::class);
    }
}