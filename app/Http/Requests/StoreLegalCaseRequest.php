<?php

namespace App\Http\Requests;

use App\Enums\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLegalCaseRequest extends FormRequest
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
        return [
            'case_number' => ['required', 'string', 'max:100', 'unique:cases,case_number'],
            'defendant_name' => ['required', 'string', 'max:255'],
            'case_type_id' => ['required', 'exists:case_types,id'],
            'prosecutor_ids' => ['required', 'array', 'min:1'],
            'prosecutor_ids.*' => ['integer', 'exists:prosecutors,id'],
            'notes' => ['nullable', 'string'],
            'units' => ['required', 'array', 'min:1'],
            'units.*.type' => ['required', Rule::enum(UnitType::class)],
            'units.*.asset_type_id' => ['required', 'exists:asset_types,id'],
            'units.*.storage_location' => ['required', 'string', 'max:255'],
            'units.*.photo' => ['nullable', 'image', 'max:8192'],
            'units.*.item_name' => ['required_if:units.*.type,SINGLE', 'nullable', 'string', 'max:255'],
            'units.*.category' => ['required_if:units.*.type,SINGLE', 'nullable', 'exists:evidence_categories,code'],
            'units.*.quantity' => ['required_if:units.*.type,SINGLE', 'nullable', 'string', 'max:50'],
            'units.*.children' => ['required_if:units.*.type,PACK', 'array'],
            'units.*.children.*.item_name' => ['required_if:units.*.type,PACK', 'string', 'max:255'],
            'units.*.children.*.category' => ['required_if:units.*.type,PACK', 'exists:evidence_categories,code'],
            'units.*.children.*.quantity' => ['required_if:units.*.type,PACK', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'case_number' => 'nomor perkara',
            'defendant_name' => 'nama terdakwa',
            'prosecutor_ids' => 'JPU',
            'case_type_id' => 'jenis perkara',
            'units' => 'unit fisik',
            'units.*.asset_type_id' => 'jenis aset',
        ];
    }
}
