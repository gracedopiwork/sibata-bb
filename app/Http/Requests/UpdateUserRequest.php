<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'nip' => ['nullable', 'string', 'max:50'],
            'telegram_id' => ['nullable', 'integer', Rule::unique('users', 'telegram_id')->ignore($user)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
