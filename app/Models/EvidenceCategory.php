<?php

namespace App\Models;

use App\Enums\ItemCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EvidenceCategory extends Model
{
    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('name');
    }

    public static function labelFor(string $code): string
    {
        $name = static::query()->where('code', $code)->value('name');

        if (is_string($name) && $name !== '') {
            return $name;
        }

        return ItemCategory::tryFrom($code)?->label() ?? $code;
    }

    public static function activeCode(string $code): ?string
    {
        $found = static::query()->where('code', $code)->where('is_active', true)->value('code');

        if (is_string($found) && $found !== '') {
            return $found;
        }

        return ItemCategory::tryFrom($code)?->value;
    }
}
