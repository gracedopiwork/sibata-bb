<?php

namespace App\Models;

use App\Enums\TelegramAccessRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TelegramWhitelist extends Model
{
    protected $table = 'telegram_whitelist';

    protected $fillable = [
        'telegram_chat_id',
        'user_name',
        'role',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'role' => TelegramAccessRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function linkedUser(): HasOne
    {
        return $this->hasOne(User::class, 'telegram_id', 'telegram_chat_id');
    }

    public static function findActive(int|string $telegramId): ?self
    {
        return static::query()
            ->where('telegram_chat_id', (string) $telegramId)
            ->where('is_active', true)
            ->first();
    }
}
