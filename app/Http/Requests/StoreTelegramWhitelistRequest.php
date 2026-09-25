<?php

namespace App\Http\Requests;

use App\Enums\TelegramAccessRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTelegramWhitelistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageUsers() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'telegram_chat_id' => ['required', 'string', 'max:50', 'unique:telegram_whitelist,telegram_chat_id'],
            'user_name' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::enum(TelegramAccessRole::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
