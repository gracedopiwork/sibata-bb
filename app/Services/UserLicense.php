<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class UserLicense
{
    public static function generate(): string
    {
        $chunk = static fn (): string => strtoupper(Str::random(4));

        return 'SITABA-'.$chunk().'-'.$chunk().'-'.$chunk();
    }

    public static function uniqueKey(): string
    {
        do {
            $key = self::generate();
        } while (User::query()->where('license_key', $key)->exists());

        return $key;
    }

    public static function normalize(?string $key): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', (string) $key));
    }
}
