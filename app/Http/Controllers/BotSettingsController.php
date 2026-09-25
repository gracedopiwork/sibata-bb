<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class BotSettingsController extends Controller
{
    public function edit(TelegramService $telegram): View
    {
        $token = (string) config('services.telegram.bot_token');
        $profile = null;

        if ($token !== '') {
            try {
                $me = $telegram->getMe();
                $profile = is_array($me['result'] ?? null) ? $me['result'] : null;
            } catch (Throwable) {
                $profile = null;
            }
        }

        return view('bot.edit', [
            'hasToken' => $token !== '',
            'profile' => $profile,
        ]);
    }

    public function update(Request $request, TelegramService $telegram): RedirectResponse
    {
        $data = $request->validate([
            'bot_token' => ['required', 'string', 'regex:/^\d+:[A-Za-z0-9_-]+$/'],
        ], [
            'bot_token.required' => 'Tempel token dari @BotFather.',
            'bot_token.regex' => 'Format token tidak valid. Contoh: 123456789:AAHxxxx',
        ]);

        $telegram->persistToken($data['bot_token']);

        try {
            $me = $telegram->getMe();
            $result = is_array($me['result'] ?? null) ? $me['result'] : [];
            $username = (string) ($result['username'] ?? 'bot');
            $telegram->deleteWebhook(true);
            $telegram->setMyCommands($telegram->defaultCommands());
        } catch (Throwable $exception) {
            return back()->withErrors([
                'bot_token' => 'Token tersimpan, tetapi Telegram menolak: '.$exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('bot.edit')
            ->with('status', "Bot @{$username} tersambung. Jalankan php artisan telegram:poll, lalu ketik /start di Telegram.");
    }
}
