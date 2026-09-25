<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class SetTelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook {url?}';

    protected $description = 'Set the Telegram bot webhook URL';

    public function handle(TelegramService $telegram): int
    {
        $url = $this->argument('url') ?: config('services.telegram.webhook_url');

        if (! is_string($url) || $url === '') {
            $this->error('Provide a URL or set TELEGRAM_WEBHOOK_URL.');

            return self::FAILURE;
        }

        $result = $telegram->setWebhook($url, config('services.telegram.secret_token'));
        $this->info(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
