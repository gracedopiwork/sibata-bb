<?php

namespace App\Models;

use App\Enums\MutationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mutation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'physical_unit_id',
        'mutation_type',
        'borrower_name',
        'court_date',
        'notes',
        'handled_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'mutation_type' => MutationType::class,
            'court_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Mutation $mutation): void {
            $mutation->created_at ??= now();
        });
    }

    public function physicalUnit(): BelongsTo
    {
        return $this->belongsTo(PhysicalUnit::class, 'physical_unit_id');
    }
}
