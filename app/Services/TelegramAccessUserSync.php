<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\TelegramWhitelist;
use App\Models\User;
use Illuminate\Support\Str;

class TelegramAccessUserSync
{
    public function syncAll(): int
    {
        $created = 0;

        foreach (TelegramWhitelist::query()->orderBy('id')->get() as $entry) {
            if ($this->ensureUser($entry)) {
                $created++;
            }
        }

        return $created;
    }

    public function ensureUser(TelegramWhitelist $entry): bool
    {
        $chatId = trim((string) $entry->telegram_chat_id);

        if ($chatId === '' || ! ctype_digit($chatId)) {
            return false;
        }

        if (User::query()->where('telegram_id', $chatId)->exists()) {
            return false;
        }

        $name = trim((string) $entry->user_name);
        if ($name === '') {
            $name = 'Petugas PB3R';
        }

        User::query()->create([
            'name' => $name,
            'email' => $this->uniqueEmail($chatId),
            'password' => Str::password(20),
            'role' => UserRole::PetugasPb3r,
            'is_active' => (bool) $entry->is_active,
            'telegram_id' => (int) $chatId,
        ]);

        return true;
    }

    private function uniqueEmail(string $chatId): string
    {
        $base = 'telegram.'.$chatId.'@sibatabbwajo.my.id';

        if (! User::query()->where('email', $base)->exists()) {
            return $base;
        }

        return 'telegram.'.$chatId.'.'.Str::lower(Str::random(4)).'@sibatabbwajo.my.id';
    }
}
