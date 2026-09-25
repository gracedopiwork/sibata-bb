<?php

namespace App\Http\Requests;

use App\Enums\ItemCategory;
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
            'prosecutor_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'units' => ['required', 'array', 'min:1'],
            'units.*.type' => ['required', Rule::enum(UnitType::class)],
            'units.*.storage_location' => ['required', 'string', 'max:255'],
            'units.*.photo' => ['nullable', 'image', 'max:8192'],
            'units.*.item_name' => ['required_if:units.*.type,SINGLE', 'nullable', 'string', 'max:255'],
            'units.*.category' => ['required_if:units.*.type,SINGLE', 'nullable', Rule::enum(ItemCategory::class)],
            'units.*.quantity' => ['required_if:units.*.type,SINGLE', 'nullable', 'string', 'max:50'],
            'units.*.children' => ['required_if:units.*.type,PACK', 'array'],
            'units.*.children.*.item_name' => ['required_if:units.*.type,PACK', 'string', 'max:255'],
            'units.*.children.*.category' => ['required_if:units.*.type,PACK', Rule::enum(ItemCategory::class)],
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
            'prosecutor_name' => 'nama JPU',
            'units' => 'unit fisik',
        ];
    }
}
