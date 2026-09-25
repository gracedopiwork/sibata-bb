<?php

namespace Tests\Unit;

use App\Support\ReadableText;
use PHPUnit\Framework\TestCase;

class ReadableTextTest extends TestCase
{
    public function test_it_keeps_already_spaced_text(): void
    {
        $text = '1 (satu) sachet kristal bening berupa narkotika jenis shabu';

        $this->assertSame($text, ReadableText::make($text));
    }

    public function test_it_restores_spaces_in_jammed_item_names(): void
    {
        $this->assertSame(
            '7 (tujuh) sachet kecil berisi kristal bening narkotika jenis shabu',
            ReadableText::make('7(tujuh)sachetkecilberisikristalbeningnarkotikajenisshabu')
        );
    }
}
