<?php

namespace App\Models;

use App\Enums\ItemCategory;
use App\Enums\VerdictStatus;
use App\Support\ReadableText;
use Illuminate\Database\Eloquent\Builder;
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
            'verdict_status' => VerdictStatus::class,
            'execution_date' => 'date',
        ];
    }

    public function displayName(): string
    {
        return ReadableText::make($this->item_name);
    }

    public function categoryLabel(): string
    {
        $code = $this->category instanceof ItemCategory
            ? $this->category->value
            : (string) $this->category;

        return EvidenceCategory::labelFor($code);
    }

    public function physicalUnit(): BelongsTo
    {
        return $this->belongsTo(PhysicalUnit::class, 'physical_unit_id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $builder) use ($like) {
            $builder->where('item_name', 'like', $like)
                ->orWhere('quantity', 'like', $like)
                ->orWhere('category', 'like', $like)
                ->orWhereHas('physicalUnit', function (Builder $unit) use ($like) {
                    $unit->where('unit_code', 'like', $like)
                        ->orWhere('storage_location', 'like', $like)
                        ->orWhereHas('legalCase', function (Builder $case) use ($like) {
                            $case->where('case_number', 'like', $like)
                                ->orWhere('defendant_name', 'like', $like);
                        });
                });
        });
    }
}
