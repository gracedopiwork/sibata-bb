<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TelegramService
{
    public function sendMessage(int|string $chatId, string $text, ?array $replyMarkup = null, array $options = []): void
    {
        $payload = array_merge($options, [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = json_encode($replyMarkup, JSON_THROW_ON_ERROR);
        }

        $this->call('sendMessage', $payload);
    }

    /**
     * @param  array<int, array<int, array<string, string>>>  $inlineKeyboard
     * @param  array<string, mixed>  $options
     */
    public function sendMessageWithInlineKeyboard(int|string $chatId, string $text, array $inlineKeyboard, array $options = []): void
    {
        $this->sendMessage($chatId, $text, [
            'inline_keyboard' => $inlineKeyboard,
        ], $options);
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): void
    {
        $payload = [
            'callback_query_id' => $callbackQueryId,
        ];

        if ($text !== null) {
            $payload['text'] = $text;
        }

        $this->call('answerCallbackQuery', $payload);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function sendPhoto(int|string $chatId, string $binary, string $caption, ?array $replyMarkup = null, array $options = []): void
    {
        $fields = array_merge($options, [
            'chat_id' => $chatId,
            'caption' => mb_substr($caption, 0, 1024),
            'parse_mode' => 'HTML',
        ]);

        if ($replyMarkup !== null) {
            $fields['reply_markup'] = json_encode($replyMarkup, JSON_THROW_ON_ERROR);
        }

        $response = Http::timeout(60)
            ->attach('photo', $binary, 'qr-bb.png')
            ->post($this->endpoint('sendPhoto'), $fields);

        if (! $response->successful() || ($response->json('ok') ?? false) !== true) {
            Log::error('Telegram sendPhoto failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Telegram sendPhoto failed.');
        }
    }

    public function downloadPhoto(string $fileId, string $directory, string $disk = 'public'): string
    {
        $file = $this->call('getFile', ['file_id' => $fileId]);
        $filePath = $file['result']['file_path'] ?? null;

        if (! is_string($filePath) || $filePath === '') {
            throw new RuntimeException('Telegram getFile did not return a file_path.');
        }

        $url = sprintf('https://api.telegram.org/file/bot%s/%s', $this->token(), $filePath);
        $response = Http::timeout(60)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Failed to download Telegram file from Bot API.');
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'jpg';
        $relativePath = trim($directory, '/').'/'.uniqid('tg_', true).'.'.$extension;

        Storage::disk($disk)->put($relativePath, $response->body());

        return $relativePath;
    }

    public function setWebhook(string $url, ?string $secretToken = null): array
    {
        $payload = [
            'url' => $url,
            'allowed_updates' => json_encode(['message', 'callback_query', 'my_chat_member'], JSON_THROW_ON_ERROR),
        ];

        if (is_string($secretToken) && $secretToken !== '') {
            $payload['secret_token'] = $secretToken;
        }

        return $this->call('setWebhook', $payload);
    }

    public function webhookInfo(): array
    {
        return $this->call('getWebhookInfo');
    }

    public function deleteWebhook(bool $dropPendingUpdates = true): array
    {
        return $this->call('deleteWebhook', [
            'drop_pending_updates' => $dropPendingUpdates ? 'true' : 'false',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getMe(): array
    {
        return $this->call('getMe');
    }

    /**
     * @param  array<int, array{command: string, description: string}>  $commands
     * @return array<string, mixed>
     */
    public function setMyCommands(array $commands): array
    {
        return $this->call('setMyCommands', [
            'commands' => json_encode($commands, JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @return list<array{command: string, description: string}>
     */
    public function defaultCommands(): array
    {
        return [
            ['command' => 'start', 'description' => 'Menu utama SITABA-BB'],
            ['command' => 'tambah', 'description' => 'Daftar perkara & barang bukti'],
            ['command' => 'cari', 'description' => 'Cari unit / perkara'],
            ['command' => 'pinjam', 'description' => 'Pinjam sidang'],
            ['command' => 'kembali', 'description' => 'Kembalikan ke gudang'],
            ['command' => 'eksekusi', 'description' => 'Catat putusan per item'],
            ['command' => 'rekap', 'description' => 'Ringkasan gudang'],
            ['command' => 'batal', 'description' => 'Batalkan proses'],
            ['command' => 'lisensi', 'description' => 'Aktifkan akses bot dengan kode lisensi'],
            ['command' => 'id', 'description' => 'Tampilkan ID Telegram / grup'],
        ];
    }

    public function persistToken(string $token): void
    {
        $path = base_path('.env');

        if (! is_file($path)) {
            throw new RuntimeException('.env tidak ditemukan.');
        }

        $env = (string) file_get_contents($path);

        if (preg_match('/^TELEGRAM_BOT_TOKEN=.*$/m', $env) === 1) {
            $env = (string) preg_replace('/^TELEGRAM_BOT_TOKEN=.*$/m', 'TELEGRAM_BOT_TOKEN='.$token, $env);
        } else {
            $env .= PHP_EOL.'TELEGRAM_BOT_TOKEN='.$token.PHP_EOL;
        }

        file_put_contents($path, $env);
        config(['services.telegram.bot_token' => $token]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getUpdates(int $offset = 0, int $timeout = 25): array
    {
        $response = Http::timeout($timeout + 15)->asForm()->post($this->endpoint('getUpdates'), [
            'offset' => $offset,
            'timeout' => $timeout,
            'allowed_updates' => json_encode(['message', 'callback_query', 'my_chat_member'], JSON_THROW_ON_ERROR),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Telegram getUpdates HTTP error: '.$response->status());
        }

        $json = $response->json();

        if (! is_array($json) || ($json['ok'] ?? false) !== true) {
            throw new RuntimeException('Telegram getUpdates returned an error.');
        }

        $result = $json['result'] ?? [];

        return is_array($result) ? array_values($result) : [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function call(string $method, array $payload = []): array
    {
        $response = $this->client()->asForm()->post($this->endpoint($method), $payload);

        if (! $response->successful()) {
            Log::error('Telegram API HTTP error.', [
                'method' => $method,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Telegram API request failed.');
        }

        $json = $response->json();

        if (! is_array($json) || ($json['ok'] ?? false) !== true) {
            Log::error('Telegram API returned not-ok.', [
                'method' => $method,
                'body' => $json,
            ]);

            throw new RuntimeException('Telegram API returned an error.');
        }

        return $json;
    }

    private function client(): PendingRequest
    {
        return Http::timeout(30);
    }

    private function endpoint(string $method): string
    {
        return sprintf('https://api.telegram.org/bot%s/%s', $this->token(), $method);
    }

    private function token(): string
    {
        $token = (string) config('services.telegram.bot_token');

        if ($token === '') {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        return $token;
    }
}
