<?php

namespace App\Modules\LandingPage\Support;

class LandingPageContentSanitizer
{
  public static function clean(?string $html): string
  {
    if ($html === null || trim($html) === '') {
      return '';
    }

    $config = \HTMLPurifier_Config::createDefault();
    $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,a[href|title|target|rel],h2,h3,ul,ol,li,blockquote');
    $config->set('HTML.TargetBlank', true);
    $config->set('Attr.AllowedFrameTargets', ['_blank']);
    $config->set('AutoFormat.AutoParagraph', false);

    $html = (new \HTMLPurifier($config))->purify($html);

    return self::normalize($html);
  }

  public static function normalize(?string $html): string
  {
    if ($html === null || trim($html) === '') {
      return '';
    }

    $html = trim($html);

    if ($html === '<p><br></p>') {
      return '';
    }

    // Quill boş satırları <p><br></p> olarak üretir; fazlalıkları temizle.
    $html = preg_replace('/^(?:\s*<p>(?:\s|<br\s*\/?>|&nbsp;)*<\/p>)+/i', '', $html) ?? $html;
    $html = preg_replace('/(?:<p>(?:\s|<br\s*\/?>|&nbsp;)*<\/p>\s*)+$/i', '', $html) ?? $html;
    $html = preg_replace('/(?:<p>(?:\s|<br\s*\/?>|&nbsp;)*<\/p>\s*){2,}/i', '<p><br></p>', $html) ?? $html;

    return trim($html);
  }
}
