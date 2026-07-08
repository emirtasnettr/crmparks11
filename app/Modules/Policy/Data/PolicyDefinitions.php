<?php

namespace App\Modules\Policy\Data;

class PolicyDefinitions
{
  public const KVKK = 'kvkk';

  public const PRIVACY = 'privacy';

  public const COOKIE = 'cookie';

  /**
   * @return array<string, array{key: string, slug: string, label: string, title: string}>
   */
  public static function all(): array
  {
    return [
      self::KVKK => [
        'key' => self::KVKK,
        'slug' => 'kvkk-aydinlatma-metni',
        'label' => 'KVKK Aydınlatma Metni',
        'title' => 'KVKK Aydınlatma Metni',
      ],
      self::PRIVACY => [
        'key' => self::PRIVACY,
        'slug' => 'gizlilik-politikasi',
        'label' => 'Gizlilik Politikası',
        'title' => 'Gizlilik Politikası',
      ],
      self::COOKIE => [
        'key' => self::COOKIE,
        'slug' => 'cerez-politikasi',
        'label' => 'Çerez Politikası',
        'title' => 'Çerez Politikası',
      ],
    ];
  }

  public static function findBySlug(string $slug): ?array
  {
    return collect(self::all())->firstWhere('slug', $slug);
  }

  /**
   * @return list<string>
   */
  public static function keys(): array
  {
    return array_keys(self::all());
  }
}
