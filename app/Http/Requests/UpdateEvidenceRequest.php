<?php

namespace App\Http\Requests;

use App\Enums\EvidenceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEvidenceRequest extends FormRequest
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
        $item = $this->route('evidence');

        return [
            'no_reg_bb' => [
                'required',
                'string',
                'max:100',
                Rule::unique('evidence_items', 'no_reg_bb')->ignore($item),
            ],
            'no_reg_perkara' => ['required', 'string', 'max:100'],
            'nama_terdakwa' => ['required', 'string', 'max:255'],
            'nama_barang' => ['required', 'string'],
            'jumlah_satuan' => ['required', 'string', 'max:100'],
            'lokasi_rak' => ['required', 'string', 'max:150'],
            'status' => ['required', Rule::enum(EvidenceStatus::class)],
        ];
    }
}
