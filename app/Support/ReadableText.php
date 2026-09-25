<?php

namespace App\Support;

class ReadableText
{
    /**
     * @var list<string>
     */
    private const WORDS = [
        'narkotika', 'handphone', 'sedangkan', 'potongan', 'kristal', 'berwarna',
        'tersimpan', 'dipinjam', 'berisi', 'berupa', 'dengan', 'sachet', 'kertas',
        'bening', 'brankas', 'bruto', 'netto', 'gram', 'lembar', 'kosong', 'jenis',
        'shabu', 'sabu', 'berat', 'buah', 'merk', 'merek', 'warna', 'hitam',
        'kecil', 'besar', 'foil', 'sidang', 'gudang', 'laci', 'unit',
    ];

    public static function make(?string $value): string
    {
        $text = trim(str_replace(["\u{00A0}", "\t", "\r"], ' ', (string) $value));

        if ($text === '') {
            return '';
        }

        $spaces = preg_match_all('/\s/u', $text) ?: 0;
        $length = max(mb_strlen($text), 1);

        if (($spaces / $length) >= 0.08) {
            return trim((string) preg_replace('/\s+/u', ' ', $text));
        }

        $text = (string) preg_replace('/(\d)\s*\(/u', '$1 (', $text);
        $text = (string) preg_replace('/\)(?=\S)/u', ') ', $text);
        $text = (string) preg_replace('/(\d)(?=\p{L})/u', '$1 ', $text);
        $text = (string) preg_replace('/(?<=\p{L})(\d)/u', ' $1', $text);
        $text = (string) preg_replace('/([a-z])([A-Z])/u', '$1 $2', $text);

        $words = self::WORDS;
        usort($words, fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left));

        foreach ($words as $word) {
            $quoted = preg_quote($word, '/');
            $text = (string) preg_replace('/(?<=\p{L})('.$quoted.')/iu', ' $1', $text);
            $text = (string) preg_replace('/('.$quoted.')(?=\p{L}|\d)/iu', '$1 ', $text);
        }

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
