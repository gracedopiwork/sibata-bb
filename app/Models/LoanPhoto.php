<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPhoto extends Model
{
    protected $table = 'bb_loan_photos';

    protected $fillable = [
        'bb_loan_id',
        'kind',
        'mime',
        'path',
        'data',
    ];

    protected $hidden = [
        'data',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(EvidenceLoan::class, 'bb_loan_id');
    }
}
