<?php

namespace App\Models;

use App\Enums\EvidenceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EvidenceItem extends Model
{
    protected $fillable = [
        'qr_token',
        'no_reg_bb',
        'no_reg_perkara',
        'nama_terdakwa',
        'nama_barang',
        'jumlah_satuan',
        'lokasi_rak',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => EvidenceStatus::class,
        ];
    }

    public function logs(): HasMany
    {
        return $this->hasMany(EvidenceLog::class, 'evidence_id')->latest();
    }

    public function latestLog(): HasOne
    {
        return $this->hasOne(EvidenceLog::class, 'evidence_id')->latestOfMany();
    }

    public function latestLoan(): HasOne
    {
        return $this->hasOne(EvidenceLog::class, 'evidence_id')
            ->where('action_type', 'PINJAM')
            ->latestOfMany();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%'.trim($term).'%';

        return $query->where(function (Builder $builder) use ($like) {
            $builder->where('qr_token', 'like', $like)
                ->orWhere('no_reg_bb', 'like', $like)
                ->orWhere('no_reg_perkara', 'like', $like)
                ->orWhere('nama_terdakwa', 'like', $like)
                ->orWhere('nama_barang', 'like', $like)
                ->orWhere('lokasi_rak', 'like', $like);
        });
    }

    public static function nextQrToken(?int $year = null): string
    {
        $year ??= (int) now()->year;
        $prefix = sprintf('BB-WAJO-%d-', $year);

        $last = static::query()
            ->where('qr_token', 'like', $prefix.'%')
            ->orderByDesc('qr_token')
            ->value('qr_token');

        $sequence = 1;

        if (is_string($last) && preg_match('/(\d{4})$/', $last, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
