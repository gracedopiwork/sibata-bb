<?php

namespace App\Models;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PhysicalUnit extends Model
{
    protected $fillable = [
        'case_id',
        'unit_code',
        'unit_type',
        'storage_location',
        'photo_path',
        'current_status',
        'is_printed',
    ];

    protected function casts(): array
    {
        return [
            'unit_type' => UnitType::class,
            'current_status' => UnitStatus::class,
            'is_printed' => 'boolean',
        ];
    }

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'case_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UnitItem::class, 'physical_unit_id');
    }

    public function mutations(): HasMany
    {
        return $this->hasMany(Mutation::class, 'physical_unit_id')->latest('id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $builder) use ($like) {
            $builder->where('unit_code', 'like', $like)
                ->orWhere('storage_location', 'like', $like)
                ->orWhereHas('legalCase', function (Builder $case) use ($like) {
                    $case->where('case_number', 'like', $like)
                        ->orWhere('defendant_name', 'like', $like);
                })
                ->orWhereHas('items', function (Builder $item) use ($like) {
                    $item->where('item_name', 'like', $like);
                });
        });
    }

    public function itemsSummary(int $limit = 80): string
    {
        $names = $this->items->pluck('item_name')->filter()->values();

        if ($names->isEmpty()) {
            return '-';
        }

        $text = $names->implode('; ');

        return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit - 1).'…' : $text;
    }

    public function hasPhoto(): bool
    {
        return is_string($this->photo_path)
            && $this->photo_path !== ''
            && Storage::disk('public')->exists($this->photo_path);
    }

    public function publicViewUrl(): string
    {
        return url('/view/'.$this->unit_code);
    }

    public static function nextUnitCode(UnitType $type, ?int $year = null): string
    {
        $year ??= (int) now()->year;
        $prefix = sprintf('%s-%d-', $type->codePrefix(), $year);

        $last = static::query()
            ->where('unit_code', 'like', $prefix.'%')
            ->orderByDesc('unit_code')
            ->value('unit_code');

        $sequence = 1;

        if (is_string($last) && preg_match('/(\d{3,})$/', $last, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
