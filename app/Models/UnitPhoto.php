<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitPhoto extends Model
{
    protected $fillable = [
        'physical_unit_id',
        'mime',
        'data',
    ];

    protected $hidden = [
        'data',
    ];

    public function physicalUnit(): BelongsTo
    {
        return $this->belongsTo(PhysicalUnit::class);
    }
}
