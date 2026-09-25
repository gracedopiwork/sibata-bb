<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Throwable;

class PollTelegramBotCommand extends Command
{
    protected $signature = 'telegram:poll {--once : Ambil satu batch lalu berhenti}';

    protected $description = 'Jalankan bot SITABA-BB secara lokal dengan long polling';

    public function handle(TelegramService $telegram, TelegramBotService $bot): int
    {
        if ((string) config('services.telegram.bot_token') === '') {
            $this->error('TELEGRAM_BOT_TOKEN masih kosong. Jalankan: php artisan telegram:setup');

            return self::FAILURE;
        }

        try {
            $telegram->deleteWebhook(true);
            $me = $telegram->getMe();
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $username = (string) (($me['result']['username'] ?? null) ?: 'bot');
        $this->info("SITABA-BB mendengarkan @{$username}. Ketik /start di Telegram. Ctrl+C untuk berhenti.");

        $offset = 0;

        while (true) {
            try {
                $updates = $telegram->getUpdates($offset, 25);
            } catch (Throwable $exception) {
                $this->warn($exception->getMessage());
                sleep(2);

                if ($this->option('once')) {
                    return self::FAILURE;
                }

                continue;
            }

            foreach ($updates as $update) {
                $offset = ((int) ($update['update_id'] ?? 0)) + 1;

                try {
                    $bot->handle($update);
                    $this->line('update #'.($update['update_id'] ?? '?'));
                } catch (Throwable $exception) {
                    $this->error($exception->getMessage());
                }
            }

            if ($this->option('once')) {
                return self::SUCCESS;
            }
        }
    }
}
