<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRequest extends Model
{
    protected $fillable = [
        'ticket_id',
        'type',
        'model',
        'duration_ms',
        'input_tokens',
        'output_tokens',
        'success',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'duration_ms' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
