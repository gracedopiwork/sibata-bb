<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBotService $bot): Response
    {
        $expected = (string) config('services.telegram.secret_token');

        if ($expected !== '') {
            $provided = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

            if (! hash_equals($expected, $provided)) {
                return response('Forbidden', 403);
            }
        }

        $update = $request->all();

        try {
            $bot->handle($update);
        } catch (\Throwable $exception) {
            Log::error('Telegram webhook handler failed.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return response('OK', 200);
    }
}
