<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Prosecutor extends Model
{
    protected $fillable = [
        'name',
        'nip',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(LegalCase::class, 'case_prosecutor', 'prosecutor_id', 'case_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('name');
    }
}
