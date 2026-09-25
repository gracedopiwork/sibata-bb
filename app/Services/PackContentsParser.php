<?php

namespace App\Services;

use App\Models\EvidenceCategory;

class PackContentsParser
{
    /**
     * Parse a pasted BA/daftar BB list. One item per line.
     * Format: nama | kategori | jumlah — kategori dan jumlah opsional.
     *
     * @return array<int, array{item_name: string, category: string, quantity: string}>
     */
    public function parse(string $text, ?string $defaultCategory = null): array
    {
        $default = $defaultCategory ?: (EvidenceCategory::active()->value('code') ?: 'NARKOTIKA');
        $categories = EvidenceCategory::query()
            ->get(['code', 'name'])
            ->mapWithKeys(fn (EvidenceCategory $category) => [
                mb_strtolower($category->code) => $category->code,
                mb_strtolower($category->name) => $category->code,
            ]);

        $items = [];

        foreach (preg_split('/\r\n|\r|\n/', $text) ?: [] as $raw) {
            $line = trim((string) $raw);
            if ($line === '') {
                continue;
            }

            $parts = array_values(array_filter(array_map('trim', preg_split('/\s*\|\s*/', $line) ?: []), fn ($part) => $part !== ''));
            $name = $parts[0] ?? '';
            if ($name === '') {
                continue;
            }

            $category = $default;
            $quantity = null;

            if (isset($parts[1])) {
                $resolved = $categories->get(mb_strtolower($parts[1]));
                if ($resolved !== null) {
                    $category = $resolved;
                } else {
                    $quantity = $parts[1];
                }
            }

            if (isset($parts[2])) {
                $quantity = $parts[2];
            }

            if ($quantity === null || $quantity === '') {
                $quantity = '1';
            }

            $items[] = [
                'item_name' => $name,
                'category' => $category,
                'quantity' => $quantity,
            ];
        }

        return $items;
    }
}
