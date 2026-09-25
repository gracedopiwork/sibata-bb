<?php

namespace App\Models;

use App\Enums\ItemCategory;
use App\Enums\VerdictStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitItem extends Model
{
    protected $table = 'sip_evidence_items';

    protected $fillable = [
        'physical_unit_id',
        'item_name',
        'category',
        'quantity',
        'verdict_status',
        'execution_ba_number',
        'execution_recipient',
        'execution_recipient_nik',
        'execution_date',
        'execution_proof_photo',
    ];

    protected function casts(): array
    {
        return [
            'category' => ItemCategory::class,
            'verdict_status' => VerdictStatus::class,
            'execution_date' => 'date',
        ];
    }

    public function physicalUnit(): BelongsTo
    {
        return $this->belongsTo(PhysicalUnit::class, 'physical_unit_id');
    }
}
