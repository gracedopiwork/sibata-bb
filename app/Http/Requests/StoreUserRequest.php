<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageUsers() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'telegram_id' => $this->filled('telegram_id') ? $this->input('telegram_id') : null,
            'nip' => $this->filled('nip') ? $this->input('nip') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nip' => ['nullable', 'string', 'max:50'],
            'telegram_id' => ['nullable', 'integer', 'unique:users,telegram_id'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
