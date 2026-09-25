<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EvidenceLoan extends Model
{
    protected $table = 'bb_loans';

    protected $fillable = [
        'physical_unit_id',
        'borrower_name',
        'court_date',
        'notes',
        'loaned_by',
        'loaned_at',
        'loan_photo_path',
        'returned_at',
        'returned_by',
        'return_notes',
        'return_storage_location',
        'return_storage_location_id',
        'return_photo_path',
    ];

    protected function casts(): array
    {
        return [
            'court_date' => 'date',
            'loaned_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function physicalUnit(): BelongsTo
    {
        return $this->belongsTo(PhysicalUnit::class);
    }

    public function returnStorageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class, 'return_storage_location_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(LoanPhoto::class, 'bb_loan_id');
    }

    public function outboundPhoto(): HasOne
    {
        return $this->hasOne(LoanPhoto::class, 'bb_loan_id')
            ->where('kind', 'loan')
            ->select(['id', 'bb_loan_id', 'kind', 'mime', 'path', 'created_at', 'updated_at']);
    }

    public function inboundPhoto(): HasOne
    {
        return $this->hasOne(LoanPhoto::class, 'bb_loan_id')
            ->where('kind', 'return')
            ->select(['id', 'bb_loan_id', 'kind', 'mime', 'path', 'created_at', 'updated_at']);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('returned_at');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $builder) use ($like) {
            $builder->where('borrower_name', 'like', $like)
                ->orWhere('notes', 'like', $like)
                ->orWhereHas('physicalUnit', function (Builder $unit) use ($like) {
                    $unit->where('unit_code', 'like', $like)
                        ->orWhereHas('legalCase', function (Builder $case) use ($like) {
                            $case->where('case_number', 'like', $like)
                                ->orWhere('defendant_name', 'like', $like);
                        });
                });
        });
    }

    public function isActive(): bool
    {
        return $this->returned_at === null;
    }

    public function hasLoanPhoto(): bool
    {
        if ($this->relationLoaded('outboundPhoto') && $this->getRelation('outboundPhoto') !== null) {
            return true;
        }

        return filled($this->loan_photo_path) || $this->photos()->where('kind', 'loan')->exists();
    }

    public function hasReturnPhoto(): bool
    {
        if ($this->relationLoaded('inboundPhoto') && $this->getRelation('inboundPhoto') !== null) {
            return true;
        }

        return filled($this->return_photo_path) || $this->photos()->where('kind', 'return')->exists();
    }

    public function loanPhotoUrl(): string
    {
        return route('loans.photo', [$this, 'loan']);
    }

    public function returnPhotoUrl(): string
    {
        return route('loans.photo', [$this, 'return']);
    }
}
