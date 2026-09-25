<?php

namespace App\Services;

use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Log;
use Throwable;
use Zxing\QrReader;

class QrCodeService
{
    public function pngDataUri(string $payload, int $scale = 8): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'scale' => $scale,
            'outputBase64' => true,
        ]);

        return (new QRCode($options))->render($payload);
    }

    public function pngBinary(string $payload, int $scale = 8): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'scale' => $scale,
            'outputBase64' => false,
        ]);

        return (new QRCode($options))->render($payload);
    }

    public function decodeFromFile(string $absolutePath): ?string
    {
        try {
            $reader = new QrReader($absolutePath, QrReader::SOURCE_TYPE_FILE, false);
            $text = $reader->text();

            if (! is_string($text) || trim($text) === '') {
                return null;
            }

            return trim($text);
        } catch (Throwable $exception) {
            Log::warning('QR decode failed.', [
                'path' => $absolutePath,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
