<?php

namespace App\Modules\LandingPage\Support;

class LandingPageHero
{
  public const DISPLAY_WIDTH = 768;

  public const DISPLAY_HEIGHT = 420;

  public const RECOMMENDED_WIDTH = 1536;

  public const RECOMMENDED_HEIGHT = 840;

  public static function aspectClass(): string
  {
    return 'aspect-[768/420]';
  }

  public static function recommendedSizeLabel(): string
  {
    return self::RECOMMENDED_WIDTH.' × '.self::RECOMMENDED_HEIGHT.' px';
  }

  public static function minimumSizeLabel(): string
  {
    return self::DISPLAY_WIDTH.' × '.self::DISPLAY_HEIGHT.' px';
  }
}
