<?php

namespace App\Models;

use App\Enums\CaseStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class LegalCase extends Model
{
    protected $table = 'cases';

    protected $fillable = [
        'case_number',
        'defendant_name',
        'prosecutor_name',
        'case_status',
        'notes',
        'case_type_id',
    ];

    protected function casts(): array
    {
        return [
            'case_status' => CaseStatus::class,
        ];
    }

    public function physicalUnits(): HasMany
    {
        return $this->hasMany(PhysicalUnit::class, 'case_id');
    }

    public function loans(): HasManyThrough
    {
        return $this->hasManyThrough(
            EvidenceLoan::class,
            PhysicalUnit::class,
            'case_id',
            'physical_unit_id'
        );
    }

    public function caseType(): BelongsTo
    {
        return $this->belongsTo(CaseType::class);
    }

    public function prosecutors(): BelongsToMany
    {
        return $this->belongsToMany(Prosecutor::class, 'case_prosecutor', 'case_id', 'prosecutor_id');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $builder) use ($like) {
            $builder->where('case_number', 'like', $like)
                ->orWhere('defendant_name', 'like', $like)
                ->orWhere('prosecutor_name', 'like', $like)
                ->orWhereHas('caseType', fn (Builder $type) => $type->where('name', 'like', $like));
        });
    }
}
