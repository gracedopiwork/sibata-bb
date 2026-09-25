<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramConversation extends Model
{
    protected $fillable = [
        'telegram_user_id',
        'action',
        'step',
        'evidence_id',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'telegram_user_id' => 'integer',
        ];
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(EvidenceItem::class, 'evidence_id');
    }
}
