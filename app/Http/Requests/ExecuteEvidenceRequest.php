<?php

namespace App\Http\Requests;

use App\Enums\EvidenceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExecuteEvidenceRequest extends FormRequest
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
            'status' => ['required', Rule::in(array_map(
                fn (EvidenceStatus $status) => $status->value,
                EvidenceStatus::executionCases()
            ))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
