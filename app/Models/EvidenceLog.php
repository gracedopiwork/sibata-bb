<?php

namespace App\Models;

use App\Enums\EvidenceActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EvidenceLog extends Model
{
    protected $fillable = [
        'evidence_id',
        'user_id',
        'action_type',
        'borrower_name',
        'purpose',
        'expected_return_date',
        'photo_proof_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'action_type' => EvidenceActionType::class,
            'expected_return_date' => 'date',
        ];
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(EvidenceItem::class, 'evidence_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasPhoto(): bool
    {
        return is_string($this->photo_proof_path)
            && $this->photo_proof_path !== ''
            && Storage::disk('local')->exists($this->photo_proof_path);
    }
}
