<?php

namespace App\Http\Requests;

use App\Enums\EvidenceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageEvidence() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'no_reg_bb' => ['required', 'string', 'max:100', 'unique:evidence_items,no_reg_bb'],
            'no_reg_perkara' => ['required', 'string', 'max:100'],
            'nama_terdakwa' => ['required', 'string', 'max:255'],
            'nama_barang' => ['required', 'string'],
            'jumlah_satuan' => ['required', 'string', 'max:100'],
            'lokasi_rak' => ['required', 'string', 'max:150'],
            'status' => ['nullable', Rule::enum(EvidenceStatus::class)],
        ];
    }
}
