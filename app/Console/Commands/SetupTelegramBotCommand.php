<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Throwable;

class SetupTelegramBotCommand extends Command
{
    protected $signature = 'telegram:setup {token? : Token dari @BotFather}';

    protected $description = 'Simpan token BotFather, daftarkan menu perintah, dan siapkan bot SIBATA-BB';

    public function handle(TelegramService $telegram): int
    {
        $token = trim((string) ($this->argument('token') ?: config('services.telegram.bot_token')));

        if ($token === '') {
            $this->warn('Token masih kosong. Buat bot dulu di Telegram:');
            $this->line('  1. Buka Telegram, cari @BotFather');
            $this->line('  2. Ketik /newbot');
            $this->line('  3. Nama: SIBATA-BB Kejari Wajo');
            $this->line('  4. Username: misalnya SibataWajoBot (harus berakhiran bot)');
            $this->line('  5. Salin token yang diberikan BotFather');
            $this->newLine();

            $token = trim((string) $this->ask('Tempel token BotFather di sini'));
        }

        if ($token === '' || ! preg_match('/^\d+:[A-Za-z0-9_-]+$/', $token)) {
            $this->error('Token tidak valid. Contoh: 123456789:AAHxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

            return self::FAILURE;
        }

        $telegram->persistToken($token);

        try {
            $me = $telegram->getMe();
            $result = is_array($me['result'] ?? null) ? $me['result'] : [];
            $username = (string) ($result['username'] ?? 'bot');
            $name = (string) ($result['first_name'] ?? 'SIBATA-BB');

            $telegram->deleteWebhook(true);
            $telegram->setMyCommands($telegram->defaultCommands());
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Bot siap: {$name} (@{$username})");
        $this->newLine();
        $this->line('Jalankan bot di komputer ini (tanpa webhook):');
        $this->line('  php artisan telegram:poll');
        $this->newLine();
        $this->line("Lalu buka Telegram, cari @{$username}, ketik /start.");
        $this->line('Pengguna Telegram pertama akan otomatis jadi Admin PB3R.');

        return self::SUCCESS;
    }
}
