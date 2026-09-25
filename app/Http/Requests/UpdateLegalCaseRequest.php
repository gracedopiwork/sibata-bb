<?php

namespace App\Http\Requests;

use App\Enums\CaseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLegalCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessDashboard() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $case = $this->route('case');

        return [
            'case_number' => ['required', 'string', 'max:100', Rule::unique('cases', 'case_number')->ignore($case)],
            'defendant_name' => ['required', 'string', 'max:255'],
            'case_type_id' => ['required', 'exists:case_types,id'],
            'prosecutor_ids' => ['required', 'array', 'min:1'],
            'prosecutor_ids.*' => ['integer', 'exists:prosecutors,id'],
            'case_status' => ['required', Rule::enum(CaseStatus::class)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
