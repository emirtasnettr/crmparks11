<?php

namespace Tests\Unit;

use App\Modules\LandingPage\Support\LandingPageContentSanitizer;
use PHPUnit\Framework\TestCase;

class LandingPageContentSanitizerTest extends TestCase
{
  public function test_it_normalizes_quill_empty_paragraphs(): void
  {
    $html = '<p><br></p><p>Merhaba</p><p><br></p><p><br></p><p>Dünya</p><p><br></p>';

    $normalized = LandingPageContentSanitizer::normalize($html);

    $this->assertSame('<p>Merhaba</p><p><br></p><p>Dünya</p>', $normalized);
  }

  public function test_it_preserves_lists_and_headings(): void
  {
    $html = '<h2>Başlık</h2><p>Metin</p><ul><li>Bir</li><li>İki</li></ul>';

    $this->assertSame($html, LandingPageContentSanitizer::clean($html));
  }
}
